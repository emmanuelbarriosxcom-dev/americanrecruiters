<?php get_header();
$blog=get_post((int)get_option('page_for_posts'));
$title=is_search()?'Resultados de búsqueda':(is_archive()?wp_strip_all_tags(get_the_archive_title()):($blog?get_the_title($blog):'Blog'));
ar_v21_hero($title,'','PERSPECTIVAS / AMERICAN RECRUITERS');
?>
<section class="section-block"><div class="wrap">
<?php if(is_archive()) the_archive_description('<div class="reading">','</div>'); ?>
<?php ar_v21_categories(); ?>
<?php if(have_posts()): ?><div class="editorial-grid">
<?php while(have_posts()):the_post(); if(get_post_status()==='publish')get_template_part('template-parts/post-card'); endwhile; ?>
</div><?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'← Anterior','next_text'=>'Siguiente →']); ?>
<?php else: ?><div class="empty"><h2>Sin artículos publicados</h2><p>Los contenidos de este entorno se conservan en borrador para su revisión editorial.</p><a class="text-link" href="<?php echo ar_url('servicios/'); ?>">Explorar servicios <?php echo ar_v21_icon('arrow-right'); ?></a></div><?php endif; ?>
</div></section><?php get_footer(); ?>

