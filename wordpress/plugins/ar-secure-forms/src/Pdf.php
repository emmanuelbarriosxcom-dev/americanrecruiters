<?php
namespace ARV;

final class PdfUpload {
    public static function basic(array $f,int $max): string {
        if (($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) return 'La carga no se completó.';
        if (!is_string($f['name']??null) || strtolower(pathinfo($f['name'],PATHINFO_EXTENSION))!=='pdf') return 'Selecciona un archivo PDF.';
        if (!is_int($f['size']??null) || $f['size']<1 || $f['size']>$max) return 'El archivo supera el límite permitido o está vacío.';
        if (!is_string($f['tmp_name']??null) || !is_uploaded_file($f['tmp_name'])) return 'No se pudo verificar la carga.';
        $path=$f['tmp_name'];
        clearstatcache(true,$path);
        if (!is_file($path) || filesize($path)!==$f['size'] || !class_exists('finfo')) return 'No se pudo verificar el archivo.';
        if ((new \finfo(FILEINFO_MIME_TYPE))->file($path)!=='application/pdf') return 'El contenido no corresponde a un PDF.';
        $h=fopen($path,'rb');if (!$h) return 'No se pudo leer el archivo.';
        try { $head=fread($h,8);fseek($h,max(0,$f['size']-1024));$tail=stream_get_contents($h); }
        finally { fclose($h); }
        if (!preg_match('/^%PDF-(?:1\.[0-7]|2\.0)$/D',$head) || !preg_match('/%%EOF\s*$/D',$tail)) return 'El documento está incompleto o no es un PDF admitido.';
        return '';
    }
}

final class ProcessInspector implements PdfInspector {
    private string $python;private string $worker;private int $max;private int $timeout;
    public function __construct(string $python,string $worker,int $max,int $timeout=8) { $this->python=$python;$this->worker=$worker;$this->max=$max;$this->timeout=$timeout; }
    public function inspect(string $path): bool {
        if (!function_exists('proc_open') || !is_file($this->worker) || $this->python==='' || $this->python[0]!=='/' || is_link($path)) return false;
        $pipes=[];$p=null;
        try {
            // Array argv: no shell, no interpolated command or original upload name.
            $p=proc_open([$this->python,$this->worker,$path,(string)$this->max],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
            if (!is_resource($p)) return false;
            fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);
            $start=microtime(true);$out='';$err='';$exit=-1;
            do {
                $out.=stream_get_contents($pipes[1],4096);$err.=stream_get_contents($pipes[2],4096);
                if (strlen($out)>8192 || strlen($err)>8192 || microtime(true)-$start>$this->timeout) { proc_terminate($p,9);return false; }
                $s=proc_get_status($p);
                if (!$s['running']) { $exit=$s['exitcode'];break; }
                usleep(10000);
            } while (true);
            $out.=stream_get_contents($pipes[1]);$err.=stream_get_contents($pipes[2]);
            $result=json_decode($out,true);
            return $exit===0 && trim($err)==='' && is_array($result) && ($result['ok']??false)===true;
        } catch (\Throwable $e) { return false; }
        finally {
            foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
            if (is_resource($p)) proc_close($p);
        }
    }
}

final class ClamdScanner implements Scanner {
    private string $socket;private int $max;private $connect;
    public function __construct(string $socket,int $max,?callable $connect=null) { $this->socket=$socket;$this->max=$max;$this->connect=$connect; }
    public static function verdict(string $reply): bool { return trim($reply,"\0\r\n ")==='stream: OK'; }
    public function clean(string $path): bool {
        if (strpos($this->socket,'unix:///')!==0 || !is_file($path) || filesize($path)>$this->max) return false;
        $socket=$this->connect?($this->connect)($this->socket):@stream_socket_client($this->socket,$errno,$error,3);
        if (!$socket) return false;
        $file=fopen($path,'rb');if (!$file) { fclose($socket);return false; }
        stream_set_timeout($socket,5);
        try {
            if (!$this->write($socket,"zINSTREAM\0")) return false;
            while (!feof($file)) { $part=fread($file,65536);if ($part===false || !$this->write($socket,pack('N',strlen($part)).$part)) return false; }
            if (!$this->write($socket,pack('N',0))) return false;
            $reply='';
            while (strlen($reply)<=1024 && !feof($socket)) {
                $c=fread($socket,1025-strlen($reply));if ($c===false) return false;$reply.=$c;
                if (strpos($reply,"\0")!==false || strpos($reply,"\n")!==false) break;
                if ((stream_get_meta_data($socket)['timed_out']??false)) return false;
            }
            return strlen($reply)<=1024 && self::verdict($reply);
        } catch (\Throwable $e) { return false; }
        finally { fclose($file);fclose($socket); }
    }
    private function write($h,string $data): bool {
        while ($data!=='') { $n=fwrite($h,$data);if (!$n) return false;$data=substr($data,$n); }
        return true;
    }
}
