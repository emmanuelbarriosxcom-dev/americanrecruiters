<?php get_header();
$blog=get_post((int)get_option('page_for_posts'));
$title=is_search()?'Resultados de búsqueda':(is_archive()?wp_strip_all_tags(get_the_archive_title()):($blog?get_the_title($blog):'Blog'));
ar_v21_hero($title,'<p>Lecturas sobre búsqueda ejecutiva, selección y decisiones empresariales.</p>','PERSPECTIVAS / BLOG');
?>
<?php if(ar_v21_editorial_review()): ?><div class="wrap review-note">Vista privada de revisión: los 17 artículos siguen en borrador. Estas tarjetas y sus enlaces de vista previa solo están disponibles para usuarios autorizados.</div><?php endif; ?>
<section class="section-block"><div class="wrap">
<?php if(is_archive()) the_archive_description('<div class="reading">','</div>'); ?>
<?php ar_v21_categories(); ?>
<div class="archive-heading"><h2>Perspectivas para tu empresa</h2></div>
<?php if(have_posts()): ?><div class="editorial-grid">
<?php while(have_posts()):the_post(); if(get_post_status()==='publish'||ar_v21_editorial_review())get_template_part('template-parts/post-card'); endwhile; ?>
</div><?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'← Anterior','next_text'=>'Siguiente →']); ?>
<?php else: get_template_part('template-parts/editorial-empty'); endif; ?>
</div></section><?php get_template_part('template-parts/closing-cta'); get_footer(); ?>

