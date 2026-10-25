"""Repeatable presentation-only refinement of the approved isolated V2 prototype."""
from pathlib import Path
import subprocess,re,json,html

ROOT=Path(__file__).resolve().parent
REPO=ROOT.parents[1]
BASE='41c16322e3aae2f8c5304615e7f9631953fbad44'
PATHS={
 'arrow-up-right':'<path d="M6 18 18 6M6 6h12v12"/>',
 'arrow-right':'<path d="M4 12h16m-6-6 6 6-6 6"/>',
 'arrow-left':'<path d="M20 12H4m6-6-6 6 6 6"/>',
 'arrow-down':'<path d="M12 4v16m-6-6 6 6 6-6"/>',
 'chevron':'<path d="m6 9 6 6 6-6"/>',
 'menu':'<path d="M4 8h16"/><path d="M4 16h16"/>',
 'search':'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4M8 10.5h5m-2.5-2.5v5"/>',
 'users':'<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m1 3a5 5 0 0 1 4 5v1"/>',
 'compass':'<circle cx="12" cy="12" r="9"/><path d="m16 8-2.5 5.5L8 16l2.5-5.5L16 8Z"/>',
 'brief':'<rect x="4" y="5" width="16" height="16" rx="2"/><path d="M9 5V3h6v2M8 10h8M8 14h8M8 18h5"/>',
 'align':'<path d="M5 5h14M5 12h14M5 19h14"/><circle cx="9" cy="5" r="2"/><circle cx="15" cy="12" r="2"/><circle cx="10" cy="19" r="2"/>',
 'check':'<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
 'chat':'<path d="M5 4h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H9l-5 3v-3H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/><path d="M7 9h10M7 13h6"/>',
 'book':'<path d="M12 5v15M3 4h4a6 6 0 0 1 5 2 6 6 0 0 1 5-2h4v15h-4a6 6 0 0 0-5 2 6 6 0 0 0-5-2H3V4Z"/>',
 'list':'<path d="M9 6h12M9 12h12M9 18h12M3 6h1M3 12h1M3 18h1"/>',
 'info':'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
 'target':'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="M12 2v3M22 12h-3M12 22v-3M2 12h3"/>',
 'plus':'<path d="M5 12h14"/><path class="plus-vertical" d="M12 5v14"/>',
 'route':'<path d="M6 5h9a4 4 0 0 1 0 8H9a4 4 0 0 0 0 8h9"/><circle cx="5" cy="5" r="2"/><path d="m16 18 3 3-3 3"/>',
}
def icon(name,extra=''):
 return f'<svg class="icon icon-{name} {extra}" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{PATHS[name]}</svg>'
def badge(name):return '<span class="concept-icon" aria-hidden="true">'+icon(name)+'</span>'
def choose(s):
 s=html.unescape(re.sub('<[^>]+>',' ',s)).lower()
 for words,name in [(('pregunta','respuest','convers','contact'),'chat'),(('criteri','perfil','aline','condicion'),'align'),(('necesidad','context','reto','brief','información'),'brief'),(('liderazgo','búsqueda','identificar'),'search'),(('equipo','colabor','person','apoyo'),'users'),(('decisi','selecci','evalu'),'check'),(('pendiente','provisional','fast placement'),'info'),(('servicio','elegir','orient'),'compass')]:
  if any(w in s for w in words):return name
 return 'target'

stats=[]
for f in sorted((ROOT/'prototype').glob('*.html')):
 rel=f.relative_to(REPO).as_posix()
 s=subprocess.check_output(['git','show',BASE+':'+rel],cwd=REPO,text=True)
 s=s.replace('Propuesta visual 2.0','Propuesta visual 2.1').replace('· Propuesta 2.0','· Propuesta 2.1')
 s=s.replace('<link rel="stylesheet" href="assets/refinements.css">','<link rel="stylesheet" href="assets/refinements.css"><link rel="stylesheet" href="assets/refinement-v21.css">')
 s=s.replace('<script src="assets/interactions.js" defer>','<script src="assets/interactions.js?v=2.1" defer>')
 s=s.replace('../informe.html','../refinamiento-v21.html')
 # Only arrow glyphs, not copy, URLs, headings or form controls.
 bits=re.split(r'(<[^>]+>)',s)
 for i in range(0,len(bits),2):
  for glyph,name in [('↗','arrow-up-right'),('→','arrow-right'),('←','arrow-left'),('↓','arrow-down'),('⌄','chevron'),('☰','menu')]:bits[i]=bits[i].replace(glyph,icon(name))
 s=''.join(bits)
 s=re.sub(r'(<a\b[^>]*class="[^"]*(?:\bbutton\b|wp-block-button__link)[^"]*"[^>]*>)(.*?)</a>',lambda m:m[0] if '<svg ' in m[2] else m[1]+m[2]+icon('arrow-up-right')+'</a>',s,flags=re.S)
 # Semantic, decorative icons; links and accessible names remain the same.
 s=re.sub(r'(<div id="subnav" hidden>)(.*?)(</div>)',lambda m:m[1]+re.sub(r'(<a href="([^"]+)">)(.*?)</a>',lambda a:a[1]+icon({'headhunting.html':'search','personalizado.html':'users','fast-placement.html':'info'}[a[2]])+'<span>'+a[3]+'</span></a>',m[2])+m[3],s)
 if f.stem in ['servicios','headhunting','personalizado','fast-placement']:
  s=s.replace('class="nav-service"','class="nav-service is-current"')
  s=re.sub(r'(<div id="subnav" hidden>)(.*?)(</div>)',lambda m:m[1]+m[2].replace('<a href="'+f.name+'">','<a href="'+f.name+'" aria-current="page">')+m[3],s)
 s=re.sub(r'(<a class="service-row" href="([^"]+)"><span class="index">)(.*?)(</span>)',lambda m:m[1]+badge({'headhunting.html':'search','personalizado.html':'users','fast-placement.html':'info'}[m[2]])+'<span class="service-number">'+m[3]+'</span>'+m[4],s)
 s=re.sub(r'(<div class="section-heading">)(.*?)(<h2[^>]*>.*?</h2>)',lambda m:m[1]+badge(choose(m[3]))+m[2]+m[3],s,flags=re.S)
 # Do not modify any form markup, even in this disabled prototype.
 if f.stem not in ['contacto','candidatos','estados']:
  s=re.sub(r'(<div class="[^"]*\bar-card\b[^"]*">)(\s*)(<h3[^>]*>.*?</h3>)',lambda m:m[1]+m[2]+badge(choose(m[3]))+m[3],s,flags=re.S)
 s=re.sub(r'(<div class="hero-symbol"[^>]*>)',lambda m:m[1]+badge('info' if f.stem=='fast-placement' else 'compass'),s)
 if f.stem=='fast-placement':s=s.replace('<div><p class="eyebrow">ANTES DE CONTINUAR','<div>'+badge('info')+'<p class="eyebrow">ANTES DE CONTINUAR')
 s=re.sub(r'(<div class="reveal"><span>0([1-4])</span>)',lambda m:m[1]+badge(['brief','align','search','check'][int(m[2])-1]),s)
 s=s.replace('<div class="section-intro">','<div class="section-intro">'+badge('compass'))
 s=s.replace('<div class="archive-heading"><h2>','<div class="archive-heading"><h2>'+icon('book','heading-icon'))
 s=s.replace('<p class="article-deck">','<p class="article-deck">')
 s=s.replace('<summary>En este artículo</summary>','<summary>'+icon('list','toc-icon')+'En este artículo'+icon('plus','summary-mark')+'</summary>')
 s=re.sub(r'<summary((?:\s[^>]*)?)>(.*?)</summary>',lambda m:m[0] if 'summary-mark' in m[2] else '<summary'+m[1]+'>'+m[2]+icon('plus','summary-mark')+'</summary>',s,flags=re.S)
 s=s.replace('<div class="sidebar-cta">','<div class="sidebar-cta">'+badge('compass'))
 s=s.replace('<div class="wrap cta-grid"><div>','<div class="wrap cta-grid"><div>'+badge('chat'))
 s=re.sub(r'(<span class="state-mark"[^>]*>)∅</span>',lambda m:m[1]+icon('search')+'<span class="visually-hidden">∅</span></span>',s)
 s=re.sub(r'(<span class="state-mark"[^>]*>)404</span>',lambda m:m[1]+icon('compass')+'<span>404</span></span>',s)
 f.write_text(s)
 stats.append({'file':f.name,'inlineIcons':s.count('<svg '),'baseline':BASE})
(ROOT/'evidence/v2.1/icon-inventory.json').write_text(json.dumps(stats,indent=2))
print(json.dumps({'documents':len(stats),'icons':sum(x['inlineIcons'] for x in stats)}))
