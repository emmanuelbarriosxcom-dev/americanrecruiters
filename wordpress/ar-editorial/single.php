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
</article></div>
<section class="wrap article-end">
<aside class="article-author" aria-labelledby="article-editorial-name">
<span class="article-author-avatar"><img src="<?php echo ar_logo(); ?>" width="1472" height="200" loading="lazy" decoding="async" alt="American Recruiters"></span>
<div><p class="article-author-label">Edición institucional</p><h2 id="article-editorial-name">Equipo editorial de American Recruiters</h2><p>Perspectivas de American Recruiters México sobre headhunting, reclutamiento y decisiones de contratación para empresas.</p></div>
</aside>
<div class="share" role="group" aria-labelledby="article-share-label">
<?php if(get_post_status()==='publish'): $url=rawurlencode(get_permalink()); ?><span id="article-share-label" class="share-label">Compartir</span><div class="share-options"><a rel="noopener noreferrer" target="_blank" href="<?php echo esc_url('https://www.linkedin.com/sharing/share-offsite/?url='.$url); ?>"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="currentColor"><path d="M5.37 2a2.37 2.37 0 1 0 0 4.74 2.37 2.37 0 0 0 0-4.74ZM3.5 8.5h3.75V22H3.5V8.5Zm6.25 0h3.6v1.84h.05C13.9 9.4 15.13 8.4 16.97 8.4c3.82 0 4.53 2.51 4.53 5.77V22h-3.75v-6.94c0-1.65-.03-3.78-2.31-3.78-2.31 0-2.66 1.8-2.66 3.66V22H9.75V8.5Z"/></svg><span>LinkedIn</span><span class="screen-reader-text"> (abre una pestaña nueva)</span></a><a rel="noopener noreferrer" target="_blank" href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u='.$url); ?>"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="currentColor"><path d="M13.5 22v-9h3l.5-3.5h-3.5V7.25c0-1 .35-1.75 1.8-1.75H17V2.37A23 23 0 0 0 14.52 2C12.06 2 10 3.5 10 6.75V9.5H7V13h3v9h3.5Z"/></svg><span>Facebook</span><span class="screen-reader-text"> (abre una pestaña nueva)</span></a><!--email_off--><a href="<?php echo esc_url('mailto:?subject='.rawurlencode(get_the_title()).'&body='.$url); ?>"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 6 8 6 8-6"/></svg><span>Correo</span></a><!--/email_off--></div>
<?php else: ?><p class="meta">Borrador de revisión. Compartir se habilitará cuando el artículo esté publicado.</p><?php endif; ?></div><a class="text-link" href="<?php echo ar_url('blog/'); ?>"><?php echo ar_v21_icon('arrow-left'); ?> Volver al Blog</a><?php ar_v21_categories(); ?></section>
<?php get_template_part('template-parts/closing-cta',null,$context);
$related_args=['post_type'=>'post','post_status'=>ar_v21_editorial_review()?'draft':'publish','posts_per_page'=>3,'post__not_in'=>[get_the_ID()],'category__in'=>wp_get_post_categories(get_the_ID()),'no_found_rows'=>true,'ignore_sticky_posts'=>true];
if(ar_v21_editorial_review())$related_args['post__in']=ar_v21_editorial_ids();
$related=new WP_Query($related_args);
if($related->have_posts()): ?><section class="section-block soft"><div class="wrap"><h2>Continúa explorando</h2><div class="editorial-grid"><?php while($related->have_posts()):$related->the_post();get_template_part('template-parts/post-card');endwhile; ?></div></div></section><?php endif; wp_reset_postdata(); endwhile; get_footer(); ?>

