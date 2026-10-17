<?php
get_header();
$blog=get_post((int)get_option('page_for_posts'));
$intro=is_home() && $blog && $blog->post_status==='publish' && !ar_is_scaffold($blog->ID) ? $blog->post_content : '';
/* The previous phase's three legacy cards are a migration placeholder only.
 * Keep their stored content intact; replace that exact section with the native
 * loop once real posts exist. Do not suppress other editorial sections. */
if($intro && have_posts()) {
    $blocks=[];
    foreach(parse_blocks($intro) as $block) {
        $markup=serialize_block($block);
        $legacy=($block['blockName']==='core/group'
            && strpos($block['attrs']['className'] ?? '','ar-section--soft')!==false
            && strpos($markup,'Artículos del sitio actual')!==false
            && strpos($markup,'americanrecruiters.com.mx/')!==false);
        if(!$legacy) $blocks[]=$markup;
    }
    $intro=implode('',$blocks);
}
$title=is_search() ? 'Resultados de búsqueda' : (is_archive() ? get_the_archive_title() : ($blog ? get_the_title($blog) : 'Blog'));
ar_hero(wp_strip_all_tags($title),'',!preg_match('/<h1\b/i',$intro));
?>
<section class="ar-page-content"><div class="container">
<?php if($intro): ?><div class="ar-blog-intro"><?php echo apply_filters('the_content',$intro); ?></div><?php endif; ?>
<?php if(is_archive()) the_archive_description('<div class="ar-reading">','</div>'); ?>
<?php if(!$intro || !has_block('core/query',$intro)): ?>
<?php if(have_posts()): ?><div class="ar-grid-3">
<?php while(have_posts()):the_post(); ?><article <?php post_class('ar-card'); ?>>
<?php if(has_post_thumbnail()): ?><a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>"><?php the_post_thumbnail('medium_large',['loading'=>'lazy']); ?></a><?php endif; ?>
<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
<p class="ar-article-meta"><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time></p>
<?php ar_post_categories(); ?>
<?php the_excerpt(); ?></article><?php endwhile; ?></div><?php the_posts_pagination(); ?>
<?php else: ?><p class="ar-blog-empty">Sin artículos publicados. La plantilla está preparada para la siguiente fase.</p><?php endif; ?>
<?php endif; ?></div></section><?php get_footer(); ?>

