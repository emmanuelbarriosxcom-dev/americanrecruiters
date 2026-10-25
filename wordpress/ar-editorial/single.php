<?php get_header(); while(have_posts()):the_post();
[$body,$toc]=ar_v21_article_content(apply_filters('the_content',get_the_content()));
$context=ar_v21_context();
$saved_date=get_post_field('post_date',get_the_ID(),'raw');
?>
<section class="article-hero wrap"><p class="eyebrow">PERSPECTIVAS / AMERICAN RECRUITERS</p><h1><?php the_title(); ?></h1>
<?php if(has_excerpt()): ?><p class="article-deck"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
<div class="article-meta"><time datetime="<?php echo esc_attr(mysql2date('c',$saved_date)); ?>"><?php echo esc_html(mysql2date(get_option('date_format'),$saved_date)); ?></time><span><?php echo esc_html(get_the_author()); ?></span><?php ar_post_categories(); ?></div>
<?php if(has_post_thumbnail()): ?><figure><?php the_post_thumbnail('large',['class'=>'article-feature','loading'=>'eager','fetchpriority'=>'high']); ?></figure><?php endif; ?></section>
<div class="wrap reading-grid"><?php get_template_part('template-parts/article-sidebar',null,['toc'=>$toc]); ?><article class="reading" data-toc><?php echo $body; wp_link_pages(); ?>
<?php if(strpos($body,'/contacto')===false&&strpos($body,$context['path'])===false): ?><aside class="inline-cta"><h2><?php echo esc_html($context['title']); ?></h2><p><?php echo esc_html($context['text']); ?></p><a class="text-link" href="<?php echo ar_url($context['path']); ?>"><?php echo esc_html($context['link']); ?> <?php echo ar_v21_icon('arrow-right'); ?></a></aside><?php endif; ?>
</article></div>
<section class="wrap article-end"><div class="share">
<?php if(get_post_status()==='publish'): $url=rawurlencode(get_permalink()); ?><span>Compartir:</span><a rel="noopener noreferrer" target="_blank" href="<?php echo esc_url('https://www.linkedin.com/sharing/share-offsite/?url='.$url); ?>">LinkedIn</a><a rel="noopener noreferrer" target="_blank" href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u='.$url); ?>">Facebook</a><a href="<?php echo esc_url('mailto:?subject='.rawurlencode(get_the_title()).'&body='.$url); ?>">Correo</a>
<?php else: ?><p class="meta">Borrador de revisión. Compartir se habilitará cuando el artículo esté publicado.</p><?php endif; ?></div><a class="text-link" href="<?php echo ar_url('blog/'); ?>"><?php echo ar_v21_icon('arrow-left'); ?> Volver al Blog</a><?php ar_v21_categories(); ?></section>
<?php get_template_part('template-parts/closing-cta',null,$context);
$related_args=['post_type'=>'post','post_status'=>ar_v21_editorial_review()?'draft':'publish','posts_per_page'=>3,'post__not_in'=>[get_the_ID()],'category__in'=>wp_get_post_categories(get_the_ID()),'no_found_rows'=>true,'ignore_sticky_posts'=>true];
if(ar_v21_editorial_review())$related_args['post__in']=ar_v21_editorial_ids();
$related=new WP_Query($related_args);
if($related->have_posts()): ?><section class="section-block soft"><div class="wrap"><h2>Continúa explorando</h2><div class="editorial-grid"><?php while($related->have_posts()):$related->the_post();get_template_part('template-parts/post-card');endwhile; ?></div></div></section><?php endif; wp_reset_postdata(); endwhile; get_footer(); ?>

