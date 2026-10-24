<?php
namespace ARV;

final class Plugin {
    private static ?self $instance=null;
    private array $cfg;private Flow $flow;private PrivateStore $store;private Tokens $tokens;private array $pageTokens=[];private string $file;private bool $lab;
    public static function defaults(): array {
        return ['private_root'=>'','webroots'=>[],'ledger_ttl'=>0,'rate_limit'=>0,'rate_window'=>0,'log_retention_days'=>0,'tombstone_days'=>0,'temporary_ttl'=>0,
            'python'=>'','scanner_socket'=>'','replace_demo'=>false,'page_ids'=>['business'=>0,'candidate'=>0],
            'business'=>['enabled'=>false,'legal_approved'=>false,'security_verified'=>false,'transport_enabled'=>false,'recipient_confirmed'=>false,'sender_authenticated'=>false,'to'=>'','from'=>'','subject'=>'','notice_version'=>'','notice_url'=>'','privacy_text'=>'','ack_mode'=>''],
            'candidate'=>['enabled'=>false,'legal_approved'=>false,'security_verified'=>false,'storage_verified'=>false,'scanner_verified'=>false,'retention_approved'=>false,'backup_policy_confirmed'=>false,'retention_days'=>0,'max_bytes'=>0,'reviewers'=>[],'notice_version'=>'','notice_url'=>'','privacy_text'=>'','ack_mode'=>''],
            'cv_access_enabled'=>false];
    }
    public static function boot(string $file): self {
        if (self::$instance) return self::$instance;
        return self::$instance=new self($file);
    }
    public static function instance(): self { return self::$instance; }
    private function __construct(string $file) {
        $this->file=$file;
        $override=defined('ARV_CONFIG') && is_array(ARV_CONFIG)?ARV_CONFIG:[];
        $this->cfg=array_replace_recursive(self::defaults(),$override);
        foreach(['private_root','python','scanner_socket'] as $key) if(!is_string($this->cfg[$key]))$this->cfg[$key]='';
        foreach(['business','candidate'] as $key) if(!is_array($this->cfg[$key]))$this->cfg[$key]=self::defaults()[$key];
        if(!is_array($this->cfg['webroots']))$this->cfg['webroots']=[];
        $this->cfg['webroots']=array_filter($this->cfg['webroots'],'is_string');
        if(!is_array($this->cfg['page_ids']))$this->cfg['page_ids']=['business'=>0,'candidate'=>0];
        $this->cfg['origin']=self::origin(home_url('/'));
        $this->lab=defined('ARV_LAB') && ARV_LAB===true && wp_get_environment_type()==='local' && in_array(wp_parse_url(home_url('/'),PHP_URL_HOST),['127.0.0.1','localhost'],true);
        if (!$this->lab && strpos($this->cfg['origin'],'https://')!==0) { $this->cfg['business']['enabled']=false;$this->cfg['candidate']['enabled']=false; }
        $key=wp_salt('auth');
        $now=static function(){return time();};
        $this->tokens=new Tokens($key,$now);
        $this->store=new PrivateStore($this->cfg['private_root'],array_merge([ABSPATH,WP_CONTENT_DIR],$this->cfg['webroots']));
        $max=(int)($this->cfg['candidate']['max_bytes']?:5*1024*1024);
        $inspector=new ProcessInspector($this->cfg['python'],dirname($file).'/bin/pdf_inspect.py',$max);
        $scanner=$this->cfg['scanner_socket']!==''?new ClamdScanner($this->cfg['scanner_socket'],$max):new ClosedScanner();
        $transport=new WpTransport();
        // Explicit, local-only doubles. Never read test implementations from a request.
        if ($this->lab) { $transport=apply_filters('arv_lab_transport',$transport);$scanner=apply_filters('arv_lab_scanner',$scanner); }
        $this->flow=new Flow($this->cfg,$this->store,$this->tokens,$transport,$inspector,$scanner,$now,$key);
        foreach (['arv_submit'=> 'submit','arv_token'=>'token'] as $action=>$method) {
            add_action('admin_post_'.$action,[$this,$method]);add_action('admin_post_nopriv_'.$action,[$this,$method]);
        }
        foreach (['arv_cv_download'=>'download','arv_cv_delete'=>'delete'] as $action=>$method) {
            add_action('admin_post_'.$action,[$this,$method]);
            add_action('admin_post_nopriv_'.$action,static function(){status_header(403);nocache_headers();exit('Acceso no autorizado.');});
        }
        add_action('template_redirect',[$this,'prepare']);
        add_filter('the_content',[$this,'content'],30);
        add_action('wp_enqueue_scripts',[$this,'assets']);
        add_action('admin_menu',function(){add_management_page('CV privados','CV privados','arv_read_cv','arv-private',[$this,'admin']);});
        add_action('arv_private_cleanup',[$this,'cleanup']);
        // No activation hook enables intake, assigns capabilities, or schedules deletion.
    }
    public static function origin(string $url): string {
        $u=wp_parse_url($url);return strtolower(($u['scheme']??'').'://'.($u['host']??'')).(isset($u['port'])?':'.$u['port']:'');
    }
    private function kind(): string {
        $k=$_GET['channel']??$_POST['channel']??'';return is_string($k)&&in_array($k,['business','candidate'],true)?$k:'';
    }
    private function pageKind(): string {
        foreach ($this->cfg['page_ids'] as $k=>$id) if ($id>0 && is_page((int)$id)) return $k;
        return '';
    }
    private function session(bool $create=false): string {
        $name=is_ssl()?'__Host-arv_session':'arv_local_session';
        $s=is_string($_COOKIE[$name]??null)?$_COOKIE[$name]:'';
        if ($this->tokens->validSession($s)) return $s;
        if (!$create) return '';
        $s=$this->tokens->session();
        if (!is_ssl() && !$this->lab) return '';
        if (!setcookie($name,$s,['expires'=>0,'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Strict'])) return '';
        $_COOKIE[$name]=$s;return $s;
    }
    public function prepare(): void {
        $kind=$this->pageKind();
        if (!$kind || !$this->cfg['replace_demo']) return;
        nocache_headers();header('X-Frame-Options: SAMEORIGIN');
        if ($this->flow->enabled($kind)) {
            $s=$this->session(true);if ($s!=='') $this->pageTokens[$kind]=$this->tokens->issue($kind,$s);
        }
    }
    public function token(): void {
        $kind=$this->kind();nocache_headers();
        if (($_SERVER['REQUEST_METHOD']??'')!=='GET') wp_send_json(['state'=>'invalid_request'],405);
        $fetch=$_SERVER['HTTP_SEC_FETCH_SITE']??'';
        if (($fetch!=='' && !in_array($fetch,['same-origin','none'],true)) || (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']!==$this->cfg['origin'])) wp_send_json(['state'=>'invalid_request'],403);
        if (!$this->flow->enabled($kind)) wp_send_json(['state'=>'disabled'],503);
        $session=$this->session(true);if ($session==='') wp_send_json(['state'=>'disabled'],503);
        wp_send_json(['token'=>$this->tokens->issue($kind,$session)],200);
    }
    public function submit(): void {
        nocache_headers();$kind=$this->kind();$values=[];
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { $this->respond(['http'=>405,'state'=>'invalid_request','errors'=>[]],$kind,[]);return; }
        if ((int)($_SERVER['CONTENT_LENGTH']??0)>(($kind==='candidate'?(int)($this->cfg['candidate']['max_bytes']?:0):0)+65536)) { $this->respond(['http'=>413,'state'=>'invalid_request','errors'=>[]],$kind,[]);return; }
        $values=wp_unslash($_POST);unset($values['channel']);
        if (isset($values['action']) && $values['action']!=='arv_submit') { $this->respond(['http'=>400,'state'=>'invalid_request','errors'=>[]],$kind,[]);return; }
        $files=$kind==='candidate'?($_FILES['cv']??[]):$_FILES;
        if ($kind==='candidate' && array_diff(array_keys($_FILES),['cv'])) $values['unknown_file']='1';
        $origin=is_string($_SERVER['HTTP_ORIGIN']??null)?$_SERVER['HTTP_ORIGIN']:'';
        // Origin required for all POSTs; no trust in a spoofable forwarded-for header.
        $r=$this->flow->submit($kind,$values,is_array($files)?$files:[], $this->session(),$origin,(string)($_SERVER['REMOTE_ADDR']??'unknown'));
        if ($kind==='candidate' && $r['state']==='file_rejected') {
            // No CV was stored. A corrected document needs a fresh operation ID,
            // but is never resubmitted automatically.
            $r['next_csrf']=$this->tokens->issue($kind,$this->session());
            $values['csrf']=$r['next_csrf'];
        }
        $this->respond($r,$kind,$values);
    }
    private function respond(array $r,string $kind,array $values): void {
        if (strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false) wp_send_json($r,$r['http']);
        status_header($r['http']);header('Content-Type: text/html; charset=UTF-8');
        wp_enqueue_style('arv-forms',plugins_url('assets/forms.css',$this->file),[], '0.1.1');
        wp_enqueue_script('arv-forms',plugins_url('assets/forms.js',$this->file),[], '0.1.1',['strategy'=>'defer','in_footer'=>true]);
        get_header();echo '<main id="main" class="ar-page-content"><div class="container">';
        echo $this->render(in_array($kind,['business','candidate'],true)?$kind:'business',$r,$values);
        echo '</div></main>';get_footer();exit;
    }
    public function content(string $content): string {
        $kind=$this->pageKind();
        if (!$kind || !$this->cfg['replace_demo'] || strpos($content,'class="ar-f9"')===false) return $content;
        return preg_replace_callback('~<section class="ar-f9"[^>]*>.*?</section>~s',function()use($kind){return $this->render($kind);},$content,1);
    }
    public function assets(): void {
        if (!$this->cfg['replace_demo'] || !$this->pageKind()) return;
        wp_enqueue_style('arv-forms',plugins_url('assets/forms.css',$this->file),[], '0.1.1');
        wp_enqueue_script('arv-forms',plugins_url('assets/forms.js',$this->file),[], '0.1.1',['strategy'=>'defer','in_footer'=>true]);
    }
    public static function message(string $state,bool $lab=false): string {
        $m=['disabled'=>'Canal deshabilitado. No introduzcas datos personales.',
            'invalid_request'=>'No se pudo verificar la solicitud. Recarga el formulario antes de intentarlo de nuevo.',
            'invalid'=>'Revisa los campos indicados.', 'conflict'=>'La referencia ya se usó con otros datos. No se ha realizado otro envío.',
            'rate_limited'=>'Se alcanzó el límite de solicitudes. Inténtalo más tarde.',
            'accepted'=>'Solicitud procesada. El mensaje fue aceptado por el transporte; esto no confirma su recepción en el buzón.',
            'stored'=>'El documento ha quedado registrado de forma privada.',
            'processing'=>'Esta referencia ya se está procesando. No vuelvas a enviarla.',
            'rejected'=>'El transporte no aceptó el mensaje. No se ha reintentado automáticamente.',
            'unknown'=>'No se pudo confirmar el resultado. Conserva la referencia y no repitas el envío automáticamente.',
            'file_rejected'=>'No se admite el documento adjunto.'];
        return ($lab?'PRUEBA AISLADA, SIN CORREO REAL. ':'').($m[$state]??$m['unknown']);
    }
    public function render(string $kind,array $result=[],array $values=[]): string {
        $candidate=$kind==='candidate';$c=$this->cfg[$kind];$enabled=$this->flow->enabled($kind);$prefix=$candidate?'arv-cv':'arv-business';
        $csrf=$this->pageTokens[$kind]??(is_string($values['csrf']??null)?$values['csrf']:'');
        $errors=$result['errors']??[];$state=$result['state']??($enabled?'':'disabled');
        ob_start(); ?>
        <section class="ar-f9 arv-form" aria-label="<?php echo $candidate?'Formulario de candidatos':'Formulario empresarial'; ?>">
        <?php if($this->lab): ?><p class="arv-lab">Entorno privado de pruebas. Datos sintéticos; transporte simulado.</p><?php endif; ?>
        <div class="arv-result" id="<?php echo esc_attr($prefix); ?>-result" tabindex="-1" aria-live="polite" role="<?php echo $errors?'alert':'status'; ?>" <?php if($state==='')echo 'hidden'; ?>>
        <p><?php echo esc_html(self::message($state,$this->lab)); ?></p>
        <?php if(!empty($result['reference'])): ?><p>Referencia: <code><?php echo esc_html($result['reference']); ?></code></p><?php endif; ?>
        <?php if($errors): ?><ul><?php foreach($errors as $key=>$error): ?><li><?php if($key!=='form'): ?><a href="#<?php echo esc_attr($prefix.'-'.$key); ?>"><?php echo esc_html($error); ?></a><?php else:echo esc_html($error);endif; ?></li><?php endforeach; ?></ul><?php endif; ?>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=arv_submit&channel='.$kind)); ?>" enctype="multipart/form-data" data-arv-form data-prefix="<?php echo esc_attr($prefix); ?>" data-token-url="<?php echo esc_url(admin_url('admin-post.php?action=arv_token&channel='.$kind)); ?>" data-lab="<?php echo $this->lab?'1':'0'; ?>" aria-describedby="<?php echo esc_attr($prefix); ?>-result">
        <input type="hidden" name="csrf" value="<?php echo esc_attr($csrf); ?>">
        <fieldset <?php if(!$enabled || in_array($state,['accepted','stored','processing','unknown','rejected'],true)) echo 'disabled'; ?>><legend><?php echo $candidate?'Presentar perfil profesional':'Solicitud empresarial'; ?></legend>
        <?php $fields=$candidate?['name'=>['Nombre','text',120,true],'email'=>['Correo electrónico','email',254,true],'phone'=>['Teléfono — opcional','tel',30,false],'area'=>['Área profesional — opcional','text',120,false]]:['name'=>['Nombre','text',120,true],'company'=>['Empresa','text',160,true],'email'=>['Correo electrónico','email',254,true],'phone'=>['Teléfono — opcional','tel',30,false]];
        foreach($fields as $name=>$f): ?><label for="<?php echo esc_attr($prefix.'-'.$name); ?>"><?php echo esc_html($f[0]); ?></label><input id="<?php echo esc_attr($prefix.'-'.$name); ?>" name="<?php echo esc_attr($name); ?>" type="<?php echo esc_attr($f[1]); ?>" maxlength="<?php echo (int)$f[2]; ?>" value="<?php echo esc_attr(is_string($values[$name]??null)?$values[$name]:''); ?>" <?php if($f[3])echo 'required'; ?> autocomplete="<?php echo $name==='email'?'email':($name==='name'?'name':($name==='phone'?'tel':'organization')); ?>" <?php if(isset($errors[$name]))echo 'aria-invalid="true" aria-describedby="'.esc_attr($prefix.'-'.$name.'-error').'"'; ?>><?php echo $this->fieldError($prefix,$name,$errors); endforeach; ?>
        <?php if(!$candidate): ?><label for="arv-business-service">Servicio de interés</label><select id="arv-business-service" name="service" required <?php echo $this->fieldAttrs($prefix,'service',$errors); ?>><option value="">Selecciona una opción</option><?php foreach(['headhunting'=>'Headhunting','personalizado'=>'Servicio personalizado','fast-placement'=>'Fast Placement — información','asesoramiento'=>'No estoy seguro / asesoramiento inicial'] as $value=>$label): ?><option value="<?php echo esc_attr($value); ?>" <?php selected(is_string($values['service']??null)?$values['service']:'', $value); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select><?php echo $this->fieldError($prefix,'service',$errors); ?>
        <label for="arv-business-message">Mensaje sobre la necesidad de contratación</label><textarea id="arv-business-message" name="message" rows="5" maxlength="3000" required aria-describedby="arv-message-help" <?php echo $this->fieldAttrs($prefix,'message',$errors); ?>><?php echo esc_textarea(is_string($values['message']??null)?$values['message']:''); ?></textarea><small id="arv-message-help">No incluyas CV ni información confidencial.</small><?php echo $this->fieldError($prefix,'message',$errors); ?>
        <?php else: ?><label for="arv-cv-cv">Currículum PDF</label><input id="arv-cv-cv" name="cv" type="file" accept=".pdf,application/pdf" required aria-describedby="arv-cv-help" <?php echo $this->fieldAttrs($prefix,'cv',$errors); ?>><small id="arv-cv-help">PDF estándar, sin cifrado, adjuntos ni contenido activo. Límite configurado: <?php echo esc_html(size_format((int)$c['max_bytes'])); ?>. No adjuntes identificaciones ni datos médicos o bancarios.</small><?php echo $this->fieldError($prefix,'cv',$errors); ?><?php endif; ?>
        <div class="arv-honeypot" aria-hidden="true"><label>Deja este campo vacío<input name="website" value="" tabindex="-1" autocomplete="off"></label></div>
        <?php if($enabled): ?><p><?php echo esc_html($c['privacy_text']); ?> <a href="<?php echo esc_url($c['notice_url']); ?>">Consultar aviso de privacidad</a></p><?php if($c['ack_mode']==='acknowledgement'): ?><label class="arv-check"><input type="checkbox" name="privacy" value="1" required id="<?php echo esc_attr($prefix); ?>-privacy" <?php echo $this->fieldAttrs($prefix,'privacy',$errors); ?>> <?php echo esc_html($c['ack_text']??''); ?></label><?php echo $this->fieldError($prefix,'privacy',$errors); ?><?php endif; ?><?php else: ?><p>Información de privacidad y mecanismo aplicable pendientes de aprobación. No hay recepción de datos.</p><?php endif; ?>
        <button class="btn btn-purple" type="submit" <?php if(!$csrf)echo 'disabled'; ?>><?php echo $candidate?'Presentar perfil':'Solicitar propuesta'; ?></button></fieldset></form></section>
        <?php return ob_get_clean();
    }
    private function fieldAttrs(string $prefix,string $name,array $errors): string { return isset($errors[$name])?'aria-invalid="true" aria-errormessage="'.esc_attr($prefix.'-'.$name.'-error').'"':''; }
    private function fieldError(string $prefix,string $name,array $errors): string { return isset($errors[$name])?'<p class="arv-field-error" id="'.esc_attr($prefix.'-'.$name.'-error').'">'.esc_html($errors[$name]).'</p>':''; }
    private function access(string $op): array {
        nocache_headers();
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST' || array_diff(array_keys($_POST),['id','_wpnonce','_wp_http_referer','action'])) $this->deny(400);
        $id=$_POST['id']??'';
        if (!is_string($id) || !preg_match('/^[a-f0-9]{64}$/D',$id) || !$this->cfg['cv_access_enabled'] || !current_user_can($op==='delete'?'arv_delete_cv':'arv_read_cv')) $this->deny(403);
        $nonce=$_POST['_wpnonce']??'';
        if (!is_string($nonce) || !wp_verify_nonce($nonce,'arv_'.$op.'_'.$id)) $this->deny(403);
        try { $this->store->initialize(); } catch(\Throwable $e) { $this->deny(503); }
        return [$id,get_current_user_id()];
    }
    private function deny(int $status): void { status_header($status);nocache_headers();exit('Documento no disponible.'); }
    public function download(): void {
        [$id,$user]=$this->access('download');
        try { $path=$this->store->downloadPath($id,$user,time()); } catch(\Throwable $e) { $this->deny(503); }
        if (!$path) $this->deny(404);
        header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="cv-'.$id.'.pdf"');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store, max-age=0');header('Content-Length: '.filesize($path));readfile($path);exit;
    }
    public function delete(): void {
        [$id,$user]=$this->access('delete');
        try { $deleted=$this->store->delete($id,$user,time()); } catch(\Throwable $e) { $this->deny(503); }
        if (!$deleted) $this->deny(404);
        wp_safe_redirect(admin_url('tools.php?page=arv-private'));exit;
    }
    public function admin(): void {
        if (!current_user_can('arv_read_cv') || !$this->cfg['cv_access_enabled']) {echo '<p>Acceso deshabilitado.</p>';return;}
        try { $this->store->initialize();$rows=$this->store->records(get_current_user_id()); } catch(\Throwable $e) {echo '<p>Almacén no disponible.</p>';return;}
        echo '<div class="wrap"><h1>CV privados</h1><p>Referencias autorizadas para tu cuenta. No hay enlaces públicos.</p>';
        foreach($rows as $r) {
            echo '<article><h2>'.esc_html($r['id']).'</h2>';
            foreach(['download'=>'Descargar PDF','delete'=>'Eliminar expediente'] as $op=>$label) {
                if ($op==='delete' && !current_user_can('arv_delete_cv')) continue;
                echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="arv_cv_'.$op.'"><input type="hidden" name="id" value="'.esc_attr($r['id']).'">';wp_nonce_field('arv_'.$op.'_'.$r['id']);echo '<button type="submit">'.esc_html($label).'</button></form>';
            }
            echo '</article>';
        }
        echo '</div>';
    }
    public function cleanup(): void {
        $c=$this->cfg['candidate'];
        if (($c['retention_approved']??false)!==true || ($c['backup_policy_confirmed']??false)!==true) return;
        try { $this->store->initialize();$this->store->cleanup(time(),(int)$this->cfg['log_retention_days'],(int)$this->cfg['tombstone_days'],(int)$this->cfg['temporary_ttl']); } catch(\Throwable $e) { /* No paths or PII in public logs. */ }
    }
}
