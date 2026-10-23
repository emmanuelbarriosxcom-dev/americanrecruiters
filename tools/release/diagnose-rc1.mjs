import {runCLI} from '@wp-playground/cli';import fs from 'node:fs';import http from 'node:http';
import {fileURLToPath} from 'node:url';
const root=fileURLToPath(new URL('../../',import.meta.url)).replace(/\/$/,'');
const priv=process.env.AR_PRIVATE_BACKUP_DIR;
if(!priv)throw new Error('AR_PRIVATE_BACKUP_DIR is required; use isolated RC1 fixture sources');
const srv=await runCLI({command:'server',wp:'7.1.3',php:'8.3',port:9413,workers:1,mount:[{hostPath:priv+'/rc1-source/ar-editorial',vfsPath:'/wordpress/wp-content/themes/ar-editorial'}],blueprint:{plugins:['https://downloads.wordpress.org/plugin/wordpress-seo.24.2.zip']},verbosity:'quiet'});
await new Promise(r=>srv.server.close(r));await new Promise(r=>srv.server.listen(9413,'127.0.0.1',r));const pg=srv.playground;
await pg.run({code:`<?php require '/wordpress/wp-load.php';update_option('ar_qa_pages_before',(int)wp_count_posts('page')->publish);switch_theme('ar-editorial');`});
await pg.run({code:fs.readFileSync(root+'/release/archive/rc1/integration-original.php','utf8')});
const dirs=await pg.run({code:`<?php require '/wordpress/wp-load.php';echo WPMU_PLUGIN_DIR;`});const mu=new TextDecoder().decode(dirs.bytes);await pg.mkdirTree(mu);await pg.writeFile(mu+'/qa-trace.php',`<?php add_action('muplugins_loaded',function(){header('X-AR-Request: '.base64_encode(json_encode(['uri'=>$_SERVER['REQUEST_URI']??'','get'=>$_GET,'method'=>$_SERVER['REQUEST_METHOD']??''])));}); add_filter('wp_redirect',function($url,$status){header('X-AR-Redirect: '.base64_encode(json_encode(['url'=>$url,'status'=>$status,'trace'=>wp_debug_backtrace_summary()])));return $url;},999,2);`);
const rows=[];
for(const path of ['/','/zzzz-release-not-found-847692/','/wp-admin/admin-post.php?action=ar_f9_candidate','/?p=999999']){
 for(const kind of ['direct','fetch','http']){
  if(kind==='direct'){const r=await pg.request({url:'http://127.0.0.1:9413'+path,method:'GET'}); rows.push({path,kind,status:r.httpStatusCode,headers:r.headers});}
  if(kind==='fetch'){const r=await fetch('http://127.0.0.1:9413'+path,{redirect:'manual'});rows.push({path,kind,status:r.status,headers:Object.fromEntries(r.headers)});await r.arrayBuffer();}
  if(kind==='http'){rows.push(await new Promise((resolve,reject)=>{http.get('http://127.0.0.1:9413'+path,r=>{r.resume();r.on('end',()=>resolve({path,kind,status:r.statusCode,headers:r.headers}));}).on('error',reject);}));}
 }
}
fs.writeFileSync(priv+'/trace-rc1-fixture.json',JSON.stringify(rows,null,2));console.log(rows.map(x=>({path:x.path,kind:x.kind,status:x.status,location:x.headers.location})));
const controls=[];
const resetSameTheme=await pg.run({code:`<?php require '/wordpress/wp-load.php';global $wp_rewrite;$before=hash('sha256',serialize(get_option('rewrite_rules')));$wp_rewrite->init();$wp_rewrite->flush_rules(false);echo json_encode(['before'=>$before,'after'=>hash('sha256',serialize(get_option('rewrite_rules')))]);`});
fs.writeFileSync(priv+'/rewrite-same-theme.json',new TextDecoder().decode(resetSameTheme.bytes));
const sameTheme=await pg.request({url:'http://127.0.0.1:9413/zzzz-release-not-found-847692/',method:'GET'});controls.push({stage:'rc1-same-theme-after-correct-initialization',status:sameTheme.httpStatusCode,headers:sameTheme.headers});
await pg.run({code:`<?php require '/wordpress/wp-load.php'; switch_theme('twentytwentyfive');`});
let ctrl=await pg.request({url:'http://127.0.0.1:9413/zzzz-release-not-found-847692/',method:'GET'});controls.push({stage:'stock-theme-before-rewrite-reset',status:ctrl.httpStatusCode,headers:ctrl.headers});
let reset=await pg.run({code:`<?php require '/wordpress/wp-load.php'; global $wp_rewrite; $before=hash('sha256',serialize(get_option('rewrite_rules')));$wp_rewrite->init();$wp_rewrite->flush_rules(false);echo json_encode(['rules_before'=>$before,'rules_after'=>hash('sha256',serialize(get_option('rewrite_rules'))),'structure'=>$wp_rewrite->permalink_structure]);`});
fs.writeFileSync(priv+'/rewrite-reset.json',new TextDecoder().decode(reset.bytes));
ctrl=await pg.request({url:'http://127.0.0.1:9413/zzzz-release-not-found-847692/',method:'GET'});controls.push({stage:'stock-theme-after-rewrite-reset',status:ctrl.httpStatusCode,headers:ctrl.headers});
await pg.run({code:`<?php require '/wordpress/wp-load.php';switch_theme('ar-editorial');`});
for(const path of ['/zzzz-release-not-found-847692/','/wp-admin/admin-post.php?action=ar_f9_candidate']){ctrl=await pg.request({url:'http://127.0.0.1:9413'+path,method:'GET'});controls.push({stage:'rc1-after-rewrite-reset',path,status:ctrl.httpStatusCode,headers:ctrl.headers});}
fs.writeFileSync(priv+'/diagnosis-controls.json',JSON.stringify(controls,null,2));console.log(controls.map(x=>({stage:x.stage,path:x.path,status:x.status})));console.log('RC1 fixture controls ready on loopback 9413');
