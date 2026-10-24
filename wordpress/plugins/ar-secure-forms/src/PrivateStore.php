<?php
namespace ARV;

final class PrivateStore {
    private string $root; private array $webroots;
    public function __construct(string $root,array $webroots) { $this->root=rtrim($root,'/');$this->webroots=$webroots; }
    public function initialize(): void {
        if ($this->root==='' || $this->root[0]!=='/' || strpos($this->root,"\0")!==false) throw new \RuntimeException('private_root');
        foreach ($this->webroots as $web) {
            $web=rtrim(realpath($web)?:$web,'/');
            if ($this->root===$web || strpos($this->root.'/', $web.'/')===0) throw new \RuntimeException('webroot');
        }
        if (!is_dir($this->root)) { if (!@mkdir($this->root,0700) && !is_dir($this->root)) throw new \RuntimeException('mkdir'); }
        if (is_link($this->root) || realpath($this->root)!==$this->root) throw new \RuntimeException('symlink');
        $marker=$this->root.'/.arv-private';
        if (!is_file($marker)) {
            if (count(scandir($this->root))!==2) throw new \RuntimeException('unowned_root');
            $this->writeExclusive($marker,"ARV private storage v1\n");
        }
        if ((fileperms($this->root)&0777)!==0700) throw new \RuntimeException('permissions');
        foreach (['ledger','rate','quarantine','cv','tombstones','logs'] as $dir) {
            $p=$this->root.'/'.$dir;
            if (!is_dir($p) && !@mkdir($p,0700) && !is_dir($p)) throw new \RuntimeException('mkdir');
            if (is_link($p) || (fileperms($p)&0777)!==0700) throw new \RuntimeException('permissions');
        }
    }
    private function validId(string $id): void { if (!preg_match('/^[a-f0-9]{64}$/D',$id)) throw new \RuntimeException('id'); }
    private function path(string $folder,string $id,string $ext='.json'): string { $this->validId($id);return $this->root.'/'.$folder.'/'.$id.$ext; }
    private function writeExclusive(string $path,string $data): bool {
        $old=umask(0077);
        try { $f=@fopen($path,'x+b'); } finally { umask($old); }
        if (!$f) return false;
        if (!chmod($path,0600)) { fclose($f);@unlink($path);throw new \RuntimeException('permissions'); }
        try { if (fwrite($f,$data)!==strlen($data) || !fflush($f)) throw new \RuntimeException('write'); if (function_exists('fsync')) fsync($f); }
        catch (\Throwable $e) { @unlink($path);throw $e; }
        finally { fclose($f); }
        if ((fileperms($path)&0777)!==0600) throw new \RuntimeException('permissions');
        return true;
    }
    private function atomic(string $path,array $data): void {
        $tmp=dirname($path).'/'.bin2hex(random_bytes(24)).'.tmp';
        if (!$this->writeExclusive($tmp,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))) throw new \RuntimeException('write');
        if (!rename($tmp,$path)) { @unlink($tmp);throw new \RuntimeException('rename'); }
    }
    private function read(string $p): ?array {
        if (!is_file($p)) return null;
        if (is_link($p) || (fileperms($p)&0777)!==0600 || filesize($p)>65536) throw new \RuntimeException('file');
        $s=file_get_contents($p);$d=json_decode($s,true);return is_array($d)?$d:[];
    }
    public function readState(string $id): ?array { return $this->read($this->path('ledger',$id)); }
    public function claim(string $id,string $fingerprint,int $expires): bool {
        return $this->writeExclusive($this->path('ledger',$id),json_encode(['state'=>'processing','fingerprint'=>$fingerprint,'expires'=>$expires]));
    }
    public function finish(string $id,string $fingerprint,string $state,int $expires): void { $this->atomic($this->path('ledger',$id),compact('state','fingerprint','expires')); }
    public function rate(string $subject,int $now,int $limit,int $window): bool {
        $bucket=(int)floor($now/$window);$id=hash('sha256',$subject.'|'.$bucket);
        for ($i=0;$i<$limit;$i++) if ($this->writeExclusive($this->root.'/rate/'.$id.'-'.$i.'.json',json_encode(['expires'=>($bucket+1)*$window]))) return true;
        return false;
    }
    public function quarantine(string $source): string {
        $dest=$this->root.'/quarantine/'.bin2hex(random_bytes(32)).'.pdf';
        $old=umask(0077);
        try { $in=fopen($source,'rb');$out=fopen($dest,'x+b'); }
        finally { umask($old); }
        if (!$in || !$out) { if(is_resource($in))fclose($in);if(is_resource($out))fclose($out);@unlink($dest);throw new \RuntimeException('quarantine'); }
        if (!chmod($dest,0600)) { fclose($in);fclose($out);@unlink($dest);throw new \RuntimeException('permissions'); }
        try { if (stream_copy_to_stream($in,$out)===false || !fflush($out)) throw new \RuntimeException('quarantine'); }
        catch (\Throwable $e) { @unlink($dest);throw $e; }
        finally { fclose($in);fclose($out); }
        return $dest;
    }
    public function reviewersValid(array $ids): bool {
        foreach ($ids as $id) if (!is_int($id) || $id<1 || !user_can($id,'arv_read_cv')) return false;
        return count($ids)>0;
    }
    public function saveCv(string $id,string $q,array $data,array $reviewers,int $created,int $expires,string $notice): void {
        $target=$this->path('cv',$id,'.pdf');
        if (is_file($this->path('tombstones',$id)) || is_file($target)) throw new \RuntimeException('exists');
        if (!rename($q,$target)) throw new \RuntimeException('store');
        try { $this->atomic($this->path('cv',$id),compact('data','reviewers','created','expires','notice')+['id'=>$id]); }
        catch (\Throwable $e) { @unlink($target);throw $e; }
    }
    public function authorize(string $id,int $user,bool $delete=false): ?array {
        $this->validId($id);
        if (!$user || !user_can($user,$delete?'arv_delete_cv':'arv_read_cv')) return null;
        if (is_file($this->path('tombstones',$id))) return null;
        $record=$this->read($this->path('cv',$id));
        if (!$record || !in_array($user,$record['reviewers']??[],true) || !is_file($this->path('cv',$id,'.pdf'))) return null;
        $file=$this->path('cv',$id,'.pdf');
        if (is_link($file) || realpath($file)!==$file || (fileperms($file)&0777)!==0600) return null;
        return $record;
    }
    public function downloadPath(string $id,int $user,int $now): ?string {
        $record=$this->authorize($id,$user);
        if (!$record || ($record['expires']??0)<=$now) return null;
        $this->log('download',$id,$user,$now);return $this->path('cv',$id,'.pdf');
    }
    public function delete(string $id,int $user,int $now): bool {
        if (!$this->authorize($id,$user,true)) return false;
        $this->erase($id,$now);$this->log('delete',$id,$user,$now);return true;
    }
    private function erase(string $id,int $now): void {
        // Tombstone precedes unlink. Restoring an old CV without its tombstone is prohibited.
        $this->atomic($this->path('tombstones',$id),['deleted'=>$now]);
        foreach (['.pdf','.json'] as $ext) { $p=$this->path('cv',$id,$ext); if (is_file($p) && !unlink($p)) throw new \RuntimeException('delete'); }
    }
    public function cleanup(int $now,int $logDays=0,int $tombstoneDays=0,int $temporaryTtl=0): int {
        $n=0;
        foreach (array_slice(glob($this->root.'/cv/*.json')?:[],0,500) as $p) {
            $id=basename($p,'.json');$r=$this->read($p);
            if ($r && (($r['expires']??PHP_INT_MAX)<=$now || is_file($this->path('tombstones',$id)))) { $this->erase($id,$now);$n++; }
        }
        foreach (['ledger','rate'] as $folder) foreach (array_slice(glob($this->root.'/'.$folder.'/*.json')?:[],0,500) as $p) {
            $r=$this->read($p);if ($r && ($r['expires']??PHP_INT_MAX)<=$now) unlink($p);
        }
        if ($temporaryTtl>0) {
            foreach (glob($this->root.'/quarantine/*.pdf')?:[] as $p) if (filemtime($p)+$temporaryTtl<$now) unlink($p);
            foreach (glob($this->root.'/cv/*.tmp')?:[] as $p) if (filemtime($p)+$temporaryTtl<$now) unlink($p);
            foreach (glob($this->root.'/cv/*.pdf')?:[] as $p) {
                $id=basename($p,'.pdf');
                if (!is_file($this->path('cv',$id)) && filemtime($p)+$temporaryTtl<$now) $this->erase($id,$now);
            }
        }
        if ($logDays>0) foreach (glob($this->root.'/logs/*.jsonl')?:[] as $p) {
            $time=strtotime(basename($p,'.jsonl').' UTC');if ($time && $time+$logDays*86400<$now) unlink($p);
        }
        // Only purge tombstones after the separately approved backup horizon.
        if ($tombstoneDays>0) foreach (glob($this->root.'/tombstones/*.json')?:[] as $p) {
            $r=$this->read($p);if ($r && ($r['deleted']??$now)+$tombstoneDays*86400<$now) unlink($p);
        }
        return $n;
    }
    public function records(int $user): array {
        $out=[];
        foreach (array_slice(glob($this->root.'/cv/*.json')?:[],0,500) as $p) { $id=basename($p,'.json');$r=$this->authorize($id,$user);if ($r) $out[]=['id'=>$id,'created'=>$r['created'],'expires'=>$r['expires']]; }
        return $out;
    }
    public function log(string $event,string $id,int $actor,int $now): void {
        if (!in_array($event,['accepted','rejected','unknown','stored','file_rejected','download','delete'],true)) return;
        $this->validId($id);
        $path=$this->root.'/logs/'.gmdate('Y-m-d',$now).'.jsonl';
        $old=umask(0077);try{$f=fopen($path,'ab');}finally{umask($old);}
        if (!$f) throw new \RuntimeException('log');
        if (!chmod($path,0600)) { fclose($f);throw new \RuntimeException('permissions'); }
        try { if (!flock($f,LOCK_EX)) throw new \RuntimeException('lock');fwrite($f,json_encode(['event'=>$event,'reference'=>$id,'actor'=>$actor,'utc'=>gmdate('c',$now)])."\n");fflush($f);flock($f,LOCK_UN); }
        finally { fclose($f); }
    }
}
