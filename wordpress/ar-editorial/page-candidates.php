<?php /* Template Name: Candidatos · Demostración sin envíos */ get_header(); while(have_posts()):the_post(); ar_page_hero('Espacio para candidatos · Flujo independiente de las solicitudes empresariales.'); ?><section class="ar-page-content"><div class="container"><?php the_content(); if(ar_is_scaffold()) ar_demo_form(true); ?></div></section><?php endwhile; get_footer(); ?>

