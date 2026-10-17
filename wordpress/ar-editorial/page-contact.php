<?php /* Template Name: Contacto · Demostración sin envíos */ get_header(); while(have_posts()):the_post(); ar_page_hero('Solicitar propuesta · Estructura de desarrollo sin envíos reales.'); ?><section class="ar-page-content"><div class="container"><?php the_content(); if(ar_is_scaffold()) ar_demo_form(); ?></div></section><?php endwhile; get_footer(); ?>

