<?php
/** Render the saved Gutenberg Home through approved shared components. */
function ar_v21_home() {
 $html=apply_filters('the_content',get_the_content());$parsed=ar_v21_dom(ar_v21_presentation($html));
 if(!$parsed){echo $html;return;}[$doc,$xp]=$parsed;
 $home=$xp->query('//*['.ar_v21_class('ar-home').']')->item(0);if(!$home){echo $html;return;}
 foreach(ar_v21_nodes($home,'./section|./div') as $section){
  $id=$section->getAttribute('id');$container=ar_v21_find($section,'container') ?: $section;
  $h2=ar_v21_first($container,'h2');
  if($id==='inicio'){
   $inner=ar_v21_find($section,'hero-inner');$image=ar_v21_first($section,'img');
   if($image){$image->setAttribute('loading','eager');$image->setAttribute('fetchpriority','high');}
   $foot=ar_v21_find($inner,'hero-bottom');if($foot)$foot->setAttribute('class','hero-foot');
   echo '<section id="inicio" class="hero home-hero"><div class="hero-photo">'.ar_v21_html($image).'</div><div class="wrap home-hero-content">'.ar_v21_decorate(ar_v21_html($inner,true)).'</div></section>';continue;
  }
  if(strpos($section->getAttribute('class'),'ticker')!==false){echo '<div class="brand-strip"><div class="wrap">'.ar_v21_html(ar_v21_find($section,'ar-ticker-copy'),true).'</div></div>';continue;}
  if($id==='servicios'){
   $cards=ar_v21_find($section,'ar-home-services');
   if(!$cards){echo ar_v21_html($section);continue;}
   $cards->parentNode->removeChild($cards);
   echo '<section id="servicios" class="section-block"><div class="wrap"><div class="section-intro">'.ar_v21_html($container,true).'</div><div class="service-list">';
   foreach(ar_v21_nodes($cards,'.//*['.ar_v21_class('ar-home-service').']') as $i=>$card){
    $a=ar_v21_first($card,'a');$h=ar_v21_first($card,'h3');$ps=ar_v21_nodes($card,'.//p[not('.ar_v21_class('svc-num').') and not('.ar_v21_class('ar-text-link').')]');
    echo '<a class="service-row" href="'.esc_url($a?$a->getAttribute('href'):ar_url('servicios/')).'"><span class="index">'.ar_v21_badge(['search','users','info'][$i]??'compass').'<span class="service-number">'.esc_html(sprintf('%02d',$i+1)).'</span></span>'.ar_v21_html($h).ar_v21_html($ps[0]??null).'<span class="arrow" aria-hidden="true">'.ar_v21_icon('arrow-up-right').'</span></a>';
   }echo '</div></div></section>';continue;
  }
  if($id==='enfoque'){
   $statement=ar_v21_find($section,'statement-inner') ?: $container;
   echo '<section id="enfoque" class="statement soft"><div class="wrap statement-grid"><span class="quote" aria-hidden="true">“</span><div>'.ar_v21_decorate(ar_v21_html($statement,true)).'</div></div></section>';continue;
  }
  if($id==='necesidades'){
   $original_image=ar_v21_first($section,'img');$alt=$original_image?$original_image->getAttribute('alt'):'Fotografía editorial ilustrativa de profesionales conversando.';
   $copy=ar_v21_find($section,'ar-needs-copy');if(!$copy)$copy=$container;
   foreach(ar_v21_nodes($copy,'.//a') as $a){$a->setAttribute('class','route-row');}
   foreach(ar_v21_nodes($copy,'.//figure|.//*['.ar_v21_class('photo-note').']') as $n)$n->parentNode->removeChild($n);
   echo '<section id="necesidades" class="section-block"><div class="wrap split-photo"><figure><img src="'.esc_url(get_theme_file_uri('/assets/images/editorial-conversacion.webp')).'" width="1200" height="900" loading="lazy" alt="'.esc_attr($alt).'"><figcaption>Fotografía editorial ilustrativa.</figcaption></figure><div>'.ar_v21_decorate(ar_v21_html($copy,true)).'</div></div></section>';continue;
  }
  if($id==='posiciones-clave'){
   echo '<section id="posiciones-clave" class="section-block soft"><div class="wrap section-grid"><div>'.ar_v21_html(ar_v21_find($section,'section-head')).'</div><div class="executive-aside">'.ar_v21_decorate(ar_v21_html(ar_v21_find($section,'ar-executive-aside'))).'</div></div></section>';continue;
  }
  if($id==='metodologia'){
   $track=ar_v21_find($section,'process-track');$steps=ar_v21_nodes($track,'.//*['.ar_v21_class('process-step').']');$track->parentNode->removeChild($track);
   $buttons=ar_v21_find($container,'wp-block-buttons');if($buttons)$buttons->parentNode->removeChild($buttons);
   echo '<section id="metodologia" class="section-block ink"><div class="wrap"><div class="section-intro">'.ar_v21_badge('compass').ar_v21_html($container,true).'</div><div class="steps-line">';
   foreach($steps as $i=>$step){$number=ar_v21_find($step,'ar-step-number');if($number)$number->parentNode->removeChild($number);echo '<div><span>'.esc_html(sprintf('%02d',$i+1)).'</span>'.ar_v21_badge(['brief','align','search','check'][$i]??'check').ar_v21_html($step,true).'</div>';}
   echo '</div>'.ar_v21_decorate(ar_v21_html($buttons)).'</div></section>';continue;
  }
  if($id==='perspectivas'){
   echo '<section id="perspectivas" class="section-block"><div class="wrap">';
   // Keep the existing, genuine external articles until this site's drafts are published.
   foreach(ar_v21_nodes($container,'.//*['.ar_v21_class('ar-insight-grid').']') as $grid)$grid->setAttribute('class','editorial-grid');
   foreach(ar_v21_nodes($container,'.//article') as $card)$card->setAttribute('class','editorial-card legacy-card');
   echo ar_v21_decorate(ar_v21_html($container,true)).'</div></section>';continue;
  }
  if($id==='preguntas'){
   $faq=ar_v21_find($section,'faq');if($faq)$faq->parentNode->removeChild($faq);
   echo '<section id="preguntas" class="section-block soft"><div class="wrap section-grid"><div>'.ar_v21_html($container,true).'</div><div>'.ar_v21_decorate(ar_v21_html($faq,true)).'</div></div></section>';continue;
  }
  if($id==='contacto'){
   $candidate=ar_v21_find($section,'ar-candidates-note');if($candidate)$candidate->parentNode->removeChild($candidate);
   $cta=ar_v21_find($section,'ar-final-cta') ?: $container;$h=ar_v21_first($cta,'h2');$heading=ar_v21_html($h);if($h)$h->parentNode->removeChild($h);
   echo '<section id="contacto" class="cta-band"><div class="wrap cta-grid"><div>'.ar_v21_badge('chat').$heading.'</div><div>'.ar_v21_decorate(ar_v21_html($cta,true)).'</div></div></section><div class="wrap candidate-route">'.ar_v21_html($candidate).'</div>';continue;
  }
  echo ar_v21_html($section);
 }
}
