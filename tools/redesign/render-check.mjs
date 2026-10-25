import fs from 'node:fs';import path from 'node:path';
const dep='/Users/emmanuelbarrios/Documents/Codex/american-recruiters-release-20261023/tools/release/node_modules/';
const {PHP}=await import(dep+'@php-wasm/universal/index.js');const {loadNodeRuntime,createNodeFsMountHandler}=await import(dep+'@php-wasm/node/index.js');
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:2}}));
await php.mount('/theme',createNodeFsMountHandler(path.resolve('wordpress/ar-editorial')));
const baseline=JSON.parse(fs.readFileSync('/Users/emmanuelbarrios/Documents/Codex/AR-AC-private-20261025/baseline.json'));
const fixtures=Object.fromEntries(baseline.posts.filter(p=>p.type==='page'&&p.status==='publish').map(p=>[p.slug==='inicio'?'home':p.slug,p]));
const out='/Users/emmanuelbarrios/Documents/Codex/AR-AC-private-20261025/render-check';fs.mkdirSync(out,{recursive:true});
const stubs=`define('ABSPATH','/');function add_action($a,$b){}function esc_html($v){return htmlspecialchars($v,ENT_QUOTES,'UTF-8');}function esc_attr($v){return esc_html($v);}function esc_url($v){return esc_attr($v);}function wp_strip_all_tags($v){return strip_tags($v);}function remove_accents($v){return iconv('UTF-8','ASCII//TRANSLIT',$v);}function apply_filters($a,$v){return $v;}function get_the_content(){return $GLOBALS['fixture'];}function get_the_title(){return $GLOBALS['title'];}function get_the_ID(){return 15;}function get_post_field($k,$id){return $GLOBALS['slug'];}function ar_url($p=''){return 'https://desarrollo.americanrecruiters.mx/'.$p;}function get_theme_file_uri($p){return '/theme'.$p;}function get_template_part($p,$n=null,$args=[]){include '/theme/'.$p.'.php';}function ar_page_hero(){}require '/theme/inc/v21.php';`;
for(const [key,p] of Object.entries(fixtures)){
 await php.writeFile('/fixture.json',JSON.stringify(p));
 const r=await php.run({code:`<?php ${stubs}$data=json_decode(file_get_contents('/fixture.json'),true);$GLOBALS['fixture']=$data['content'];$GLOBALS['slug']=$data['slug'];$GLOBALS['title']=$data['title'];${key==='home'?'ar_v21_home();':'ar_v21_page();'}`});
 fs.writeFileSync(path.join(out,key+'.html'),r.text);console.log(key,'exit',r.exitCode,'errors',r.errors,'bytes',r.bytes.length);
}
process.exit(0);
