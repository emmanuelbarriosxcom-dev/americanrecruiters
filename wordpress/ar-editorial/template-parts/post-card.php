<?php if(!defined('ABSPATH'))exit;
$post_url=get_post_status()==='publish'?get_permalink():get_preview_post_link();
if(ar_v21_editorial_review())$post_url=ar_v21_review_url($post_url);
$post_date=get_post_field('post_date',get_the_ID(),'raw'); ?>
<article <?php post_class('editorial-card'); ?>>
<?php if(has_post_thumbnail()): ?><a class="card-image" href="<?php echo esc_url($post_url); ?>" aria-label="<?php echo esc_attr(get_the_title()); ?>"><?php the_post_thumbnail('medium_large',['loading'=>'lazy','decoding'=>'async']); ?></a><?php endif; ?>
<div class="card-copy"><span class="meta"><time datetime="<?php echo esc_attr(mysql2date('c',$post_date)); ?>"><?php echo esc_html(mysql2date(get_option('date_format'),$post_date)); ?></time></span><?php ar_post_categories(); ?><h3><a href="<?php echo esc_url($post_url); ?>"><?php the_title(); ?></a></h3><?php the_excerpt(); ?><a class="text-link" href="<?php echo esc_url($post_url); ?>"><?php echo get_post_status()==='publish'?'Leer artículo':'Vista previa'; ?> <?php echo ar_v21_icon('arrow-up-right'); ?></a></div></article>
