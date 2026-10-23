<?php
require '/wordpress/wp-load.php';
$checks=[];
function check_rc($name,$pass){global $checks;$checks[$name]=(bool)$pass;}
$before=(int)get_option('ar_qa_pages_before');
check_rc('Activation does not create pages',(int)wp_count_posts('page')->publish===$before);
update_option('blog_public','0');
$home=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA Home','post_content'=>'<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">QA Home</h1><!-- /wp:heading --><!-- wp:paragraph --><p>Isolated fixture, not editorial copy.</p><!-- /wp:paragraph -->']);
$blog=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA Blog','post_name'=>'blog','post_content'=>'<!-- wp:paragraph --><p>QA Blog intro.</p><!-- /wp:paragraph -->']);
update_option('show_on_front','page');update_option('page_on_front',$home);update_option('page_for_posts',$blog);update_option('posts_per_page',3);update_option('permalink_structure','/%postname%/');
$cat=wp_insert_term('QA Category','category');
for($i=1;$i<=7;$i++)wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'QA Post '.$i,'post_name'=>'qa-post-'.$i,'post_content'=>'<!-- wp:paragraph --><p>QA article.</p><!-- /wp:paragraph -->','post_category'=>[$cat['term_id']]]);
$draft=wp_insert_post(['post_type'=>'post','post_status'=>'draft','post_title'=>'PRIVATE_DRAFT_SENTINEL','post_content'=>'DO_NOT_RENDER_DRAFT']);
global $wp_rewrite;
$wp_rewrite->init();
flush_rewrite_rules(false);
foreach(glob(get_theme_file_path().'/*.php') as $file){try{token_get_all(file_get_contents($file),TOKEN_PARSE);check_rc('PHP parse '.basename($file),true);}catch(ParseError $e){check_rc('PHP parse '.basename($file),false);}}
check_rc('Gutenberg supports',current_theme_supports('align-wide')&&current_theme_supports('editor-styles')&&current_theme_supports('responsive-embeds'));
foreach(['intro','cards','faq','cta'] as $pattern)check_rc('Pattern '.$pattern,WP_Block_Patterns_Registry::get_instance()->is_registered('ar-editorial/'.$pattern));
$valid=['name'=>'QA','company'=>'QA Company','email'=>'qa@example.invalid','service'=>'headhunting','message'=>'Synthetic test','privacy'=>'1'];
check_rc('Valid business validator',!ar_f9_validate('business',$valid)['errors']);
foreach(['name'=>['array'],'email'=>"bad\r\n@example.invalid",'service'=>'unknown','message'=>str_repeat('x',3001),'company'=>'<b></b>'] as $field=>$value){$bad=$valid;$bad[$field]=$value;check_rc('Reject '.$field,!empty(ar_f9_validate('business',$bad)['errors']));}
check_rc('Valid candidate validator',!ar_f9_validate('candidate',['name'=>'QA','email'=>'qa@example.invalid','privacy'=>'1'])['errors']);
check_rc('Privacy fails closed',!empty(ar_f9_validate('candidate',['name'=>'QA','email'=>'qa@example.invalid'])['errors']));
foreach([['GET',true,0,405],['POST',false,0,403],['POST',true,5,429],['POST',true,0,503]] as $case)check_rc('Guard '.$case[3],ar_f9_guard($case[0],$case[1],$case[2])===$case[3]);
check_rc('PDF oversized rejected',ar_f9_pdf_check(['error'=>0,'size'=>5242881,'name'=>'test.pdf','tmp_name'=>''])!=='');
check_rc('PDF non-upload rejected',ar_f9_pdf_check(['error'=>0,'size'=>10,'name'=>'test.pdf','tmp_name'=>__FILE__])!=='');
check_rc('NonPDF content rejected',ar_f9_pdf_content_check(__FILE__)!=='');
check_rc('Demo hidden outside staging',ar_f9_form()==='');
// Exercise the host-gated disabled UI in this isolated process only.
add_filter('home_url',function($url){return preg_replace('~127\.0\.0\.1:\d+~','desarrollo.americanrecruiters.mx',$url);});
$html=ar_f9_form('candidate');
check_rc('Disabled candidate controls',strpos($html,'<fieldset disabled')!==false && strpos($html,'<form')===false && strpos($html,'<button disabled type="button"')!==false);
check_rc('No consent checkbox',strpos($html,'type="checkbox"')===false);
$before=(int)wp_count_posts('page')->publish;wp_set_current_user(1);ar_staging_bootstrap();check_rc('Bootstrap explicit opt-in required',(int)wp_count_posts('page')->publish===$before);
remove_all_filters('home_url');
ob_start(); ar_f9_qa_page(); $qa=ob_get_clean(); // Host-gated here, no shared QA endpoint called.
echo json_encode(['php'=>PHP_VERSION,'wordpress'=>get_bloginfo('version'),'checks'=>$checks,'fixture_ids'=>['home'=>$home,'blog'=>$blog,'draft'=>$draft]],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
