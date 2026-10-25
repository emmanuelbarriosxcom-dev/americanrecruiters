<?php
/** V2.1 presentation adapters. No database writes; Gutenberg remains the source. */
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/icons.php';
require_once __DIR__.'/home-v21.php';

/** The public editorial loop must not leak private QA posts to signed-in previews. */
add_action('pre_get_posts',static function($query){
    if(!is_admin() && $query->is_main_query() && ($query->is_home() || $query->is_archive() || $query->is_search())) $query->set('post_status','publish');
});

function ar_v21_dom($html) {
    if (!class_exists('DOMDocument')) return null;
    $doc=new DOMDocument('1.0','UTF-8');
    $old=libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><!doctype html><html><body><div id="ar-v21-source">'.$html.'</div></body></html>',LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($old);
    return [$doc,new DOMXPath($doc)];
}
function ar_v21_class($class) { return 'contains(concat(" ",normalize-space(@class)," ")," '.$class.' ")'; }
function ar_v21_find($node,$class) { return (new DOMXPath($node->ownerDocument))->query('.//*['.ar_v21_class($class).']',$node)->item(0); }
function ar_v21_nodes($node,$path) { return iterator_to_array((new DOMXPath($node->ownerDocument))->query($path,$node)); }
function ar_v21_html($node,$inner=false) {
    if (!$node) return '';
    if (!$inner) return $node->ownerDocument->saveHTML($node);
    $s=''; foreach($node->childNodes as $n) $s.=$node->ownerDocument->saveHTML($n); return $s;
}
function ar_v21_first($node,$tag) { return $node ? (new DOMXPath($node->ownerDocument))->query('.//'.$tag,$node)->item(0) : null; }
function ar_v21_choose_icon($text) {
    $text=remove_accents(mb_strtolower(wp_strip_all_tags($text)));
    foreach(['chat'=>['pregunta','respuest','convers','contact'],'align'=>['criteri','perfil','aline','condicion'],'brief'=>['necesidad','context','reto','brief','informacion'],'search'=>['liderazgo','busqueda','identificar'],'users'=>['equipo','colabor','person','apoyo'],'check'=>['decisi','selecci','evalu'],'info'=>['pendiente','provisional','fast placement'],'compass'=>['servicio','elegir','orient']] as $icon=>$words) foreach($words as $word) if(strpos($text,$word)!==false)return $icon;
    return 'target';
}
/** Remove obsolete embedded presentation only; keep content, IDs and destinations. */
function ar_v21_presentation($html) {
    $html=preg_replace('~<style\b[^>]*>.*?</style>~is','',$html);
    $html=preg_replace('~\sstyle="[^"]*"~i','',$html);
    return $html;
}
function ar_v21_decorate($html) {
    $html=preg_replace_callback('~<summary([^>]*)>(.*?)</summary>~s',static function($m){
        if(strpos($m[2],'<svg')!==false)return $m[0];
        return '<summary'.$m[1].'>'.$m[2].ar_v21_icon('plus','summary-mark').'</summary>';
    },$html);
    return preg_replace_callback('~(<a\b[^>]*class="[^"]*(?:wp-block-button__link|text-link|\bbutton\b)[^"]*"[^>]*>)(.*?)</a>~s',static function($m){
        if(strpos($m[2],'<svg')!==false)return $m[0];
        return $m[1].preg_replace('/\s*[→↗↓]\s*$/u','',$m[2]).ar_v21_icon('arrow-up-right').'</a>';
    },$html);
}
function ar_v21_hero($title,$description='',$label='AMERICAN RECRUITERS MÉXICO',$image='',$heading_html='',$image_alt='') {
    get_template_part('template-parts/hero',null,compact('title','description','label','image','heading_html','image_alt'));
}
function ar_v21_section($heading,$body,$index,$dark=false,$process=false,$close=false,$id='') {
    get_template_part('template-parts/section',null,compact('heading','body','index','dark','process','close','id'));
}
function ar_v21_page() {
    $html=apply_filters('the_content',get_the_content());
    $parsed=ar_v21_dom(ar_v21_presentation($html));
    if(!$parsed){ar_page_hero();echo '<section class="section-block"><div class="wrap reading">'.$html.'</div></section>';return;}
    [$doc,$xp]=$parsed;
    $phase=$xp->query('//*['.ar_v21_class('ar-phase4').']')->item(0);
    if(!$phase){
        ar_v21_hero(get_the_title());
        echo '<section class="section-block"><div class="wrap reading">'.ar_v21_decorate(ar_v21_presentation($html)).'</div></section>';return;
    }
    $sections=ar_v21_nodes($phase,'./div');
    if(!$sections){echo $html;return;}
    $intro=array_shift($sections);$h1=ar_v21_first($intro,'h1');
    $heading_html=ar_v21_html($h1);$title=$h1?$h1->textContent:get_the_title();
    if($h1)$h1->parentNode->removeChild($h1);
    $desc=''; foreach(ar_v21_nodes($intro,'.//p|.//*['.ar_v21_class('wp-block-buttons').']') as $n) $desc.=ar_v21_html($n);
    $slug=get_post_field('post_name',get_the_ID());
    $image=$slug==='headhunting'?'editorial-reunion.webp':(in_array($slug,['reclutamiento-personalizado','nosotros'],true)?'editorial-conversacion.webp':'');
    $labels=['headhunting'=>'SERVICIOS / BÚSQUEDA EJECUTIVA','reclutamiento-personalizado'=>'SERVICIOS / SELECCIÓN A LA MEDIDA','metodologia'=>'CÓMO TRABAJAMOS','contacto'=>'EMPRESAS','candidatos'=>'ESPACIO PARA CANDIDATOS'];
    $source_image=ar_v21_first($phase,'img');
    ar_v21_hero($title,ar_v21_decorate($desc),$labels[$slug]??'AMERICAN RECRUITERS MÉXICO',$image,$heading_html,$source_image?$source_image->getAttribute('alt'):'');
    foreach($sections as $i=>$section){
        $h=ar_v21_first($section,'h2');
        if(!$h && ar_v21_first($section,'img'))continue; // already represented by the approved hero image.
        $heading=ar_v21_html($h);$text=$h?$h->textContent:'';
        if($h)$h->parentNode->removeChild($h);
        $body=ar_v21_html($section,true);
        if(!in_array($slug,['contacto','candidatos'],true)){
            $body=preg_replace_callback('~(<div\b[^>]*class="[^"]*\bar-card\b[^"]*"[^>]*>)(\s*)(<h3\b[^>]*>.*?</h3>)~s',static fn($m)=>$m[1].$m[2].ar_v21_badge(ar_v21_choose_icon($m[3])).$m[3],$body);
        }
        $close=($i===count($sections)-1&&in_array($slug,['servicios','headhunting','reclutamiento-personalizado','nosotros'],true))||($slug==='contacto'&&strpos($text,'¿Buscas enviar tu currículum?')!==false);
        ar_v21_section($heading,ar_v21_decorate($body),$i+1,strpos($section->getAttribute('class'),'ar-section--dark')!==false,$slug==='metodologia'&&strpos($body,'ar-step-grid')!==false,$close,$section->getAttribute('id'));
    }
}

/** Shared server-side TOC. The complete article remains readable without JavaScript. */
function ar_v21_article_content($html) {
    $parsed=ar_v21_dom($html);if(!$parsed)return [$html,[]];
    [$doc,$xp]=$parsed;$root=$doc->getElementById('ar-v21-source');$used=['main','nav','subnav'];$toc=[];
    foreach($xp->query('//*[@id]') as $node){if($node===$root)continue;$id=$node->getAttribute('id');if(in_array($id,$used,true)){$base=$id;$i=2;while(in_array($id,$used,true))$id=$base.'-'.$i++;$node->setAttribute('id',$id);}$used[]=$id;}
    foreach($xp->query('.//h2|.//h3',$root) as $i=>$head){
        $id=$head->getAttribute('id');if(!$id){$base='contenido-'.($i+1);$id=$base;$j=2;while(in_array($id,$used,true))$id=$base.'-'.$j++;$head->setAttribute('id',$id);$used[]=$id;}
        $toc[]=['id'=>$id,'text'=>$head->textContent,'level'=>$head->tagName];
    }
    foreach(iterator_to_array($xp->query('.//table',$root)) as $i=>$table){
        $wrapper=$doc->createElement('div');$wrapper->setAttribute('class','table-scroll');$wrapper->setAttribute('tabindex','0');$wrapper->setAttribute('role','region');$wrapper->setAttribute('aria-label','Tabla '.($i+1).' — desplazable horizontalmente');$table->parentNode->replaceChild($wrapper,$table);$wrapper->appendChild($table);
    }
    return [ar_v21_html($root,true),$toc];
}
function ar_v21_context() {
    $text=remove_accents(mb_strtolower(get_the_title()));
    if(preg_match('/headhunt|honorario|directiv|gerencial|pasiv/',$text))return ['title'=>'Una búsqueda alineada con tu organización','text'=>'Revisa el servicio de búsqueda ejecutiva y presenta el contexto del puesto que necesitas cubrir.','path'=>'servicios/headhunting/','link'=>'Explorar Headhunting'];
    if(preg_match('/vacante|evaluacion|entrevista|retencion|seleccion|incorporacion/',$text))return ['title'=>'Del criterio de selección a tu necesidad','text'=>'Conoce la opción de reclutamiento y selección según las necesidades de tu empresa.','path'=>'servicios/reclutamiento-personalizado/','link'=>'Consultar selección personalizada'];
    return ['title'=>'Explora las opciones para tu empresa','text'=>'Revisa los servicios disponibles antes de plantear una necesidad de contratación.','path'=>'servicios/','link'=>'Conocer servicios'];
}
function ar_v21_categories() {
    $terms=get_categories(['hide_empty'=>true]);
    if(!$terms)return;
    echo '<nav class="category-links" aria-label="Categorías del Blog">';
    foreach($terms as $term)echo '<a href="'.esc_url(get_category_link($term)).'">'.esc_html($term->name).'</a>';
    echo '</nav>';
}
