<?php
/** Home: contenido modular editable en Gutenberg. */
// Canonical propio, único, incluso con el noindex del entorno de desarrollo.
add_filter('wpseo_canonical', '__return_false');
// Core also emits a canonical without Yoast. Keep this fallback unique.
remove_action('wp_head', 'rel_canonical');
add_action('wp_head', static function () {
    echo '<link rel="canonical" href="' . esc_url(home_url('/')) . '" />' . "\n";
}, 20);
get_header();
while (have_posts()) : the_post();
    the_content();
endwhile;
get_footer();

