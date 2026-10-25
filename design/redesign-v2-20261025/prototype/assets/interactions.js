// Progressive enhancement only. No analytics, forms, requests or libraries.
const nav=document.querySelector('#nav'),menu=document.querySelector('.menu-toggle'),sub=document.querySelector('#subnav'),toggle=document.querySelector('.sub-toggle');
const closeSub=()=>{sub.hidden=true;toggle.setAttribute('aria-expanded','false')};
const closeMenu=()=>{nav.classList.remove('is-open');menu.setAttribute('aria-expanded','false');closeSub()};
menu.addEventListener('click',()=>{const state=!nav.classList.contains('is-open');nav.classList.toggle('is-open',state);menu.setAttribute('aria-expanded',String(state))});
toggle.addEventListener('click',()=>{sub.hidden=!sub.hidden;toggle.setAttribute('aria-expanded',String(!sub.hidden))});
toggle.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();sub.hidden=false;toggle.setAttribute('aria-expanded','true');sub.querySelector('a').focus()}});
sub.addEventListener('keydown',e=>{const links=[...sub.querySelectorAll('a')],i=links.indexOf(document.activeElement);let next=null;if(e.key==='ArrowDown')next=(i+1)%links.length;if(e.key==='ArrowUp')next=(i-1+links.length)%links.length;if(e.key==='Home')next=0;if(e.key==='End')next=links.length-1;if(next!==null){e.preventDefault();links[next].focus()}});
document.addEventListener('keydown',e=>{if(e.key==='Escape'){if(!sub.hidden){closeSub();toggle.focus()}else if(nav.classList.contains('is-open')){closeMenu();menu.focus()}}});
document.addEventListener('click',e=>{if(!e.target.closest('.site-header'))closeMenu()});
document.querySelector('.site-header').addEventListener('focusout',e=>{if(!e.currentTarget.contains(e.relatedTarget))closeMenu()});
matchMedia('(min-width:1001px)').addEventListener('change',closeMenu);
const reading=document.querySelector('[data-toc]'),toc=document.querySelector('.toc nav');
if(reading&&toc){
 const heads=[...reading.querySelectorAll('h2,h3')],used=new Set([...document.querySelectorAll('[id]')].map(x=>x.id));
 heads.forEach((h,i)=>{if(!h.id){let id='contenido-'+(i+1);while(used.has(id))id+='-seccion';h.id=id;used.add(id)}const a=document.createElement('a');a.href='#'+h.id;a.textContent=h.textContent;a.classList.toggle('sub',h.tagName==='H3');toc.append(a)});
 if(!heads.length){document.querySelector('.toc').hidden=true}
 if(matchMedia('(max-width:1000px)').matches)document.querySelector('.toc').open=false;
 const middle=heads.filter(h=>h.tagName==='H2')[2];if(middle){const c=document.createElement('aside');c.className='inline-cta';c.innerHTML='<p><strong>¿Cómo se relaciona esto con tu empresa?</strong></p><p>Consulta el servicio de búsqueda ejecutiva y prepara el contexto de tu necesidad.</p><a class="text-link" href="headhunting.html">Conocer Headhunting <svg class="icon icon-arrow-right" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M4 12h16m-6-6 6 6-6 6"/></svg></a>';middle.before(c)}
 reading.querySelectorAll('table').forEach(t=>{const w=document.createElement('div');w.className='table-scroll';w.tabIndex=0;w.setAttribute('role','region');w.setAttribute('aria-label','Tabla desplazable horizontalmente');t.before(w);w.append(t)});
 if('IntersectionObserver'in window){const io=new IntersectionObserver(es=>{for(const e of es)if(e.isIntersecting){toc.querySelectorAll('a').forEach(a=>a.classList.toggle('is-active',a.hash==='#'+e.target.id))}},{rootMargin:'-10% 0px -60% 0px'});heads.forEach(h=>io.observe(h))}
}
// Manual reduced-motion QA is an explicit simulation, never a replacement for OS testing.
const motionPreference=matchMedia('(prefers-reduced-motion:reduce)');
const motionSimulation=new URLSearchParams(location.search).get('motion')==='reduce';
if(motionSimulation)document.documentElement.dataset.motion='reduce';
let motionObserver;
const stopMotion=()=>{motionObserver?.disconnect();document.querySelectorAll('.motion-in').forEach(e=>e.classList.remove('motion-in'))};
const startMotion=()=>{
 stopMotion();if(motionPreference.matches||motionSimulation||!('IntersectionObserver'in window))return;
 const targets=[...document.querySelectorAll('.section-heading,.section-intro,.service-row,.ar-card,.steps-line>div,.editorial-card,.split-photo>figure,.cta-grid>div,.empty')];
 targets.forEach(el=>{const group=[...el.parentElement.children].filter(n=>targets.includes(n));el.style.setProperty('--reveal-delay',Math.min(group.indexOf(el)*65,195)+'ms')});
 motionObserver=new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('motion-in');motionObserver.unobserve(e.target)}})},{threshold:.12});
 targets.forEach(el=>motionObserver.observe(el));
};
startMotion();motionPreference.addEventListener('change',startMotion);
// Disabled prototypes must never submit, even if a keyboard attempts it.
document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',e=>e.preventDefault()));
