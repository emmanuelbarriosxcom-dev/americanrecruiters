import urllib.request,urllib.error,re,json,hashlib
from pathlib import Path
base='https://desarrollo.americanrecruiters.mx'
paths=['/','/servicios/','/servicios/headhunting/','/servicios/reclutamiento-personalizado/','/servicios/fast-placement/','/metodologia/','/nosotros/','/blog/','/contacto/','/candidatos/','/preguntas-frecuentes/','/privacidad/','/ar-qa-missing-20261023/']
report=[]
for path in paths:
    try:
        r=urllib.request.urlopen(base+path,timeout=30); status=r.status;html=r.read().decode('utf8','replace')
    except urllib.error.HTTPError as e:status=e.code;html=e.read().decode('utf8','replace')
    row={'url':base+path,'status':status,'h1':re.findall(r'<h1\b[^>]*>(.*?)</h1>',html,re.S),'canonicals':re.findall(r'<link[^>]+rel=["\']canonical["\'][^>]*>',html),'robots':re.findall(r'<meta[^>]+name=["\']robots["\'][^>]*>',html),'forms':len(re.findall(r'<form\b',html)),'enabled_submit':len(re.findall(r'<button(?![^>]*disabled)[^>]*type=["\']submit',html)),'images':re.findall(r'<img[^>]+src=["\']([^"\']+)',html)}
    report.append(row)
assets=[]
for path in ['style.css','assets/css/design-system.css','assets/js/navigation.js','assets/images/american-recruiters-logo.png']:
    try:data=urllib.request.urlopen(base+'/wp-content/themes/ar-editorial/'+path,timeout=30).read()
    except Exception as e:
        assets.append({'path':path,'error':str(e)});continue
    local=Path('wordpress/ar-editorial',path).read_bytes()
    assets.append({'path':path,'public_sha256':hashlib.sha256(data).hexdigest(),'rc_sha256':hashlib.sha256(local).hexdigest(),'equal':data==local})
for url in ['https://www.americanrecruiters.com.mx/','https://emmanuelbarriosxcom-dev.github.io/americanrecruiters/diseno/']:
    try:
        r=urllib.request.urlopen(url,timeout=30);html=r.read().decode('utf8','replace');report.append({'url':url,'status':r.status,'title':re.findall(r'<title>(.*?)</title>',html,re.S)})
    except Exception as e:report.append({'url':url,'error':str(e)})
Path('release/qa/public-readonly.json').write_text(json.dumps({'pages':report,'assets':assets},indent=2,ensure_ascii=False))
print(json.dumps({'pages':[(r['url'],r.get('status'),len(r.get('h1',[]))) for r in report],'assets':assets},indent=2))
