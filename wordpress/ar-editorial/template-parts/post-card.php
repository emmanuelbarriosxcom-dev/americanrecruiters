<?php if(!defined('ABSPATH'))exit; ?>
<article <?php post_class('editorial-card'); ?>>
<?php if(has_post_thumbnail()): ?><a class="card-image" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>"><?php the_post_thumbnail('medium_large',['loading'=>'lazy','decoding'=>'async']); ?></a><?php endif; ?>
<div class="card-copy"><span class="meta"><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time></span><?php ar_post_categories(); ?><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?><a class="text-link" href="<?php the_permalink(); ?>">Leer artículo <?php echo ar_v21_icon('arrow-up-right'); ?></a></div></article>
