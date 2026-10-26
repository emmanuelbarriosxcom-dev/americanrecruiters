<?php
if (!defined('ABSPATH')) { exit; }
require_once __DIR__.'/inc/v21.php';
function ar_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','gallery','caption','style','script']);
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style(['assets/css/design-system.css','style.css']);
    register_nav_menus(['primary'=>'Navegación principal']);
}
add_action('after_setup_theme','ar_setup');
function ar_assets() {
    $version=wp_get_theme()->get('Version');
    // AQ: keep the existing font definitions, remove their blocking CSS round trip.
    $font_file=get_theme_file_path('/assets/css/fonts.css');
    $font_css=is_readable($font_file) ? file_get_contents($font_file) : false;
    if($font_css!==false && $font_css!=='') {
        $font_css=str_replace('../fonts/',esc_url_raw(get_theme_file_uri('/assets/fonts/')),$font_css);
        wp_register_style('ar-fonts',false,[],$version);
        wp_enqueue_style('ar-fonts');
        wp_add_inline_style('ar-fonts',$font_css);
    } else {
        wp_enqueue_style('ar-fonts',get_theme_file_uri('/assets/css/fonts.css'),[],$version);
    }
    wp_enqueue_style('ar-design',get_theme_file_uri('/assets/css/v21.css'),['ar-fonts'],$version);
    wp_enqueue_style('ar-theme',get_stylesheet_uri(),['ar-design'],$version);
    wp_enqueue_script('ar-navigation',get_theme_file_uri('/assets/js/v21.js'),[], $version,['strategy'=>'defer','in_footer'=>true]);
}
add_action('wp_enqueue_scripts','ar_assets');
/* Discover the unchanged Latin font before styles: avoid a late fallback swap. */
function ar_preload_primary_font() {
    echo '<link rel="preload" href="'.esc_url(get_theme_file_uri('/assets/fonts/montserrat-latin-v31.woff2')).'" as="font" type="font/woff2" crossorigin>';
}
add_action('wp_head','ar_preload_primary_font',1);
function ar_url($path='') { return esc_url(home_url('/'.ltrim($path,'/'))); }
function ar_logo() { return esc_url(get_theme_file_uri('/assets/images/american-recruiters-logo.png')); }
function ar_services() { return [
    ['Headhunting','servicios/headhunting/'],
    ['Servicio personalizado','servicios/reclutamiento-personalizado/'],
    ['Fast Placement','servicios/fast-placement/']
]; }
function ar_navigation() { ?>
<ul class="ar-menu"><li><a class="ar-header-link" href="<?php echo ar_url('servicios/'); ?>">Servicios</a><button class="ar-sub-toggle" aria-label="Mostrar servicios" aria-controls="ar-services-menu" aria-expanded="false">▾</button><ul class="ar-submenu" id="ar-services-menu" hidden><?php foreach(ar_services() as $s): ?><li><a href="<?php echo ar_url($s[1]); ?>"><?php echo esc_html($s[0]); ?></a></li><?php endforeach; ?></ul></li>
<?php foreach([['Cómo trabajamos','metodologia/'],['Nosotros','nosotros/'],['Blog','blog/'],['Enviar CV','candidatos/']] as $s): ?><li><a class="ar-header-link" href="<?php echo ar_url($s[1]); ?>"><?php echo esc_html($s[0]); ?></a></li><?php endforeach; ?>
<li class="ar-mobile-cta"><a href="<?php echo ar_url('contacto/'); ?>">Solicitar propuesta →</a></li></ul><?php }
function ar_hero($title,$description='Base estructural en desarrollo. Contenido pendiente de aprobación.',$heading=true){ ?>
<section class="ar-interior-hero"><div class="container"><nav class="ar-breadcrumb" aria-label="Ruta de navegación"><a href="<?php echo ar_url(); ?>">Inicio</a><span aria-hidden="true">/</span><span><?php echo esc_html($title); ?></span></nav><div class="overline">AMERICAN RECRUITERS MÉXICO</div><?php if($heading): ?><h1><?php echo esc_html($title); ?></h1><?php endif; ?><?php if($description!==''): ?><p><?php echo esc_html($description); ?></p><?php endif; ?></div></section><?php }
/* Only the known initial scaffolds receive automatic demo sections. */
function ar_is_scaffold($post_id=0) {
    $text=trim(wp_strip_all_tags(get_post_field('post_content',$post_id ?: get_the_ID())));
    if($text==='') return true;
    foreach(['Base estructural en desarrollo.','Servicio previsto en la arquitectura aprobada.','Documento legal pendiente de revisión'] as $prefix) {
        if(strpos($text,$prefix)===0) return true;
    }
    return false;
}
function ar_page_hero($description='Base estructural en desarrollo. Contenido pendiente de aprobación.') {
    $content=get_post_field('post_content',get_the_ID());
    ar_hero(get_the_title(),ar_is_scaffold() ? $description : '',!preg_match('/<h1\b/i',$content));
}
function ar_font_hints($urls,$relation) {
    if($relation==='preconnect') {
        $urls[]=['href'=>'https://fonts.gstatic.com','crossorigin'=>'anonymous'];
    }
    return $urls;
}
add_filter('wp_resource_hints','ar_font_hints',10,2);
/* Keep the approved photograph; optimize only its Home image block. */
function ar_home_hero_image($html) {
    if(!is_front_page() || strpos($html,'photo-1556761175-b413da4baf72')===false || !class_exists('WP_HTML_Tag_Processor')) return $html;
    $image=new WP_HTML_Tag_Processor($html);
    while($image->next_tag('IMG')) {
        if(strpos((string)$image->get_attribute('src'),'photo-1556761175-b413da4baf72')===false) continue;
        $base=content_url('/uploads/2026/10/ar-hero-editorial-');
        $image->set_attribute('src',$base.'1300.webp');
        $image->set_attribute('srcset',$base.'640.webp 640w, '.$base.'960.webp 960w, '.$base.'1300.webp 1300w');
        $image->set_attribute('sizes','(max-width: 820px) 80vw, 56vw');
        $image->set_attribute('width',1300);
        $image->set_attribute('height',975);
        $image->set_attribute('loading','eager');
        $image->set_attribute('fetchpriority','high');
        $image->set_attribute('decoding','async');
        break;
    }
    return $image->get_updated_html();
}
add_filter('the_content','ar_home_hero_image',20);
function ar_demo_form($candidate=false) { echo ar_f9_form($candidate ? 'candidate' : 'business'); }
function ar_patterns() {
    register_block_pattern_category('ar-editorial',['label'=>'American Recruiters · Ejecutivo Editorial']);
    $patterns=[
      'intro'=>['Introducción editorial','<!-- wp:group {"className":"ar-reading"} --><div class="wp-block-group ar-reading"><!-- wp:heading --><h2 class="wp-block-heading">Encabezado de sección</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Contenido pendiente de aprobación comercial.</p><!-- /wp:paragraph --></div><!-- /wp:group -->'],
      'cards'=>['Tarjetas de servicios','<!-- wp:columns {"className":"ar-grid-3"} --><div class="wp-block-columns ar-grid-3">'.str_repeat('<!-- wp:column {"className":"ar-card"} --><div class="wp-block-column ar-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Título de tarjeta</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Descripción pendiente de aprobación.</p><!-- /wp:paragraph --></div><!-- /wp:column -->',3).'</div><!-- /wp:columns -->'],
      'faq'=>['FAQ accesible','<!-- wp:details --><details class="wp-block-details"><summary>Pregunta pendiente de aprobación</summary><!-- wp:paragraph --><p>Respuesta pendiente de aprobación.</p><!-- /wp:paragraph --></details><!-- /wp:details -->'],
      'cta'=>['CTA compartido','<!-- wp:group {"className":"ar-cta-band"} --><div class="wp-block-group ar-cta-band"><!-- wp:heading --><h2 class="wp-block-heading">Solicitar propuesta</h2><!-- /wp:heading --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="'.ar_url('contacto/').'">Ir a contacto</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->']
    ];
    foreach($patterns as $id=>$p) register_block_pattern('ar-editorial/'.$id,['title'=>$p[0],'categories'=>['ar-editorial'],'content'=>$p[1]]);
}
add_action('init','ar_patterns');
/* Bootstrap only for the explicitly authorized staging. Never overwrites existing pages. */
function ar_staging_bootstrap(){
    if(!defined('AR_ENABLE_STAGING_BOOTSTRAP') || AR_ENABLE_STAGING_BOOTSTRAP!==true) return;
    if(wp_parse_url(home_url(),PHP_URL_HOST)!=='desarrollo.americanrecruiters.mx' || !current_user_can('manage_options') || get_option('ar_base_installed')) return;
    $pages=[['inicio','Inicio','',0],['servicios','Servicios','',0],['headhunting','Headhunting','page-service.php','servicios'],['reclutamiento-personalizado','Servicio personalizado de reclutamiento y selección','page-service.php','servicios'],['fast-placement','Fast Placement','page-service.php','servicios'],['metodologia','Cómo trabajamos','page-methodology.php',0],['nosotros','Nosotros','page-corporate.php',0],['blog','Blog','',0],['contacto','Contacto','page-contact.php',0],['candidatos','Enviar CV','page-candidates.php',0],['preguntas-frecuentes','Preguntas frecuentes','page-faq.php',0],['privacidad','Privacidad','',0],['sistema-visual','Sistema visual · Base de desarrollo','page-components.php',0]];
    $ids=[];
    foreach($pages as $p){
        $path=($p[3]?$p[3].'/':'').$p[0]; $existing=get_page_by_path($path);
        if($existing){$ids[$p[0]]=$existing->ID;continue;}
        $content='<!-- wp:paragraph --><p>Base estructural en desarrollo. Contenido pendiente de aprobación; esta página no ofrece funciones comerciales activas.</p><!-- /wp:paragraph -->';
        if($p[0]==='fast-placement') $content='<!-- wp:paragraph --><p>Servicio previsto en la arquitectura aprobada. Alcance, metodología y descripción comercial pendientes de confirmación. No se ofrecen plazos ni garantías en esta base.</p><!-- /wp:paragraph -->';
        if($p[0]==='privacidad') $content='<!-- wp:paragraph --><p>Documento legal pendiente de revisión y aprobación. No se recopilan solicitudes ni CV mediante los componentes de demostración de esta fase.</p><!-- /wp:paragraph -->';
        $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$p[1],'post_name'=>$p[0],'post_parent'=>$p[3]?($ids[$p[3]]??0):0,'post_content'=>$content],true);
        if(is_wp_error($id)) continue;
        $ids[$p[0]]=$id;
        if($p[2]) update_post_meta($id,'_wp_page_template',$p[2]);
    }
    if(!empty($ids['inicio'])&&!empty($ids['blog'])){update_option('show_on_front','page');update_option('page_on_front',$ids['inicio']);update_option('page_for_posts',$ids['blog']);}
    update_option('ar_base_installed',['version'=>'1.0.0','pages'=>$ids,'date'=>current_time('mysql')]);
}
add_action('after_switch_theme','ar_staging_bootstrap');


/* Real historical taxonomies; never link to an empty public archive. */
function ar_post_categories($post_id=0) {
    $categories=get_the_category($post_id ?: get_the_ID());
    if(!$categories || is_wp_error($categories)) return;
    echo '<ul class="ar-post-categories" aria-label="Categorías">';
    foreach($categories as $category) {
        echo '<li>';
        $url=(int)$category->count>0 || ar_v21_editorial_review() ? get_category_link($category->term_id) : '';
        if($url&&ar_v21_editorial_review())$url=ar_v21_review_url($url);
        if($url && !is_wp_error($url)) {
            echo '<a class="ar-category" href="'.esc_url($url).'">'.esc_html($category->name).'</a>';
        } else {
            echo '<span class="ar-category">'.esc_html($category->name).'</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
}


/* Fase 9. Deliberately no mail, storage, attachment or successful submission path. */
function ar_f9_staging() {
    return wp_parse_url(home_url(), PHP_URL_HOST)==='desarrollo.americanrecruiters.mx';
}
function ar_f9_form($kind='business') {
    if(!ar_f9_staging()) return '';
    $candidate=$kind==='candidate'; $prefix=$candidate?'ar-cv':'ar-business';
    ob_start(); ?>
    <section class="ar-f9" aria-label="<?php echo $candidate?'Formulario de candidatos — revisión':'Formulario empresarial — revisión'; ?>">
    <p class="ar-f9-status" id="<?php echo esc_attr($prefix); ?>-status"><strong>Demostración deshabilitada.</strong> No introduzcas datos personales. No se envían mensajes ni se reciben archivos. Privacidad, destinatario y seguridad operativa pendientes de aprobación.</p>
    <fieldset disabled aria-describedby="<?php echo esc_attr($prefix); ?>-status"><legend><?php echo $candidate?'Presentar perfil profesional — no habilitado':'Solicitud empresarial — no habilitada'; ?></legend>
    <?php
    $fields=$candidate ? ['name'=>['Nombre','text',120,true],'email'=>['Correo electrónico','email',254,true],'phone'=>['Teléfono — opcional','tel',30,false],'area'=>['Área profesional — opcional','text',120,false]] : ['name'=>['Nombre','text',120,true],'company'=>['Empresa','text',160,true],'email'=>['Correo corporativo','email',254,true],'phone'=>['Teléfono — opcional','tel',30,false]];
    foreach($fields as $name=>$f) { $id=$prefix.'-'.$name;
        echo '<label for="'.esc_attr($id).'">'.esc_html($f[0]).'</label><input disabled id="'.esc_attr($id).'" name="'.esc_attr($name).'" type="'.esc_attr($f[1]).'" maxlength="'.(int)$f[2].'"'.($f[3]?' required':'').' autocomplete="off">';
    }
    if(!$candidate): ?>
    <label for="ar-business-service">Servicio de interés</label><select disabled required id="ar-business-service" name="service"><option value="">Selecciona una opción</option><option value="headhunting">Headhunting</option><option value="personalizado">Servicio personalizado</option><option value="fast-placement">Fast Placement — información</option><option value="asesoramiento">No estoy seguro / asesoramiento inicial</option></select>
    <label for="ar-business-message">Mensaje sobre la necesidad de contratación</label><textarea disabled required id="ar-business-message" name="message" maxlength="3000" rows="5"></textarea><small>No incluyas CV, datos de candidatos ni información sensible.</small>
    <?php else: ?>
    <label for="ar-cv-pdf">Currículum PDF — carga deshabilitada</label><input disabled id="ar-cv-pdf" name="cv" type="file" accept=".pdf,application/pdf" aria-describedby="ar-cv-pdf-help"><small id="ar-cv-pdf-help">Límite técnico propuesto: 5 MB, pendiente de aprobación. No se solicitan identificaciones, datos bancarios ni información médica. El canal no se habilitará sin almacenamiento privado verificado.</small>
    <?php endif; ?>
    <p class="ar-f9-privacy">Información de privacidad y mecanismo aplicable pendientes de aprobación. No hay consentimiento activo ni recepción de datos en esta demostración.</p>
    <button disabled type="button" class="btn btn-purple"><?php echo $candidate?'Enviar CV — no disponible':'Solicitar propuesta — no disponible'; ?></button></fieldset>
    <p><a href="<?php echo ar_url('privacidad/'); ?>">Consultar el estado del aviso de privacidad</a></p>
    </section>
    <?php return ob_get_clean();
}
/* Replace only the two known draft mockups at render time; Gutenberg copy is untouched. */
function ar_f9_draft_demo($content) {
    if(!ar_f9_staging() || !is_main_query() || !in_the_loop() || !in_array(get_the_ID(),[45,47],true) || get_post_status()!=='draft' || !current_user_can('edit_post',get_the_ID())) return $content;
    return preg_replace_callback('~<div class="ar-demo-form">\s*<fieldset\b[^>]*>.*?</fieldset>\s*(?:<small>La recepción no está habilitada\. No introduzcas datos personales\.</small>\s*)?</div>~s',function() { return ar_f9_form(get_the_ID()===47?'candidate':'business'); },$content,1);
}
add_filter('the_content','ar_f9_draft_demo',25);
/* Pure validators prepared for later activation, not a data collection endpoint. */
function ar_f9_validate($kind,$raw) {
    $errors=[]; $clean=[];
    if(!in_array($kind,['business','candidate'],true) || !is_array($raw)) return ['errors'=>['form'=>'Formulario no válido.'],'data'=>[]];
    $limits=$kind==='business'?['name'=>120,'company'=>160,'email'=>254,'phone'=>30,'service'=>40,'message'=>3000]:['name'=>120,'email'=>254,'phone'=>30,'area'=>120];
    $required=$kind==='business'?['name','company','email','service','message']:['name','email'];
    foreach($limits as $field=>$limit) {
        if(isset($raw[$field])&&!is_string($raw[$field])) {$errors[$field]='Valor no válido.';continue;}
        $value=trim($raw[$field]??'');
        if(strlen($value)>$limit*4 || (function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):strlen($value))>$limit) $errors[$field]='Texto demasiado largo.';
        if(in_array($field,$required,true)&&$value==='') $errors[$field]='Completa este campo.';
        if($field==='email' && (!is_email($value)||preg_match('/[\r\n]/',$value))) $errors[$field]='Introduce un correo válido.';
        if($field==='service' && !in_array($value,['headhunting','personalizado','fast-placement','asesoramiento'],true)) $errors[$field]='Selecciona un servicio válido.';
        if($field==='phone' && $value!=='' && !preg_match('/^[0-9+().\s-]{5,30}$/',$value)) $errors[$field]='Revisa el teléfono.';
        $clean[$field]=$field==='message'?sanitize_textarea_field($value):sanitize_text_field($value);
        if(in_array($field,$required,true)&&trim($clean[$field])==='') $errors[$field]='Completa este campo con información válida.';
    }
    if(!empty($raw['website'])) $errors['spam']='Solicitud no admitida.';
    if(($raw['privacy']??'')!=='1') $errors['privacy']='La información de privacidad requiere validación antes de activar el canal.';
    return ['errors'=>$errors,'data'=>$errors?[]:$clean];
}
function ar_f9_pdf_check($file) {
    if(!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) return 'Carga no válida.';
    if(!isset($file['size']) || !is_int($file['size']) || $file['size']<1 || $file['size']>5*1024*1024) return 'El PDF debe pesar como máximo 5 MB.';
    if(!is_string($file['name']??null)||strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='pdf') return 'Solo se admite PDF.';
    $path=$file['tmp_name']??'';
    if(!is_string($path)||!is_uploaded_file($path)) return 'No es un archivo de carga HTTP válido.';
    return ar_f9_pdf_content_check($path);
}
function ar_f9_pdf_content_check($path) {
    if(!is_string($path)||!is_file($path)||!is_readable($path)||!class_exists('finfo')) return 'No se puede verificar el archivo.';
    $size=filesize($path);if(!$size||$size>5*1024*1024) return 'Tamaño no válido.';
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
    $fh=fopen($path,'rb');if(!$fh)return 'No se puede verificar el archivo.';
    $header=fread($fh,8);fseek($fh,max(0,$size-1024));$tail=stream_get_contents($fh);fclose($fh);
    if($mime!=='application/pdf'||!preg_match('/^%PDF-(?:1\.[0-9]|2\.0)/',$header)||strpos($tail,'%%EOF')===false) return 'Contenido PDF no válido.';
    /* Signature/MIME checks are not antivirus or proof that a PDF is safe. */
    return '';
}
function ar_f9_guard($method,$nonce_ok,$count) {
    if($method!=='POST') return 405;
    if(!$nonce_ok) return 403;
    if($count>=5) return 429;
    return 503; // Hard stop. No configuration flag can turn collection on.
}
function ar_f9_disabled_endpoint() {
    $method=$_SERVER['REQUEST_METHOD']??'';
    $nonce=isset($_POST['_wpnonce'])&&is_string($_POST['_wpnonce'])?sanitize_text_field(wp_unslash($_POST['_wpnonce'])):'';
    $nonce_ok=(bool)wp_verify_nonce($nonce,'ar_f9_disabled');
    $count=0;
    if($method==='POST'&&$nonce_ok) {
        $key='ar_f9_rate_'.hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'),wp_salt('nonce'));
        $count=(int)get_transient($key);set_transient($key,$count+1,10*MINUTE_IN_SECONDS);
    }
    $status=ar_f9_guard($method,$nonce_ok,$count);
    nocache_headers();
    wp_die('Canal deshabilitado. No se ha enviado ni almacenado ninguna solicitud. No introduzcas datos personales.','Canal no disponible',['response'=>$status]);
}
foreach(['ar_f9_business','ar_f9_candidate'] as $action) {
    add_action('admin_post_'.$action,'ar_f9_disabled_endpoint');
    add_action('admin_post_nopriv_'.$action,'ar_f9_disabled_endpoint');
}
/* Administrator-only deterministic QA. No customer data, mail or uploads. */
function ar_f9_qa_page() {
    if(!ar_f9_staging()||!current_user_can('manage_options')) return;
    $valid=['name'=>'QA FICTICIO','company'=>'Empresa QA','email'=>'qa@example.invalid','phone'=>'','service'=>'headhunting','message'=>'Datos ficticios para validar.','privacy'=>'1'];
    $cases=[];
    $cases['Empresa válida']=!ar_f9_validate('business',$valid)['errors'];
    $bad=$valid;$bad['email']='invalido';$cases['Email inválido rechazado']=isset(ar_f9_validate('business',$bad)['errors']['email']);
    $bad=$valid;$bad['name']=['array'];$cases['Array rechazado']=isset(ar_f9_validate('business',$bad)['errors']['name']);
    $bad=$valid;$bad['service']='otro';$cases['Servicio fuera de lista rechazado']=isset(ar_f9_validate('business',$bad)['errors']['service']);
    $bad=$valid;$bad['message']=str_repeat('x',3001);$cases['Longitud excesiva rechazada']=isset(ar_f9_validate('business',$bad)['errors']['message']);
    $bad=$valid;$bad['website']='spam';$cases['Honeypot rechazado']=isset(ar_f9_validate('business',$bad)['errors']['spam']);
    $cases['Campos vacíos rechazados']=count(ar_f9_validate('business',[])['errors'])>=5;
    $bad=$valid;$bad['name']='<b></b>';$cases['Nombre vacío tras sanitizar rechazado']=isset(ar_f9_validate('business',$bad)['errors']['name']);
    $bad=$valid;$bad['company']='<i></i>';$cases['Empresa vacía tras sanitizar rechazada']=isset(ar_f9_validate('business',$bad)['errors']['company']);
    $cases['Tamaño de PDF no entero rechazado']=ar_f9_pdf_check(['error'=>0,'size'=>'pequeno','name'=>'qa.pdf','tmp_name'=>''])!=='';
    $cases['Candidato sin empresa ni mensaje válido']=!ar_f9_validate('candidate',['name'=>'QA','email'=>'qa@example.invalid','privacy'=>'1'])['errors'];
    $cases['Sin autorización de privacidad rechazado']=isset(ar_f9_validate('candidate',['name'=>'QA','email'=>'qa@example.invalid'])['errors']['privacy']);
    $cases['CSRF inválido devuelve 403']=ar_f9_guard('POST',false,0)===403;
    $cases['GET devuelve 405']=ar_f9_guard('GET',true,0)===405;
    $cases['Límite devuelve 429']=ar_f9_guard('POST',true,5)===429;
    $cases['POST válido permanece bloqueado 503']=ar_f9_guard('POST',true,0)===503;
    $cases['Archivo demasiado grande rechazado']=ar_f9_pdf_check(['error'=>0,'size'=>5242881,'name'=>'qa.pdf','tmp_name'=>''])!=='';
    $cases['Extensión falsa rechazada']=ar_f9_pdf_check(['error'=>0,'size'=>10,'name'=>'qa.php','tmp_name'=>''])!=='';
    $cases['Ruta no subida rechazada']=ar_f9_pdf_check(['error'=>0,'size'=>10,'name'=>'qa.pdf','tmp_name'=>__FILE__])!=='';
    $cases['Contenido no PDF rechazado']=ar_f9_pdf_content_check(__FILE__)!=='';
    echo '<div class="wrap"><h1>Fase 9 — QA sin envíos</h1><p>Validación de funciones con datos ficticios. No verifica entrega de correo, procesamiento de una carga HTTP ni almacenamiento privado.</p><table class="widefat striped"><thead><tr><th>Prueba</th><th>Resultado</th></tr></thead><tbody>';
    foreach($cases as $name=>$pass)echo '<tr><td>'.esc_html($name).'</td><td>'.($pass?'PASS':'FAIL').'</td></tr>';
    echo '</tbody></table><p>Canales bloqueados. No existe ruta de éxito, correo ni almacenamiento.</p></div>';
}
add_action('admin_menu',function(){if(ar_f9_staging())add_management_page('Fase 9 — QA','Formularios — QA','manage_options','ar-f9-qa','ar_f9_qa_page');});

/* AR: unified editorial author graph, reviewed and integrated by AQ. */
require_once __DIR__.'/inc/editorial-author.php';
