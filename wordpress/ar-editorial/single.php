<?php get_header(); while(have_posts()):the_post(); ar_page_hero(''); ?>
<article <?php post_class('ar-page-content'); ?>><div class="container"><div class="ar-article ar-reading">
<p class="ar-article-meta"><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time> · <?php echo esc_html(get_the_author()); ?></p>
<?php ar_post_categories(); ?>
<?php if(has_post_thumbnail()) the_post_thumbnail('large',['loading'=>'eager']); ?>
<?php the_content(); wp_link_pages(); ?>
</div></div></article><?php endwhile; get_footer(); ?>

