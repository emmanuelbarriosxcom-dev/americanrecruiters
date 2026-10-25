/* Progressive presentation only: no form handlers, tracking or network requests. */
(()=>{'use strict';
 const nav=document.querySelector('#nav'),menu=document.querySelector('.menu-toggle'),sub=document.querySelector('#subnav'),toggle=document.querySelector('.sub-toggle'),header=document.querySelector('.site-header');
 if(nav&&menu&&sub&&toggle){
  const closeSub=()=>{sub.hidden=true;toggle.setAttribute('aria-expanded','false')};
  const closeMenu=()=>{nav.classList.remove('is-open');menu.setAttribute('aria-expanded','false');closeSub()};
  menu.addEventListener('click',()=>{const state=!nav.classList.contains('is-open');nav.classList.toggle('is-open',state);menu.setAttribute('aria-expanded',String(state))});
  toggle.addEventListener('click',()=>{sub.hidden=!sub.hidden;toggle.setAttribute('aria-expanded',String(!sub.hidden))});
  toggle.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();sub.hidden=false;toggle.setAttribute('aria-expanded','true');sub.querySelector('a')?.focus()}});
  sub.addEventListener('keydown',e=>{const links=[...sub.querySelectorAll('a')],i=links.indexOf(document.activeElement);let next=null;if(e.key==='ArrowDown')next=(i+1)%links.length;if(e.key==='ArrowUp')next=(i-1+links.length)%links.length;if(e.key==='Home')next=0;if(e.key==='End')next=links.length-1;if(next!==null){e.preventDefault();links[next].focus()}});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'){if(!sub.hidden){closeSub();toggle.focus()}else if(nav.classList.contains('is-open')){closeMenu();menu.focus()}}});
  document.addEventListener('click',e=>{if(!e.target.closest('.site-header'))closeMenu()});
  header.addEventListener('focusout',e=>{if(!header.contains(e.relatedTarget))closeMenu()});
  matchMedia('(min-width:1001px)').addEventListener('change',closeMenu);
 }
 const toc=document.querySelector('.toc'),reading=document.querySelector('[data-toc]');
 if(toc&&reading){
  if(matchMedia('(max-width:1000px)').matches)toc.open=false;
  if('IntersectionObserver'in window){const io=new IntersectionObserver(entries=>{for(const e of entries)if(e.isIntersecting)toc.querySelectorAll('a').forEach(a=>a.classList.toggle('is-active',decodeURIComponent(a.hash.slice(1))===e.target.id))},{rootMargin:'-10% 0px -60% 0px'});reading.querySelectorAll('h2,h3').forEach(h=>io.observe(h))}
 }
 const preference=matchMedia('(prefers-reduced-motion:reduce)');let observer;
 const start=()=>{observer?.disconnect();document.querySelectorAll('.motion-in').forEach(e=>e.classList.remove('motion-in'));if(preference.matches||!('IntersectionObserver'in window))return;
  const targets=[...document.querySelectorAll('.section-heading,.section-intro,.service-row,.ar-card,.steps-line>div,.editorial-card,.split-photo>figure,.cta-grid>div')];
  targets.forEach(el=>el.style.setProperty('--reveal-delay',Math.min([...el.parentElement.children].indexOf(el)*65,195)+'ms'));
  observer=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('motion-in');observer.unobserve(e.target)}}),{threshold:.12});targets.forEach(el=>observer.observe(el));
 };start();preference.addEventListener('change',start);
})();
