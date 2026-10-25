from pathlib import Path
from html.parser import HTMLParser
from urllib.parse import urlparse,unquote
import re,json,subprocess,hashlib,sys
R=Path(__file__).parent;repo=R.parents[1];base=sys.argv[1] if len(sys.argv)>1 else '41c16322e3aae2f8c5304615e7f9631953fbad44'
class Parse(HTMLParser):
 def __init__(self,s):super().__init__();self.main=False;self.text=[];self.links=[];self.ids=[];self.svgs=[];self.feed(s)
 def handle_starttag(self,t,a):
  d=dict(a)
  if t=='main':self.main=True
  if 'id'in d:self.ids.append(d['id'])
  if t=='a':self.links.append(d.get('href',''))
  if t=='svg':self.svgs.append(d)
 def handle_endtag(self,t):
  if t=='main':self.main=False
 def handle_data(self,s):
  if self.main:self.text.append(s)
def normalized(p):return re.sub(r'\s+',' ',re.sub('[↗→←↓⌄☰]','',' '.join(p.text))).strip()
results=[];broken=[]
for f in sorted((R/'prototype').glob('*.html')):
 before=subprocess.check_output(['git','show',base+':'+f.relative_to(repo).as_posix()],cwd=repo,text=True);after=f.read_text();a,b=Parse(before),Parse(after)
 for h in b.links:
  u=urlparse(h)
  if u.scheme or u.netloc or h.startswith('/'):continue
  target=f.parent/unquote(u.path) if u.path else f
  if not target.exists():broken.append({'file':f.name,'href':h})
  elif u.fragment and u.fragment not in Parse(target.read_text()).ids:broken.append({'file':f.name,'href':h})
 forms=lambda s:re.findall(r'<form\b.*?</form>',s,re.S)
 expected=[x.replace('../informe.html','../refinamiento-v21.html') for x in a.links]
 if f.name=='contacto.html':expected=[x.replace('/contacto/#h-cuentanos-que-necesitas','#h-cuentanos-que-necesitas') for x in expected]
 results.append({'file':f.name,'mainCopyUnchanged':normalized(a)==normalized(b),'linksMatchApprovedMapping':expected==b.links,'localGuideAnchorRemapped':f.name=='contacto.html','formsByteIdentical':forms(before)==forms(after),'duplicateIds':len(b.ids)-len(set(b.ids)),'decorativeSvgValid':all(x.get('aria-hidden')=='true' and x.get('focusable')=='false' and x.get('viewbox')=='0 0 24 24' for x in b.svgs),'icons':len(b.svgs)})
out={'baseline':base,'documents':len(results),'brokenLocalLinks':broken,'results':results,'cssBytes':sum(p.stat().st_size for p in (R/'prototype/assets').glob('*.css')),'jsBytes':(R/'prototype/assets/interactions.js').stat().st_size}
(R/(sys.argv[2] if len(sys.argv)>2 else 'evidence/v2.1/static-validation.json')).write_text(json.dumps(out,indent=2,ensure_ascii=False))
print(json.dumps(out,ensure_ascii=False))
