<?php
if (!defined('ABSPATH')) { exit; }
function ar_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form','gallery','caption','style','script']);
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    add_editor_style(['assets/css/design-system.css','style.css']);
    register_nav_menus(['primary'=>'Navegación principal']);
}
add_action('after_setup_theme','ar_setup');
function ar_assets() {
    wp_enqueue_style('ar-fonts','https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap',[],null);
    wp_enqueue_style('ar-design',get_theme_file_uri('/assets/css/design-system.css'),['ar-fonts'],'1.0.0');
    wp_enqueue_style('ar-theme',get_stylesheet_uri(),['ar-design'],'1.0.2');
    wp_enqueue_script('ar-navigation',get_theme_file_uri('/assets/js/navigation.js'),[], '1.0.0',['strategy'=>'defer','in_footer'=>true]);
}
add_action('wp_enqueue_scripts','ar_assets');
function ar_url($path='') { return esc_url(home_url('/'.ltrim($path,'/'))); }
function ar_logo() { return esc_url(get_theme_file_uri('/assets/images/american-recruiters-logo.png')); }
function ar_services() { return [
    ['Headhunting','servicios/headhunting/'],
    ['Servicio personalizado','servicios/reclutamiento-personalizado/'],
    ['Fast Placement','servicios/fast-placement/']
]; }
function ar_navigation() { ?>
<ul class="ar-menu"><li><a class="ar-header-link" href="<?php echo ar_url('servicios/'); ?>">Servicios</a><button class="ar-sub-toggle" aria-label="Mostrar servicios" aria-controls="ar-services-menu" aria-expanded="false">▾</button><ul class="ar-submenu" id="ar-services-menu" hidden><?php foreach(ar_services() as $s): ?><li><a href="<?php echo ar_url($s[1]); ?>"><?php echo esc_html($s[0]); ?></a></li><?php endforeach; ?></ul></li>
<?php foreach([['Cómo trabajamos','metodologia/'],['Nosotros','nosotros/'],['Blog','blog/'],['Enviar CV','candidatos/']] as $s): ?><li><a class="ar-header-link" href="<?php echo ar_url($s[1]); ?>"><?php echo esc_html($s[0]); ?></a></li><?php endforeach; ?>
<li class="ar-mobile-cta"><a href="<?php echo ar_url('contacto/'); ?>">Solicitar propuesta →</a></li></ul><?php }
function ar_hero($title,$description='Base estructural en desarrollo. Contenido pendiente de aprobación.',$heading=true){ ?>
<section class="ar-interior-hero"><div class="container"><nav class="ar-breadcrumb" aria-label="Ruta de navegación"><a href="<?php echo ar_url(); ?>">Inicio</a><span aria-hidden="true">/</span><span><?php echo esc_html($title); ?></span></nav><div class="overline">AMERICAN RECRUITERS MÉXICO</div><?php if($heading): ?><h1><?php echo esc_html($title); ?></h1><?php endif; ?><?php if($description!==''): ?><p><?php echo esc_html($description); ?></p><?php endif; ?></div></section><?php }
/* Only the known initial scaffolds receive automatic demo sections. */
function ar_is_scaffold($post_id=0) {
    $text=trim(wp_strip_all_tags(get_post_field('post_content',$post_id ?: get_the_ID())));
    if($text==='') return true;
    foreach(['Base estructural en desarrollo.','Servicio previsto en la arquitectura aprobada.','Documento legal pendiente de revisión'] as $prefix) {
        if(strpos($text,$prefix)===0) return true;
    }
    return false;
}
function ar_page_hero($description='Base estructural en desarrollo. Contenido pendiente de aprobación.') {
    $content=get_post_field('post_content',get_the_ID());
    ar_hero(get_the_title(),ar_is_scaffold() ? $description : '',!preg_match('/<h1\b/i',$content));
}
function ar_font_hints($urls,$relation) {
    if($relation==='preconnect') {
        $urls[]='https://fonts.googleapis.com';
        $urls[]=['href'=>'https://fonts.gstatic.com','crossorigin'=>'anonymous'];
    }
    return $urls;
}
add_filter('wp_resource_hints','ar_font_hints',10,2);
/* Keep the approved photograph; optimize only its Home image block. */
function ar_home_hero_image($html) {
    if(!is_front_page() || strpos($html,'photo-1556761175-b413da4baf72')===false || !class_exists('WP_HTML_Tag_Processor')) return $html;
    $image=new WP_HTML_Tag_Processor($html);
    while($image->next_tag('IMG')) {
        if(strpos((string)$image->get_attribute('src'),'photo-1556761175-b413da4baf72')===false) continue;
        $base=content_url('/uploads/2026/10/ar-hero-editorial-');
        $image->set_attribute('src',$base.'1300.webp');
        $image->set_attribute('srcset',$base.'640.webp 640w, '.$base.'960.webp 960w, '.$base.'1300.webp 1300w');
        $image->set_attribute('sizes','(max-width: 820px) 80vw, 56vw');
        $image->set_attribute('width',1300);
        $image->set_attribute('height',975);
        $image->set_attribute('loading','eager');
        $image->set_attribute('fetchpriority','high');
        $image->set_attribute('decoding','async');
        break;
    }
    return $image->get_updated_html();
}
add_filter('the_content','ar_home_hero_image',20);
function ar_demo_form($candidate=false) { ?>
<div class="ar-demo-form demo-form" role="group" aria-label="Formulario visual de demostración"><p><strong>Demostración visual — no admite envíos.</strong> No introduzcas datos personales. Campos, destinatarios y consentimiento se definirán en la fase funcional.</p><fieldset disabled><legend><?php echo $candidate ? 'Envío de CV · base visual' : 'Solicitud de propuesta · base visual'; ?></legend><label for="ar-demo-name">Nombre</label><input id="ar-demo-name" type="text" placeholder="Campo de demostración"><label for="ar-demo-email">Correo electrónico</label><input id="ar-demo-email" type="email" placeholder="Campo de demostración"><?php if($candidate): ?><p>La carga privada de CV PDF se implementará después de aprobar su tratamiento y destinatario. No se almacenan archivos en esta demo.</p><?php else: ?><label for="ar-demo-company">Empresa</label><input id="ar-demo-company" type="text" placeholder="Campo de demostración"><?php endif; ?><label for="ar-demo-message">Mensaje</label><textarea id="ar-demo-message" rows="4" placeholder="Campo de demostración"></textarea><button class="btn btn-purple" type="button" disabled>Envío no disponible en desarrollo</button></fieldset></div><?php }
function ar_patterns() {
    register_block_pattern_category('ar-editorial',['label'=>'American Recruiters · Ejecutivo Editorial']);
    $patterns=[
      'intro'=>['Introducción editorial','<!-- wp:group {"className":"ar-reading"} --><div class="wp-block-group ar-reading"><!-- wp:heading --><h2 class="wp-block-heading">Encabezado de sección</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Contenido pendiente de aprobación comercial.</p><!-- /wp:paragraph --></div><!-- /wp:group -->'],
      'cards'=>['Tarjetas de servicios','<!-- wp:columns {"className":"ar-grid-3"} --><div class="wp-block-columns ar-grid-3">'.str_repeat('<!-- wp:column {"className":"ar-card"} --><div class="wp-block-column ar-card"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Título de tarjeta</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Descripción pendiente de aprobación.</p><!-- /wp:paragraph --></div><!-- /wp:column -->',3).'</div><!-- /wp:columns -->'],
      'faq'=>['FAQ accesible','<!-- wp:details --><details class="wp-block-details"><summary>Pregunta pendiente de aprobación</summary><!-- wp:paragraph --><p>Respuesta pendiente de aprobación.</p><!-- /wp:paragraph --></details><!-- /wp:details -->'],
      'cta'=>['CTA compartido','<!-- wp:group {"className":"ar-cta-band"} --><div class="wp-block-group ar-cta-band"><!-- wp:heading --><h2 class="wp-block-heading">Solicitar propuesta</h2><!-- /wp:heading --><!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="'.ar_url('contacto/').'">Ir a contacto</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div><!-- /wp:group -->']
    ];
    foreach($patterns as $id=>$p) register_block_pattern('ar-editorial/'.$id,['title'=>$p[0],'categories'=>['ar-editorial'],'content'=>$p[1]]);
}
add_action('init','ar_patterns');
/* Bootstrap only for the explicitly authorized staging. Never overwrites existing pages. */
function ar_staging_bootstrap(){
    if(wp_parse_url(home_url(),PHP_URL_HOST)!=='desarrollo.americanrecruiters.mx' || !current_user_can('manage_options') || get_option('ar_base_installed')) return;
    $pages=[['inicio','Inicio','',0],['servicios','Servicios','',0],['headhunting','Headhunting','page-service.php','servicios'],['reclutamiento-personalizado','Servicio personalizado de reclutamiento y selección','page-service.php','servicios'],['fast-placement','Fast Placement','page-service.php','servicios'],['metodologia','Cómo trabajamos','page-methodology.php',0],['nosotros','Nosotros','page-corporate.php',0],['blog','Blog','',0],['contacto','Contacto','page-contact.php',0],['candidatos','Enviar CV','page-candidates.php',0],['preguntas-frecuentes','Preguntas frecuentes','page-faq.php',0],['privacidad','Privacidad','',0],['sistema-visual','Sistema visual · Base de desarrollo','page-components.php',0]];
    $ids=[];
    foreach($pages as $p){
        $path=($p[3]?$p[3].'/':'').$p[0]; $existing=get_page_by_path($path);
        if($existing){$ids[$p[0]]=$existing->ID;continue;}
        $content='<!-- wp:paragraph --><p>Base estructural en desarrollo. Contenido pendiente de aprobación; esta página no ofrece funciones comerciales activas.</p><!-- /wp:paragraph -->';
        if($p[0]==='fast-placement') $content='<!-- wp:paragraph --><p>Servicio previsto en la arquitectura aprobada. Alcance, metodología y descripción comercial pendientes de confirmación. No se ofrecen plazos ni garantías en esta base.</p><!-- /wp:paragraph -->';
        if($p[0]==='privacidad') $content='<!-- wp:paragraph --><p>Documento legal pendiente de revisión y aprobación. No se recopilan solicitudes ni CV mediante los componentes de demostración de esta fase.</p><!-- /wp:paragraph -->';
        $id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$p[1],'post_name'=>$p[0],'post_parent'=>$p[3]?($ids[$p[3]]??0):0,'post_content'=>$content],true);
        if(is_wp_error($id)) continue;
        $ids[$p[0]]=$id;
        if($p[2]) update_post_meta($id,'_wp_page_template',$p[2]);
    }
    if(!empty($ids['inicio'])&&!empty($ids['blog'])){update_option('show_on_front','page');update_option('page_on_front',$ids['inicio']);update_option('page_for_posts',$ids['blog']);}
    update_option('ar_base_installed',['version'=>'1.0.0','pages'=>$ids,'date'=>current_time('mysql')]);
}
add_action('after_switch_theme','ar_staging_bootstrap');


/* Real historical taxonomies; never link to an empty public archive. */
function ar_post_categories($post_id=0) {
    $categories=get_the_category($post_id ?: get_the_ID());
    if(!$categories || is_wp_error($categories)) return;
    echo '<ul class="ar-post-categories" aria-label="Categorías">';
    foreach($categories as $category) {
        echo '<li>';
        $url=(int)$category->count>0 ? get_category_link($category->term_id) : '';
        if($url && !is_wp_error($url)) {
            echo '<a class="ar-category" href="'.esc_url($url).'">'.esc_html($category->name).'</a>';
        } else {
            echo '<span class="ar-category">'.esc_html($category->name).'</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
}
