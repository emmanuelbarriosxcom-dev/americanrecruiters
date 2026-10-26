<?php if(!defined('ABSPATH'))exit; ?>
<aside class="reading-aside">
<?php if($args['toc']): ?><details class="toc" open><summary><?php echo ar_v21_icon('list','toc-icon'); ?>En este artículo<?php echo ar_v21_icon('plus','summary-mark'); ?></summary><nav aria-label="Tabla de contenidos"><?php foreach($args['toc'] as $item): ?><a<?php if($item['level']==='h3')echo ' class="sub"'; ?> href="#<?php echo esc_attr($item['id']); ?>"><?php echo esc_html($item['text']); ?></a><?php endforeach; ?></nav></details><?php endif; ?>
</aside>
