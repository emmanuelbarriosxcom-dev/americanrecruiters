"""Presentation-only closing pass, repeatable over V2.1. Never touches WordPress."""
from pathlib import Path
import re
R=Path(__file__).parent
for path in (R/'prototype').glob('*.html'):
    text=path.read_text()
    text=re.sub(r'assets/refinement-v21\.css(?:\?[^"\s]*)?', 'assets/refinement-v21.css?v=final20261025', text)
    path.write_text(text)
report=R/'refinamiento-v21.html'
text=report.read_text()
notice='<aside style="padding:22px;background:#ddf4f3;color:#26222d;text-align:center"><strong>Cierre visual final disponible.</strong> <a href="cierre-v21.html">Ver ajustes finales, comparativas y QA</a>. El informe siguiente documenta la primera ronda V2.1.</aside>'
if 'href="cierre-v21.html"' not in text:
    report.write_text(text.replace('<body>','<body>'+notice,1))
for name in ['servicios','headhunting','personalizado','nosotros','contacto']:
    path=R/'prototype'/f'{name}.html'
    text=path.read_text()
    if name=='contacto':
        text=text.replace(' editorial-close','')
        text=text.replace('href="/contacto/#h-cuentanos-que-necesitas"','href="#h-cuentanos-que-necesitas"')
    sections=list(re.finditer(r'<section class="section-block[^"]*"',text))
    target=sections[-1]
    if name=='contacto':
        target=next(m for m in sections if 'id="h-buscas-enviar-tu-curriculum"' in text[m.start():text.index('</section>',m.end())])
    tag=target.group()
    if 'editorial-close' not in tag:
        text=text[:target.start()]+tag[:-1]+' editorial-close"'+text[target.end():]
    path.write_text(text)
