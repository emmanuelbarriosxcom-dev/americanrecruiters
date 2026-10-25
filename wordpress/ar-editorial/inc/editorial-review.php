<?php
/** Authenticated visual QA of the existing drafts. Never changes publication state. */
if(!defined('ABSPATH'))exit;
function ar_v21_editorial_ids(){return [156,159,718,762,766,769,772,775,778,781,783,808,816,818,820,822,824];}
function ar_v21_editorial_review(){
    return isset($_GET['ar_v21_review'],$_GET['_wpnonce'])
        && $_GET['ar_v21_review']==='editorial'
        && current_user_can('edit_posts')
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])),'ar-v21-editorial-review');
}
function ar_v21_review_url($url){
    return wp_nonce_url(add_query_arg('ar_v21_review','editorial',$url),'ar-v21-editorial-review');
}
add_action('admin_bar_menu',static function($bar){
    if(current_user_can('edit_posts'))$bar->add_node(['id'=>'ar-v21-editorial-review','title'=>'Revisión editorial V2.1','href'=>ar_v21_review_url(home_url('/blog/'))]);
},100);
add_action('template_redirect',static function(){if(ar_v21_editorial_review())nocache_headers();});
