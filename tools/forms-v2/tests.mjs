import fs from 'node:fs';
import {execFileSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime,createNodeFsMountHandler} from '@php-wasm/node';

export async function runTests({php,handler,root,base,ids,cfg,plugin}) {
 const tests=[];
 const record=(name,pass,detail={})=>{tests.push({name,pass:!!pass,...detail});if(!pass)console.log('FAIL',name,detail);};
 const run=async code=>{const r=await php.run({code:'<?php require "/wordpress/wp-load.php";'+code});if(r.exitCode||r.errors)throw new Error(r.errors+r.text);return r.text;};
 const parsed=async code=>JSON.parse(await run(code));
 const req=async(url,{method='GET',body,jar={},accept='application/json',headers={}}={})=>{
   const hs={Accept:accept,...headers};if(Object.keys(jar).length)hs.Cookie=Object.entries(jar).map(([k,v])=>k+'='+v).join('; ');
   if(method==='POST' && body instanceof URLSearchParams){hs['Content-Type']='application/x-www-form-urlencoded';body=new TextEncoder().encode(body.toString());}
   const r=await handler.request({url:base+url,method,headers:hs,body});
   for(const c of r.headers['set-cookie']||[]){const pair=c.split(';')[0];const i=pair.indexOf('=');jar[pair.slice(0,i)]=pair.slice(i+1);}
   let data;try{data=JSON.parse(r.text);}catch{}return {status:r.httpStatusCode,data,text:r.text,headers:r.headers,bytes:r.bytes};
 };
 const token=async(kind,jar={})=>{const r=await req('/wp-admin/admin-post.php?action=arv_token&channel='+kind,{jar});if(r.status!==200)throw new Error('Token '+r.status+' '+r.text);return{token:r.data.token,jar};};
 const valid={name:'SYNTHETIC QA',company:'SYNTHETIC QA COMPANY',email:'qa@example.invalid',phone:'',service:'headhunting',message:'SYNTHETIC QA - NO REAL ENQUIRY',privacy:'1',website:''};
 const submit=async(raw,t,kind='business',files={})=>{
   const body=Object.keys(files).length?{...raw,csrf:t.token,...files}:new URLSearchParams({...raw,csrf:t.token});
   return req('/wp-admin/admin-post.php?action=arv_submit&channel='+kind,{method:'POST',jar:t.jar,headers:{Origin:base},body});
 };
 const attempts=async()=>Number(await run('echo (int)get_option("arv_mail_attempts",0);'));
 const resetRates=()=>run("foreach(glob(ARV_CONFIG['private_root'].'/rate/*.json')?:[] as $f){unlink($f);}");
 const probe=await parsed(`$out=[];foreach(glob('${plugin.replace(/'/g,"\\'")}')?:[] as $p){} echo json_encode(['php'=>PHP_VERSION,'wp'=>get_bloginfo('version')]);`);
 // Parse every PHP source with PHP's own parser.
 for(const file of ['ar-secure-forms.php','src/Core.php','src/PrivateStore.php','src/Pdf.php','src/Plugin.php']){
   const r=await parsed(`try{token_get_all(file_get_contents('/wordpress/wp-content/plugins/ar-secure-forms/${file}'),TOKEN_PARSE);echo json_encode(['ok'=>true]);}catch(Throwable $e){echo json_encode(['ok'=>false]);}`);record('syntax '+file,r.ok);
 }
 const inputCases=await parsed(`$v=${JSON.stringify(valid).replace(/:/g,':')};` .replace(/.*/s, `
 $v=json_decode('${JSON.stringify(valid)}',true);$out=[];
 foreach(['name','company','email','service','message'] as $k){$r=$v;unset($r[$k]);$out['missing_'.$k]=isset(\\ARV\\Input::validate('business',$r,true)['errors'][$k]);}
 foreach(['name','company','email','phone','service','message'] as $k){$r=$v;$r[$k]=['array'];$out['array_'.$k]=isset(\\ARV\\Input::validate('business',$r,true)['errors'][$k]);}
 foreach(['name'=>120,'company'=>160,'email'=>254,'phone'=>30,'service'=>40,'message'=>3000] as $k=>$limit){$r=$v;$r[$k]=str_repeat('x',$limit+1);$out['long_'.$k]=isset(\\ARV\\Input::validate('business',$r,true)['errors'][$k]);}
 foreach(['name','company','message'] as $k){$r=$v;$r[$k]='<b></b>';$out['empty_after_clean_'.$k]=isset(\\ARV\\Input::validate('business',$r,true)['errors'][$k]);}
 $r=$v;$r['email']="qa@example.invalid\\r\\nBcc: other@example.invalid";$out['header_injection']=isset(\\ARV\\Input::validate('business',$r,true)['errors']['email']);
 $r=$v;$r['service']='admin';$out['service_allowlist']=isset(\\ARV\\Input::validate('business',$r,true)['errors']['service']);
 $r=$v;$r['to']='external@example.invalid';$out['unknown_recipient_field']=isset(\\ARV\\Input::validate('business',$r,true)['errors']['form']);
 $r=$v;$r['website']='spam';$out['honeypot']=isset(\\ARV\\Input::validate('business',$r,true)['errors']['form']);
 $r=$v;$r['privacy']='0';$out['privacy_missing']=isset(\\ARV\\Input::validate('business',$r,true)['errors']['privacy']);
 $r=$v;$r['name']='<b>QA</b>';$out['sanitization']=\\ARV\\Input::validate('business',$r,true)['data']['name']==='QA';
 $r=$v;$r['phone']="12345\\n";$out['phone_format']=empty(\\ARV\\Input::validate('business',$r,true)['errors']);
 $out['mailbox_crlf']=!\\ARV\\Input::mailbox("qa@example.invalid\\r\\nBcc:other@example.invalid");
 echo json_encode($out);`));
 for(const[n,p]of Object.entries(inputCases))record('validation '+n,p);
 const utf=await parsed(`$v=json_decode('${JSON.stringify(valid)}',true);$v['name']=str_repeat('ñ',120);$a=\\ARV\\Input::validate('business',$v,true);$v['name'].='ñ';$b=\\ARV\\Input::validate('business',$v,true);echo json_encode(['at_limit'=>empty($a['errors']),'over_limit'=>isset($b['errors']['name'])]);`);record('UTF8 character length boundary',utf.at_limit&&utf.over_limit);
 const csrfCases=await parsed(`$now=100000;$clock=function()use(&$now){return $now;};$t=new \\ARV\\Tokens(str_repeat('k',64),$clock);$a=$t->session();$b=$t->session();$v=$t->issue('business',$a);$out=['valid'=>$t->verify('business',$a,$v)!==null,'cross_session'=>$t->verify('business',$b,$v)===null,'cross_channel'=>$t->verify('candidate',$a,$v)===null,'forged_cookie'=>$t->verify('business',str_repeat('f',129),$v)===null,'tampered'=>$t->verify('business',$a,$v.'x')===null];$now+=1801;$out['expired']=$t->verify('business',$a,$v)===null;echo json_encode($out);`);
 for(const[n,p]of Object.entries(csrfCases))record('csrf '+n,p);
 const t=await token('business');
 const wrong=await req('/wp-admin/admin-post.php?action=arv_submit&channel=business',{method:'POST',jar:t.jar,body:new URLSearchParams({...valid,csrf:t.token}),headers:{Origin:'https://untrusted.example.invalid'}});record('HTTP foreign origin',wrong.status===403);
 const noToken=await submit(valid,{token:'',jar:t.jar});record('HTTP missing CSRF',noToken.status===403);
 const newSession=await token('business');const cross=await submit(valid,{token:t.token,jar:newSession.jar});record('HTTP session mismatch',cross.status===403);
 const bad=await submit({...valid,name:''},t);record('HTTP invalid no mail',bad.status===422 && await attempts()===0);
 const spam=await submit({...valid,website:'spam'},t);record('HTTP spam no mail',spam.status===422 && await attempts()===0);
 const manip=await submit({...valid,to:'x@example.invalid'},t);record('HTTP manipulated fields',manip.status===422 && await attempts()===0);
 const htmlBad=await req('/wp-admin/admin-post.php?action=arv_submit&channel=business',{method:'POST',jar:t.jar,headers:{Origin:base},accept:'text/html',body:new URLSearchParams({...valid,name:'<b></b>',company:'<script>alert(1)</script>',csrf:t.token})});
 record('native HTML fallback escapes values and links errors',htmlBad.status===422&&htmlBad.text.includes('&lt;script&gt;alert(1)&lt;/script&gt;')&&htmlBad.text.includes('arv-business-name-error')&&!htmlBad.text.includes('value="<script>'));
 const ok=await submit(valid,t);record('HTTP valid simulated transport',ok.status===200 && ok.data.state==='accepted' && await attempts()===1);
 record('JSON has no PII',!JSON.stringify(ok.data).includes('qa@example.invalid') && !JSON.stringify(ok.data).includes(valid.company));
 const dup=await submit(valid,t);record('HTTP duplicate stable result',dup.data.reference===ok.data.reference && dup.data.duplicate===true && await attempts()===1);
 const conflict=await submit({...valid,message:'Other SYNTHETIC message'},t);record('HTTP token payload conflict',conflict.status===409 && await attempts()===1);
 const concurrent=await Promise.all(Array.from({length:8},()=>submit(valid,t)));record('HTTP concurrent duplicate attempts',concurrent.every(r=>r.status===200)&&await attempts()===1,{note:'HTTP handler serializes PHP; independent filesystem race tested below.'});
 await resetRates();
 for(const mode of ['rejected','timeout']){
   await run(`update_option('arv_lab_mode','${mode}');`);const before=await attempts();const k=await token('business');const r=await submit(valid,k);const again=await submit(valid,k);
   record('transport '+mode,r.status===(mode==='rejected'?502:503)&&again.data.duplicate===true&&await attempts()===before+1);
 }
 await run("update_option('arv_lab_mode','accepted');");await resetRates();
 const states=[];for(let i=0;i<6;i++){const k=await token('business');const r=await submit(valid,k);states.push(r.status);}
 record('fixed rate limit 5 then 429',states.slice(0,5).every(n=>n===200)&&states[5]===429,{statuses:states});await resetRates();
 const gates=await parsed(`$original=ARV_CONFIG;$original['origin']='${base}';$out=[];$clock=function(){return time();};$store=new \\ARV\\PrivateStore($original['private_root'],[ABSPATH]);$tokens=new \\ARV\\Tokens(wp_salt('auth'),$clock);$parser=new \\ARV\\ProcessInspector($original['python'],'/wordpress/wp-content/plugins/ar-secure-forms/bin/pdf_inspect.py',5242880);$positive=new \\ARV\\Flow($original,$store,$tokens,new \\ARVLocalMail,$parser,new \\ARVLocalScanner,$clock,wp_salt('auth'));$out['positive_business']=$positive->enabled('business');$out['positive_candidate']=$positive->enabled('candidate');foreach(['enabled','legal_approved','security_verified','transport_enabled','recipient_confirmed','sender_authenticated'] as $flag){$c=$original;$c['business'][$flag]=false;$f=new \\ARV\\Flow($c,$store,$tokens,new \\ARVLocalMail,$parser,new \\ARVLocalScanner,$clock,wp_salt('auth'));$out[$flag]=!$f->enabled('business');}foreach(['enabled','legal_approved','storage_verified','scanner_verified','retention_approved','backup_policy_confirmed'] as $flag){$c=$original;$c['candidate'][$flag]=false;$f=new \\ARV\\Flow($c,$store,$tokens,new \\ARVLocalMail,$parser,new \\ARVLocalScanner,$clock,wp_salt('auth'));$out['cv_'.$flag]=!$f->enabled('candidate');}$c=\\ARV\\Plugin::defaults();$c['origin']='${base}';$f=new \\ARV\\Flow($c,$store,$tokens,new \\ARVLocalMail,$parser,new \\ARVLocalScanner,$clock,wp_salt('auth'));$out['defaults_disabled']=!$f->enabled('business')&&!$f->enabled('candidate');echo json_encode($out);`);
 for(const[n,p]of Object.entries(gates))record('activation gate '+n,p);
 execFileSync(process.env.ARV_PYTHON||'/usr/bin/python3',[fileURLToPath(new URL('./fixtures.py',import.meta.url)),root+'/fixtures'],{env:{PATH:'/usr/bin:/bin',PYTHONPATH:process.env.ARV_PYTHONPATH||''}});
 const parserResults={};
 for(const name of ['valid.pdf','renamed.pdf','false-signature.pdf','truncated.pdf','empty.pdf','encrypted.pdf','active.pdf','attachment.pdf','mime-mismatch.pdf']){
   const out=await parsed(`$p=new \\ARV\\ProcessInspector('${process.env.ARV_PYTHON||'/usr/bin/python3'}','/wordpress/wp-content/plugins/ar-secure-forms/bin/pdf_inspect.py',5242880);echo json_encode(['ok'=>$p->inspect('${root}/fixtures/${name}')]);`);
   parserResults[name]=out.ok;record('structural parser '+name,name==='valid.pdf'?out.ok:!out.ok);
 }
 fs.writeFileSync(root+'/evidence/parser-'+probe.php+'.json',JSON.stringify(parserResults,null,2));
 const cvbase={name:'SYNTHETIC QA',email:'qa-cv@example.invalid',phone:'',area:'SYNTHETIC',privacy:'1',website:''};let recordId;
 for(const name of ['valid.pdf','renamed.pdf','false-signature.pdf','truncated.pdf','empty.pdf','too-large.pdf','mime-mismatch.pdf','encrypted.pdf','active.pdf','attachment.pdf']){
   await resetRates();const k=await token('candidate');const body={...cvbase,csrf:k.token,cv:new File([fs.readFileSync(root+'/fixtures/'+name)],name,{type:'application/pdf'})};
   const r=await req('/wp-admin/admin-post.php?action=arv_submit&channel=candidate',{method:'POST',jar:k.jar,headers:{Origin:base},body});
   record('HTTP upload '+name,name==='valid.pdf'?r.status===200&&r.data?.state==='stored':r.status===422,{status:r.status,state:r.data?.state});if(name==='valid.pdf')recordId=r.data?.reference;
 }
 const errors=await parsed(`echo json_encode(['incomplete'=>\\ARV\\PdfUpload::basic(['name'=>'qa.pdf','error'=>UPLOAD_ERR_PARTIAL,'size'=>10,'tmp_name'=>''],5242880)!=='','array_name'=>\\ARV\\PdfUpload::basic(['name'=>[],'error'=>0,'size'=>10,'tmp_name'=>''],5242880)!=='','string_size'=>\\ARV\\PdfUpload::basic(['name'=>'qa.pdf','error'=>0,'size'=>'10','tmp_name'=>''],5242880)!=='']);`);
 for(const[n,p]of Object.entries(errors))record('upload '+n,p);
 await run("update_option('arv_lab_scan','error');");await resetRates();const av=await token('candidate');const avResult=await req('/wp-admin/admin-post.php?action=arv_submit&channel=candidate',{method:'POST',jar:av.jar,headers:{Origin:base},body:{...cvbase,csrf:av.token,cv:new File([fs.readFileSync(root+'/fixtures/valid.pdf')],'qa.pdf',{type:'application/pdf'})}});record('scanner unavailable fails closed',avResult.status===422&&avResult.data?.state==='file_rejected');await run("update_option('arv_lab_scan','clean');");
 const verdict=await parsed(`echo json_encode(['clean'=>\\ARV\\ClamdScanner::verdict("stream: OK\\0"),'infected'=>!\\ARV\\ClamdScanner::verdict("stream: Synthetic.Test FOUND\\0"),'error'=>!\\ARV\\ClamdScanner::verdict("stream: scan error ERROR\\0"),'empty'=>!\\ARV\\ClamdScanner::verdict('')]);`);for(const[n,p]of Object.entries(verdict))record('clamd response '+n,p);
 record('valid CV stored',!!recordId && fs.existsSync(root+'/store/cv/'+recordId+'.pdf'));
 if(recordId){
   const mode=fs.statSync(root+'/store/cv/'+recordId+'.pdf').mode&0o777;record('CV file 0600',mode===0o600,{mode:mode.toString(8)});record('private root 0700',(fs.statSync(root+'/store').mode&0o777)===0o700);
   const auth=async(user,op='download',id=recordId)=>parsed(`$id=${user};$token=WP_Session_Tokens::get_instance($id)->create(time()+3600);$logged=wp_generate_auth_cookie($id,time()+3600,'logged_in',$token);$auth=wp_generate_auth_cookie($id,time()+3600,'auth',$token);$_COOKIE[LOGGED_IN_COOKIE]=$logged;wp_set_current_user($id);echo json_encode(['jar'=>[LOGGED_IN_COOKIE=>$logged,AUTH_COOKIE=>$auth],'nonce'=>wp_create_nonce('arv_${op}_${id}')]);`);
   const down=async(a,id=recordId,op='download',nonce=a?.nonce)=>req('/wp-admin/admin-post.php?action=arv_cv_'+op,{method:'POST',jar:a?.jar||{},body:new URLSearchParams({id,_wpnonce:nonce||''}),accept:'application/pdf'});
   const anon=await down(null);record('download anonymous denied',anon.status===403);
   const noperm=await auth(ids.users.no_permission);const np=await down(noperm);record('download no capability denied',np.status===403);
   const outsider=await auth(ids.users.outsider);const other=await down(outsider);record('download capability but wrong ACL denied',other.status===404);
   const owner=await auth(ids.users.reviewer);const dl=await down(owner);record('authorized download actual PDF',dl.status===200&&Buffer.from(dl.bytes).subarray(0,5).toString()==='%PDF-');record('private download headers',(dl.headers['cache-control']||[]).join().includes('no-store')&&(dl.headers['x-content-type-options']||[]).includes('nosniff'));
   const repeat=await down(owner);record('authorized repeat download',repeat.status===200);
   const nonceBad=await down(owner,recordId,'download','invalid');record('download invalid nonce denied',nonceBad.status===403);
   const path=await down(owner,'../../wp-config.php');record('download traversal denied',path.status===403);
   const missingId='f'.repeat(64);const missingAuth=await auth(ids.users.reviewer,'download',missingId);const missing=await down(missingAuth,missingId);record('missing record denied',missing.status===404);
   php.mkdirTree('/wordpress/wp-content/uploads');
   php.writeFile('/wordpress/wp-content/uploads/arv-public-synthetic-control.pdf',fs.readFileSync(root+'/fixtures/valid.pdf'));
   const control=await req('/wp-content/uploads/arv-public-synthetic-control.pdf',{accept:'application/pdf'});
   record('positive control static public PDF would be served',control.status===200&&Buffer.from(control.bytes).subarray(0,5).toString()==='%PDF-');
   php.unlink('/wordpress/wp-content/uploads/arv-public-synthetic-control.pdf');
   for(const url of ['/wp-content/uploads/'+recordId+'.pdf','/store/cv/'+recordId+'.pdf',root+'/store/cv/'+recordId+'.pdf','/wp-json/arv/v1/cv/'+recordId]){
     let r=await req(url,{accept:'text/html'});const chain=[r.status];
     if([301,302].includes(r.status) && r.headers.location?.[0]?.startsWith(base+'/')){r=await req(r.headers.location[0].slice(base.length),{accept:'text/html'});chain.push(r.status);}
     record('no direct public CV '+url,r.status===404&&!Buffer.from(r.bytes).subarray(0,5).toString().includes('%PDF-'),{statuses:chain});
   }
   const media=await req('/wp-json/wp/v2/media?mime_type=application/pdf');record('no CV in REST media',media.status===200&&Array.isArray(media.data)&&media.data.length===0);
   const permissions=fs.statSync(root+'/store/cv/'+recordId+'.pdf').mode;await run(`chmod(ARV_CONFIG['private_root'].'/cv/${recordId}.pdf',0644);`);const unsafe=await down(owner);record('unsafe file permissions deny download',unsafe.status===404);await run(`chmod(ARV_CONFIG['private_root'].'/cv/${recordId}.pdf',0600);`);
   const backup=root+'/backup-synthetic';fs.mkdirSync(backup,{recursive:true,mode:0o700});for(const ext of ['pdf','json'])fs.copyFileSync(root+'/store/cv/'+recordId+'.'+ext,backup+'/'+recordId+'.'+ext);
   const removeAuth=await auth(ids.users.reviewer,'delete');const del=await down(removeAuth,recordId,'delete');record('authorized deletion',del.status===302&&!fs.existsSync(root+'/store/cv/'+recordId+'.pdf')&&!fs.existsSync(root+'/store/cv/'+recordId+'.json'));
   for(const ext of ['pdf','json'])await run(`copy('${backup}/${recordId}.${ext}',ARV_CONFIG['private_root'].'/cv/${recordId}.${ext}');chmod(ARV_CONFIG['private_root'].'/cv/${recordId}.${ext}',0600);`);
   const resurrected=await down(owner);record('backup restore tombstone denies access',resurrected.status===404);
   await run(`$s=new \\ARV\\PrivateStore(ARV_CONFIG['private_root'],[ABSPATH]);$s->initialize();$s->cleanup(time());`);record('restore reconciliation removes deleted CV',!fs.existsSync(root+'/store/cv/'+recordId+'.pdf'));
   fs.rmSync(backup,{recursive:true});
 }
 const storeTests=await parsed(`$s=new \\ARV\\PrivateStore(ARV_CONFIG['private_root'],[ABSPATH]);$s->initialize();$out=[];try{(new \\ARV\\PrivateStore(ABSPATH.'uploads-private',[ABSPATH]))->initialize();$out['webroot_refused']=false;}catch(Throwable $e){$out['webroot_refused']=true;}$id=bin2hex(random_bytes(32));$q=$s->quarantine('${root}/fixtures/valid.pdf');$s->saveCv($id,$q,['name'=>'SYNTHETIC'],[${ids.users.reviewer}],time()-100,time()-1,'QA');$out['expired_denied']=$s->downloadPath($id,${ids.users.reviewer},time())===null;$s->cleanup(time());$out['retention_deleted']=!file_exists(ARV_CONFIG['private_root'].'/cv/'.$id.'.pdf');echo json_encode($out);`);for(const[n,p]of Object.entries(storeTests))record('storage '+n,p);
 const db=await parsed(`global $wpdb;echo json_encode(['media'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type='application/pdf'"),'unexpected_mail'=>(int)get_option('arv_unexpected_mail',0),'old_business'=>ar_f9_guard('POST',true,0)]);`);record('no public PDF attachment',db.media===0);record('no real wp_mail call',db.unexpected_mail===0);record('RC2 legacy hard stop intact',db.old_business===503);
 const logs=fs.readdirSync(root+'/store/logs').map(f=>fs.readFileSync(root+'/store/logs/'+f,'utf8')).join('');record('minimal logs no PII',!logs.includes('qa@example.invalid')&&!logs.includes('SYNTHETIC QA COMPANY')&&!logs.includes('qa-cv@example.invalid'));
 const {exclusiveRace}=await import('./race.mjs');
 const race=await exclusiveRace({root,plugin,version:probe.php.startsWith('8.0')?'8.0':'8.3'});
 record('atomic claim across six independent PHP instances',race.winners===1,{winners:race.winners});
 record('atomic rate slots across six independent PHP instances',race.rateWinners===3,{allowed:race.rateWinners});
 // Roll back the plugin only; preserve the RC2 theme and stored page bodies.
 const before=await parsed(`echo json_encode(['theme'=>wp_get_theme()->get('Version'),'pages'=>array_map(function($id){return hash('sha256',get_post_field('post_content',$id));},ARV_CONFIG['page_ids'])]);`);
 await run(`require_once ABSPATH.'wp-admin/includes/plugin.php';deactivate_plugins('ar-secure-forms/ar-secure-forms.php');`);
 const closed=await req('/contacto/',{accept:'text/html'});record('rollback restores original disabled interface',closed.status===200&&!closed.text.includes('data-arv-form')&&closed.text.includes('Demostración deshabilitada'));
 const after=await parsed(`echo json_encode(['theme'=>wp_get_theme()->get('Version'),'pages'=>array_map(function($id){return hash('sha256',get_post_field('post_content',$id));},ARV_CONFIG['page_ids'])]);`);record('rollback theme and editorial hashes unchanged',JSON.stringify(before)===JSON.stringify(after));
 await run(`require_once ABSPATH.'wp-admin/includes/plugin.php';activate_plugin('ar-secure-forms/ar-secure-forms.php');`);
 const out={environment:probe,tests,pass:tests.filter(t=>t.pass).length,fail:tests.filter(t=>!t.pass).length,limits:['PHP WASM/SQLite, not native hosting.','Scanner clean/error is synthetic; no actual malware engine certified.','Concurrent HTTP requests are queued by one PHP runtime; filesystem race uses independent runtimes.'],mail_calls:db.unexpected_mail};
 fs.writeFileSync(root+'/evidence/tests-php'+probe.php+'.json',JSON.stringify(out,null,2));
 console.log('TESTS',probe,'PASS',out.pass,'FAIL',out.fail);
 if(out.fail)console.log(tests.filter(t=>!t.pass));
}
