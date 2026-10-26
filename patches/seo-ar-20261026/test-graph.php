<?php
define('ABSPATH', '/fixture/');
$GLOBALS['host']='desarrollo.americanrecruiters.mx';
$GLOBALS['post_id']=808;
$GLOBALS['singular']=true;
$GLOBALS['checks']=[];
function wp_parse_url($v,$component) { return parse_url($v,$component); }
function home_url($path='/') { return 'https://'.$GLOBALS['host'].$path; }
function is_singular($type) { return $type==='post' && $GLOBALS['singular']; }
function get_the_ID() { return $GLOBALS['post_id']; }
function get_queried_object_id() { return $GLOBALS['post_id']; }
function add_filter($hook,$callback,$priority=10) { $GLOBALS['registered'][]=[$hook,$callback,$priority]; }
require __DIR__.'/editorial-author.php';
function check_ar($ok,$name) { $GLOBALS['checks'][]=['case'=>$name,'result'=>$ok?'PASS':'FAIL']; }
$fixtures=json_decode(file_get_contents(__DIR__.'/fixtures/graphs17.json'),true);
foreach (['desarrollo.americanrecruiters.mx','www.americanrecruiters.com.mx','americanrecruiters.com.mx'] as $host) {
 $GLOBALS['host']=$host;
 foreach ($fixtures as $fixture) {
  $id=$fixture['id'];$GLOBALS['post_id']=$id;
  $graph=json_decode(str_replace('desarrollo.americanrecruiters.mx',$host,json_encode($fixture['graph'])),true);
  $out=ar_seo_editorial_graph($graph);
  $orgs=array_values(array_filter($out,function($x){return ($x['@id']??'')===home_url('/blog/#equipo-editorial');}));
  check_ar(count($orgs)===1 && $orgs[0]['@type']==='Organization',"$host/$id one editorial Organization");
  $ids=array_column($out,'@id');check_ar(count($ids)===count(array_unique($ids)),"$host/$id unique node IDs");
  foreach ($graph as $node) {
   $types=(array)($node['@type']??[]);
   if (!array_intersect($types,['Article','BlogPosting','WebPage'])) continue;
   $matches=array_values(array_filter($out,function($x)use($node){return ($x['@id']??'')===($node['@id']??'');}));
   $original=$node;$new=$matches[0]??[];unset($original['author'],$new['author']);
   check_ar($original===$new,"$host/$id preserve dates/headline/publisher/images/url");
   if (isset($node['author']) || array_intersect($types,['Article','BlogPosting'])) {
    check_ar(($matches[0]['author']['@id']??'')===home_url('/blog/#equipo-editorial'),"$host/$id author resolves");
   }
  }
  check_ar(ar_seo_editorial_graph($out)===$out,"$host/$id idempotent");
  check_ar(ar_seo_editorial_name('old')==='Equipo editorial de American Recruiters',"$host/$id visible signature");
 }
}
$GLOBALS['host']='unrelated.example';check_ar(ar_seo_editorial_graph($graph)===$graph,'unapproved host unchanged');
$GLOBALS['host']='desarrollo.americanrecruiters.mx';$GLOBALS['post_id']=999;check_ar(ar_seo_editorial_graph($graph)===$graph,'unapproved post unchanged');
$GLOBALS['post_id']=808;$GLOBALS['singular']=false;check_ar(ar_seo_editorial_graph($graph)===$graph,'archive unchanged');$GLOBALS['singular']=true;
$g=[['@type'=>'Article','@id'=>'a','author'=>['@id'=>'p']],['@type'=>'Person','@id'=>'p','name'=>'Example'],['@type'=>'Thing','@id'=>'t','about'=>['@id'=>'p']]];
$o=ar_seo_editorial_graph($g);check_ar(in_array('p',array_column($o,'@id'),true),'Person retained when referenced outside replaced author');
array_pop($g);$o=ar_seo_editorial_graph($g);check_ar(!in_array('p',array_column($o,'@id'),true),'orphan previous Person removed');
check_ar(count($GLOBALS['registered'])===2,'only two intended filters registered');
$failed=count(array_filter($GLOBALS['checks'],function($x){return $x['result']==='FAIL';}));
echo json_encode(['suite'=>'PHP fixture graphs, not live WordPress integration','total'=>count($GLOBALS['checks']),'failed'=>$failed,'cases'=>$GLOBALS['checks']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
if ($failed) { exit(1); }
