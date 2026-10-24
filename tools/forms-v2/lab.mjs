import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import http from 'node:http';
import {spawn} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {bootWordPressAndRequestHandler,getFileNotFoundActionForWordPress} from '@wp-playground/wordpress';
import {loadNodeRuntime,createNodeFsMountHandler} from '@php-wasm/node';

const source=fileURLToPath(new URL('../../',import.meta.url));
const output=process.env.ARV_LAB_DIR || fs.mkdtempSync(path.join(os.tmpdir(),'arv-lab-'));
fs.mkdirSync(output,{recursive:true,mode:0o700});fs.chmodSync(output,0o700);
const root=fs.realpathSync(output);
for(const dir of ['evidence','fixtures'])fs.mkdirSync(root+'/'+dir,{recursive:true,mode:0o700});
const phpVersion=process.env.QA_PHP || '8.3';
const wpVersion=process.env.QA_WP || '7.1.3';
const port=Number(process.env.ARV_PORT || 9420);
const base=`http://127.0.0.1:${port}`;
const python=process.env.ARV_PYTHON || '/usr/bin/python3';
const pythonPath=process.env.ARV_PYTHONPATH || '';
const plugin=source+'/wordpress/plugins/ar-secure-forms';
const nativeWorker=plugin+'/bin/pdf_inspect.py';
const zip=path.join(os.homedir(),'.wordpress-playground',wpVersion+'.zip');
if (!fs.existsSync(zip)) throw new Error('Provide an official cached WordPress ZIP: '+zip);
let php;
const handler=await bootWordPressAndRequestHandler({
  siteUrl:base,documentRoot:'/wordpress',phpVersion,
  createPhpRuntime:()=>loadNodeRuntime(phpVersion,{emscriptenOptions:{processId:1}}),
  wordPressZip:new File([fs.readFileSync(zip)],'wordpress.zip'),
  sqliteIntegrationPluginZip:new File([fs.readFileSync(fileURLToPath(import.meta.resolve('@wp-playground/cli')).replace(/index\.js$/,'sqlite-database-integration.zip'))],'sqlite.zip'),
  constants:{WP_ENVIRONMENT_TYPE:'local',WP_DEBUG:true,WP_DEBUG_DISPLAY:false,DISABLE_WP_CRON:true,AUTOMATIC_UPDATER_DISABLED:true},
  phpIniEntries:{display_errors:'0',log_errors:'0',upload_max_filesize:'6M',post_max_size:'7M'},
  cookieStore:false,maxPhpInstances:1,
  // Only nonexistent static PDF paths get this response. Existing files would
  // still be served, so an accidental public upload is not hidden by this rule.
  getFileNotFoundAction:relative=>/\.pdf\/?$/i.test(relative)?{type:'404'}:getFileNotFoundActionForWordPress(relative),
  spawnHandler:()=>((command,args)=>{
    // The production adapter uses proc_open argv. In WASM map only this trusted
    // parser path to its native copy. No shell or arbitrary process is permitted.
    if(command!==python || args[0]!=='/wordpress/wp-content/plugins/ar-secure-forms/bin/pdf_inspect.py') throw new Error('Blocked subprocess');
    return spawn(python,[nativeWorker,...args.slice(1)],{env:{PATH:'/usr/bin:/bin',PYTHONPATH:pythonPath},stdio:['pipe','pipe','pipe']});
  }),
  onPHPInstanceCreated:async instance=>{php=instance;},
  hooks:{beforeDatabaseSetup:async p=>{
    p.mkdirTree(root);await p.mount(root,createNodeFsMountHandler(root));
    await p.mount('/wordpress/wp-content/themes/ar-editorial',createNodeFsMountHandler(source+'/wordpress/ar-editorial'));
    await p.mount('/wordpress/wp-content/plugins/ar-secure-forms',createNodeFsMountHandler(plugin));
    p.mkdirTree('/wordpress/wp-content/mu-plugins');
    p.writeFile('/wordpress/wp-content/mu-plugins/000-no-egress.php',`<?php
add_filter('pre_wp_mail',function(){update_option('arv_unexpected_mail',(int)get_option('arv_unexpected_mail',0)+1,false);return false;},PHP_INT_MAX);
add_filter('pre_http_request',function(){return new WP_Error('lab_no_egress','Lab outbound HTTP blocked');},PHP_INT_MAX);
add_filter('pre_option_blog_public',function(){return '0';});
`);
  }}
});
const configPath=root+'/runtime-config.php';
const setup=`<?php
require '/wordpress/wp-load.php';require_once ABSPATH.'wp-admin/includes/plugin.php';
switch_theme('ar-editorial');update_option('permalink_structure','/%postname%/');global $wp_rewrite;$wp_rewrite->init();$wp_rewrite->flush_rules(false);
wp_set_current_user(1); // Preserve approved custom HTML/CSS in the local fixture.
kses_remove_filters(); // The installer had initialized filters as an anonymous user.
$users=[];foreach(['reviewer','outsider','no_permission'] as $login){$id=wp_create_user('qa_'.$login,wp_generate_password(40),'qa-'.$login.'@example.invalid');$u=get_user_by('id',$id);if($login!=='no_permission'){$u->add_cap('arv_read_cv');$u->add_cap('arv_delete_cv');}$users[$login]=$id;}
$contact=file_get_contents('${root}/contact-content.html');$candidate=file_get_contents('${root}/candidate-content.html');
$pages=[];foreach(['business'=>['contacto','Hablemos de la contratación que necesita tu empresa','page-contact.php',$contact],'candidate'=>['candidatos','Comparte tu perfil profesional con American Recruiters','page-candidates.php',$candidate]] as $k=>$v){$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$v[0],'post_title'=>$v[1],'post_content'=>$v[3]]);update_post_meta($id,'_wp_page_template',$v[2]);$pages[$k]=$id;}
$privacy=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>'privacidad','post_title'=>'Privacidad — fixture local','post_content'=>'Documento ficticio de QA; no es un aviso aprobado.']);
echo json_encode(['users'=>$users,'pages'=>$pages]);`;
if(!fs.existsSync(root+'/contact-content.html'))fs.writeFileSync(root+'/contact-content.html','<!-- wp:html --><section class="ar-f9">Demostración deshabilitada.</section><!-- /wp:html -->');
if(!fs.existsSync(root+'/candidate-content.html'))fs.writeFileSync(root+'/candidate-content.html','<!-- wp:html --><section class="ar-f9">Demostración deshabilitada.</section><!-- /wp:html -->');
let response=await php.run({code:setup});
if(response.exitCode || response.errors)throw new Error(response.errors+response.text);
const ids=JSON.parse(response.text);
const bootMail=await php.run({code:`<?php require '/wordpress/wp-load.php';echo (int)get_option('arv_unexpected_mail',0);update_option('arv_unexpected_mail',0,false);`});
fs.writeFileSync(root+'/bootstrap-mail-blocked.json',JSON.stringify({blockedDuringWordPressInstall:Number(bootMail.text),realMessagesSent:0}));
const cfg={private_root:root+'/store',webroots:['/wordpress'],ledger_ttl:86400,rate_limit:5,rate_window:600,log_retention_days:7,tombstone_days:35,temporary_ttl:3600,python,replace_demo:true,page_ids:ids.pages,cv_access_enabled:true,
 business:{enabled:true,legal_approved:true,security_verified:true,transport_enabled:true,recipient_confirmed:true,sender_authenticated:true,to:'qa-inbox@example.invalid',from:'qa-sender@example.invalid',subject:'LOCAL SYNTHETIC QA',notice_version:'LOCAL-QA-NOT-LEGAL',notice_url:base+'/privacidad/',privacy_text:'Información ficticia, únicamente para QA local.',ack_mode:'acknowledgement',ack_text:'Confirmación ficticia para esta prueba aislada.'},
 candidate:{enabled:true,legal_approved:true,security_verified:true,storage_verified:true,scanner_verified:true,retention_approved:true,backup_policy_confirmed:true,retention_days:1,max_bytes:5*1024*1024,reviewers:[ids.users.reviewer],notice_version:'LOCAL-QA-NOT-LEGAL',notice_url:base+'/privacidad/',privacy_text:'Información ficticia, únicamente para QA local.',ack_mode:'acknowledgement',ack_text:'Confirmación ficticia para esta prueba aislada.'}};
const runtimeCfg=process.env.ARV_DEFAULTS==='1'?{}:cfg;
fs.writeFileSync(configPath,'<?php define("ARV_LAB",true);define("ARV_CONFIG",json_decode(base64_decode("'+Buffer.from(JSON.stringify(runtimeCfg)).toString('base64')+'"),true));',{mode:0o600});
php.writeFile('/wordpress/wp-content/mu-plugins/001-local-fixtures.php',`<?php
require ${JSON.stringify(configPath)};
require_once '/wordpress/wp-content/plugins/ar-secure-forms/src/Core.php';
class ARVLocalMail implements \\ARV\\Transport {public function send(array $m):string{$count=(int)get_option('arv_mail_attempts',0)+1;update_option('arv_mail_attempts',$count,false);$mode=get_option('arv_lab_mode','accepted');if($mode==='timeout')throw new RuntimeException('SIMULATED timeout');return $mode;}}
class ARVLocalScanner implements \\ARV\\Scanner {public function clean(string $path):bool{return get_option('arv_lab_scan','clean')==='clean';}}
add_filter('arv_lab_transport',function(){return new ARVLocalMail;});add_filter('arv_lab_scanner',function(){return new ARVLocalScanner;});
`);
response=await php.run({code:`<?php require '/wordpress/wp-load.php';require_once ABSPATH.'wp-admin/includes/plugin.php';activate_plugin('ar-secure-forms/ar-secure-forms.php');echo json_encode(['active'=>is_plugin_active('ar-secure-forms/ar-secure-forms.php'),'php'=>PHP_VERSION]);`});
console.log('Lab',base, response.text,response.errors);
fs.writeFileSync(root+'/lab.json',JSON.stringify({base,phpVersion,wpVersion,ids,source,root,cfg},null,2),{mode:0o600});

const server=http.createServer(async(req,res)=>{
  if(!['127.0.0.1','::1'].includes(req.socket.remoteAddress)){res.writeHead(403);res.end();return;}
  try {
    let length=0;const chunks=[];for await(const chunk of req){length+=chunk.length;if(length>8*1024*1024){res.writeHead(413);res.end();return;}chunks.push(chunk);}
    const h=Object.fromEntries(Object.entries(req.headers).map(([k,v])=>[k,Array.isArray(v)?v.join(','):v||'']));
    const out=await handler.request({url:base+req.url,method:req.method,headers:h,body:chunks.length?new Uint8Array(Buffer.concat(chunks)):undefined});
    for(const [k,v]of Object.entries(out.headers))res.setHeader(k,v);
    res.statusCode=out.httpStatusCode;res.end(Buffer.from(out.bytes));
  }catch(e){res.writeHead(500,{'Content-Type':'text/plain'});res.end('Lab request failed');console.error(String(e));}
});
await new Promise(resolve=>server.listen(port,'127.0.0.1',resolve));
// A live same-origin iframe gives each page a real CSS viewport when the
// browser-level viewport override is unavailable. It is not a static snapshot.
php.mkdirTree('/wordpress/qa');
php.writeFile('/wordpress/qa/view.php',`<?php $w=(int)($_GET['w']??390);if(!in_array($w,[360,390,768,1024,1440],true))$w=390;$p=($_GET['p']??'contacto')==='candidatos'?'candidatos':'contacto';?><!doctype html><html><meta charset="utf-8"><title>LIVE WordPress QA</title><style>body{margin:0;background:#eee;font:14px system-ui}header{padding:12px}iframe{display:block;border:0;background:white;margin:0 20px;width:<?php echo $w;?>px;height:900px}</style><header><strong>Live local WordPress iframe — simulation only</strong><form><label>Width <select name="w"><?php foreach([360,390,768,1024,1440] as $s)echo '<option '.($s===$w?'selected':'').'>'.$s.'</option>';?></select></label><label> Page <select name="p"><option value="contacto" <?php if($p==='contacto')echo 'selected';?>>Contacto</option><option value="candidatos" <?php if($p==='candidatos')echo 'selected';?>>Candidatos</option></select></label><button>Open</button></form><p id="dimensions">Loading…</p></header><iframe title="Live form" src="/<?php echo $p;?>/"></iframe><script>const f=document.querySelector('iframe');function measure(){try{const w=f.contentWindow,d=f.contentDocument;document.querySelector('#dimensions').textContent='Child window.innerWidth='+w.innerWidth+'; document width='+d.documentElement.scrollWidth+'; URL='+w.location.href}catch(e){}}f.addEventListener('load',measure);setInterval(measure,500);</script></html>`);
fs.writeFileSync(root+'/ready','ready');
if(process.env.ARV_RUN_TESTS==='1'){
 const {runTests}=await import('./tests.mjs');
 await runTests({php,handler,root,base,ids,cfg,plugin});
}
if(process.env.ARV_DEFAULTS==='1'){
 const rows=[];
 for(const channel of ['business','candidate'])for(const method of ['GET','POST']){
  const action=method==='GET'?'arv_token':'arv_submit';
  const r=await handler.request({url:base+'/wp-admin/admin-post.php?action='+action+'&channel='+channel,method,headers:{Accept:'application/json',Origin:base},body:method==='POST'?new TextEncoder().encode('name=SYNTHETIC'):undefined});
  rows.push({channel,method,status:r.httpStatusCode,disabled:JSON.parse(r.text).state==='disabled'});
 }
 const info=await php.run({code:`<?php require '/wordpress/wp-load.php';echo json_encode(['admin_capability_added'=>user_can(1,'arv_read_cv'),'unexpected_mail'=>(int)get_option('arv_unexpected_mail',0)]);`});
 fs.writeFileSync(root+'/evidence/defaults-'+phpVersion+'.json',JSON.stringify({rows,storeCreated:fs.existsSync(root+'/store'),info:JSON.parse(info.text)},null,2));
 console.log('DEFAULTS',rows);
}
console.log('READY',base);
if(process.env.ARV_EXIT==='1'){server.close();await handler[Symbol.asyncDispose]?.();process.exit(0);}
