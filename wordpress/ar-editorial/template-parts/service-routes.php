<?php if(!defined('ABSPATH'))exit; ?>
<div class="service-list"><?php foreach($args['routes'] as $i=>$route): ?>
<a class="service-row" href="<?php echo esc_url($route['href']); ?>"><span class="index"><?php echo ar_v21_badge($route['icon']); ?><span class="service-number"><?php echo esc_html(sprintf('%02d',$i+1)); ?></span></span><h3><?php echo esc_html($route['title']); ?></h3><p><?php echo esc_html($route['description']); ?></p><span class="arrow" aria-hidden="true"><?php echo ar_v21_icon('arrow-up-right'); ?></span></a>
<?php endforeach; ?></div>
