<?php
namespace ARV;

interface Transport { public function send(array $message): string; }
interface PdfInspector { public function inspect(string $path): bool; }
interface Scanner { public function clean(string $path): bool; }

final class ClosedTransport implements Transport {
    public function send(array $message): string { return 'rejected'; }
}
final class ClosedScanner implements Scanner {
    public function clean(string $path): bool { return false; }
}
final class WpTransport implements Transport {
    public function send(array $m): string {
        // Called only after the independent activation gate and an atomic claim.
        try {
            $headers = ['Content-Type: text/plain; charset=UTF-8',
                'From: '.$m['from'], 'Reply-To: '.$m['reply_to']];
            return wp_mail($m['to'], $m['subject'], $m['body'], $headers) ? 'accepted' : 'rejected';
        } catch (\Throwable $e) { return 'unknown'; }
    }
}

final class Input {
    // Fase 9 limits retained; this plugin does not require an active theme.
    public static function validate(string $kind, array $raw, bool $ack): array {
        $limits = $kind === 'business'
            ? ['name'=>120,'company'=>160,'email'=>254,'phone'=>30,'service'=>40,'message'=>3000]
            : ['name'=>120,'email'=>254,'phone'=>30,'area'=>120];
        $required = $kind === 'business' ? ['name','company','email','service','message'] : ['name','email'];
        $errors=[]; $data=[];
        foreach ($raw as $k=>$v) {
            if (!in_array($k,array_merge(array_keys($limits),['csrf','privacy','website','action']),true)) $errors['form']='La solicitud contiene campos no admitidos.';
        }
        foreach ($limits as $field=>$limit) {
            if (isset($raw[$field]) && !is_string($raw[$field])) { $errors[$field]='Valor no válido.'; continue; }
            $value=trim($raw[$field]??'');
            $length=function_exists('mb_strlen')?mb_strlen($value,'UTF-8'):preg_match_all('/./us',$value);
            if (strlen($value)>$limit*4 || !preg_match('//u',$value) || $length===false || $length>$limit) $errors[$field]='Texto no válido o demasiado largo.';
            if (in_array($field,$required,true) && $value==='') $errors[$field]='Completa este campo.';
            if ($field==='email' && (!is_email($value) || preg_match('/[\r\n]/',$value))) $errors[$field]='Introduce un correo válido.';
            if ($field==='service' && !in_array($value,['headhunting','personalizado','fast-placement','asesoramiento'],true)) $errors[$field]='Selecciona un servicio válido.';
            if ($field==='phone' && $value!=='' && !preg_match('/^[0-9+(). -]{5,30}$/D',$value)) $errors[$field]='Revisa el teléfono.';
            $data[$field]=$field==='message'?sanitize_textarea_field($value):sanitize_text_field($value);
            if (in_array($field,$required,true) && trim($data[$field])==='') $errors[$field]='Completa este campo con información válida.';
        }
        if (isset($raw['website']) && (!is_string($raw['website']) || $raw['website']!=='')) $errors['form']='Solicitud no admitida.';
        if ($ack && ($raw['privacy']??null)!=='1') $errors['privacy']='Confirma la información indicada junto al formulario.';
        if (!$ack && isset($raw['privacy']) && $raw['privacy']!=='') $errors['privacy']='Valor no admitido.';
        return ['data'=>$errors?[]:$data,'errors'=>$errors];
    }
    public static function mailbox($value): bool {
        return is_string($value) && !preg_match('/[\r\n,<>]/',$value) && (bool)is_email($value);
    }
}

final class Tokens {
    private string $key; private $now; private int $ttl;
    public function __construct(string $key, callable $now, int $ttl=1800) { $this->key=$key; $this->now=$now; $this->ttl=$ttl; }
    private function mac(string $s): string { return hash_hmac('sha256',$s,$this->key); }
    public function session(): string { $id=bin2hex(random_bytes(32)); return $id.'.'.$this->mac('session|'.$id); }
    public function validSession(string $s): bool {
        if (strlen($s)!==129) return false;
        $p=explode('.',$s); return count($p)===2 && (bool)preg_match('/^[a-f0-9]{64}$/D',$p[0]) && hash_equals($this->mac('session|'.$p[0]),$p[1]);
    }
    public function issue(string $kind,string $session): string {
        if (!$this->validSession($session)) throw new \RuntimeException('session');
        $payload=$kind.'.'.bin2hex(random_bytes(32)).'.'.(($this->now)()+$this->ttl);
        return $payload.'.'.$this->mac('csrf|'.$session.'|'.$payload);
    }
    public function verify(string $kind,string $session,string $token): ?string {
        if (!$this->validSession($session) || strlen($token)>240) return null;
        $p=explode('.',$token);
        if (count($p)!==4 || $p[0]!==$kind || !preg_match('/^[a-f0-9]{64}$/D',$p[1]) || !ctype_digit($p[2])) return null;
        $exp=(int)$p[2]; $now=($this->now)();
        if ($exp<=$now || $exp>$now+$this->ttl) return null;
        $payload=implode('.',array_slice($p,0,3));
        return hash_equals($this->mac('csrf|'.$session.'|'.$payload),$p[3]) ? $p[1] : null;
    }
}

final class Flow {
    private array $cfg; private PrivateStore $store; private Tokens $tokens;
    private Transport $transport; private PdfInspector $inspector; private Scanner $scanner; private $now; private string $key;
    public function __construct(array $cfg,PrivateStore $store,Tokens $tokens,Transport $transport,PdfInspector $inspector,Scanner $scanner,callable $now,string $key) {
        $this->cfg=$cfg;$this->store=$store;$this->tokens=$tokens;$this->transport=$transport;$this->inspector=$inspector;$this->scanner=$scanner;$this->now=$now;$this->key=$key;
    }
    public function enabled(string $kind): bool {
        if (!in_array($kind,['business','candidate'],true)) return false;
        $c=$this->cfg[$kind]??[];
        if (!is_array($c)) return false;
        foreach (['enabled','legal_approved','security_verified'] as $flag) if (($c[$flag]??false)!==true) return false;
        foreach (['notice_version','privacy_text','notice_url','ack_mode'] as $field) if (!is_string($c[$field]??null)) return false;
        if (empty($c['notice_version']) || empty($c['privacy_text']) || !filter_var($c['notice_url']??'',FILTER_VALIDATE_URL) || !in_array($c['ack_mode']??'', ['acknowledgement','notice_only'],true)) return false;
        if (Plugin::origin($c['notice_url'])!==$this->cfg['origin'] || ($c['ack_mode']==='acknowledgement' && (!is_string($c['ack_text']??null) || $c['ack_text']===''))) return false;
        if (empty($this->cfg['private_root']) || ($this->cfg['ledger_ttl']??0)<3600 || ($this->cfg['rate_limit']??0)<1 || ($this->cfg['rate_window']??0)<60 || ($this->cfg['log_retention_days']??0)<1) return false;
        if ($kind==='business') return !($this->transport instanceof ClosedTransport) && ($c['transport_enabled']??false)===true && ($c['recipient_confirmed']??false)===true && ($c['sender_authenticated']??false)===true && Input::mailbox($c['to']??null) && Input::mailbox($c['from']??null) && is_string($c['subject']??null) && $c['subject']!=='' && strlen($c['subject'])<=160 && !preg_match('/[\r\n]/',$c['subject']);
        return !($this->scanner instanceof ClosedScanner) && is_string($this->cfg['python']??null) && strpos($this->cfg['python'],'/')===0 && ($c['storage_verified']??false)===true && ($c['scanner_verified']??false)===true && ($c['retention_approved']??false)===true && ($c['backup_policy_confirmed']??false)===true && ($this->cfg['temporary_ttl']??0)>=300 && ($this->cfg['tombstone_days']??0)>0 && ($c['retention_days']??0)>0 && ($c['max_bytes']??0)>0 && ($c['max_bytes']??PHP_INT_MAX)<=20*1024*1024 && !empty($c['reviewers']) && is_array($c['reviewers']) && $this->store->reviewersValid($c['reviewers']);
    }
    private function answer(int $http,string $state,array $more=[]): array { return array_merge(['http'=>$http,'state'=>$state,'errors'=>[]],$more); }
    public function submit(string $kind,array $raw,array $file,string $session,string $origin,string $ip): array {
        if (!$this->enabled($kind)) return $this->answer(503,'disabled');
        if ($origin!==$this->cfg['origin']) return $this->answer(403,'invalid_request');
        $id=$this->tokens->verify($kind,$session,is_string($raw['csrf']??null)?$raw['csrf']:'');
        if ($id===null) return $this->answer(403,'invalid_request');
        $c=$this->cfg[$kind];
        $v=Input::validate($kind,$raw,$c['ack_mode']==='acknowledgement');
        if ($kind==='business' && $file) $v['errors']['form']='Este formulario no admite archivos.';
        if ($v['errors']) return $this->answer(422,'invalid',['errors'=>$v['errors']]);
        $data=$v['data'];
        $fingerprint=hash_hmac('sha256',json_encode([$kind,$data,$c['notice_version']],JSON_UNESCAPED_UNICODE),$this->key);
        $now=($this->now)(); $fileDigest='';
        if ($kind==='candidate') {
            $err=PdfUpload::basic($file,(int)$c['max_bytes']);
            if ($err!=='') return $this->answer(422,'invalid',['errors'=>['cv'=>$err]]);
            $fileDigest=hash_file('sha256',$file['tmp_name']);
            $fingerprint=hash_hmac('sha256',$fingerprint.'|'.$fileDigest,$this->key);
        }
        try {
            $this->store->initialize();
            $previous=$this->store->readState($id);
            if ($previous!==null) return $this->repeat($previous,$fingerprint,$id);
            if (!$this->store->rate(hash_hmac('sha256',$kind.'|'.$ip,$this->key),$now,(int)$this->cfg['rate_limit'],(int)$this->cfg['rate_window'])) return $this->answer(429,'rate_limited');
            if (!$this->store->claim($id,$fingerprint,$now+(int)$this->cfg['ledger_ttl'])) return $this->repeat($this->store->readState($id)??[],$fingerprint,$id);
            if ($kind==='business') {
                $body="Solicitud empresarial\nReferencia: ".$id."\n";
                foreach ($data as $name=>$value) $body.=$name.': '.$value."\n";
                $body.='Aviso: '.$c['notice_version'];
                try { $state=$this->transport->send(['to'=>$c['to'],'from'=>$c['from'],'reply_to'=>$data['email'],'subject'=>$c['subject'],'body'=>$body]); }
                catch (\Throwable $e) { $state='unknown'; }
                if (!in_array($state,['accepted','rejected','unknown'],true)) $state='unknown';
            } else {
                $q=$this->store->quarantine($file['tmp_name']);
                try {
                    if (!$this->inspector->inspect($q) || !$this->scanner->clean($q)) {
                        $state='file_rejected';
                    } else {
                        $this->store->saveCv($id,$q,$data,$c['reviewers'],$now,$now+(int)$c['retention_days']*86400,$c['notice_version']);
                        $state='stored';
                    }
                } finally { if (is_file($q)) unlink($q); }
            }
            $this->store->finish($id,$fingerprint,$state,$now+(int)$this->cfg['ledger_ttl']);
            $this->store->log($state,$id,0,$now);
            return $this->stateAnswer($state,$id);
        } catch (\Throwable $e) {
            // Do not retry a reserved operation: a transport/storage result may be ambiguous.
            return $this->answer(503,'unknown',['reference'=>$id]);
        }
    }
    private function repeat(array $previous,string $fingerprint,string $id): array {
        if (isset($previous['fingerprint']) && !hash_equals($previous['fingerprint'],$fingerprint)) return $this->answer(409,'conflict');
        return $this->stateAnswer($previous['state']??'processing',$id)+['duplicate'=>true];
    }
    private function stateAnswer(string $s,string $id): array {
        $http=['accepted'=>200,'stored'=>200,'processing'=>202,'rejected'=>502,'unknown'=>503,'file_rejected'=>422][$s]??503;
        return $this->answer($http,$s,['reference'=>$id,'errors'=>$s==='file_rejected'?['cv'=>'No se admite este documento. Usa un PDF estándar sin cifrado, archivos adjuntos o contenido activo.']:[]]);
    }
}
