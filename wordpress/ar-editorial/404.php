<?php
/** Native 404: do not render the Blog loop for an unknown URL. */
get_header();
ar_hero('Página no encontrada', '', true);
?>
<section class="ar-page-content"><div class="container ar-reading">
<p>La dirección solicitada no está disponible.</p>
<p><a href="<?php echo ar_url(); ?>">Volver al inicio</a> · <a href="<?php echo ar_url('servicios/'); ?>">Consultar servicios</a></p>
</div></section>
<?php get_footer(); ?>
