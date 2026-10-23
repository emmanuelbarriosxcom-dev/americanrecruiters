from pathlib import Path
import hashlib,json,zipfile
root=Path(__file__).resolve().parents[2]
theme=root/'wordpress/ar-editorial';out=root/'release';out.mkdir(exist_ok=True)
files=sorted(p for p in theme.rglob('*') if p.is_file())
required=['style.css','index.php','functions.php','front-page.php','home.php','single.php','404.php','header.php','footer.php','theme.json','assets/js/navigation.js','assets/css/design-system.css','assets/images/american-recruiters-logo.png']
assert all((theme/p).is_file() for p in required)
assert 'Version: 1.0.5-rc.1' in (theme/'style.css').read_text()
json.loads((theme/'theme.json').read_text())
manifest=[]
for p in files:
    assert not p.is_symlink()
    assert p.suffix in ['.php','.css','.js','.json','.png']
    if p.suffix!='.png':
        text=p.read_text();assert not any(x in text for x in ['/Users/','/home/','DB_PASSWORD','BEGIN PRIVATE KEY','AKIA'])
    manifest.append({'path':'ar-editorial/'+str(p.relative_to(theme)),'bytes':p.stat().st_size,'sha256':hashlib.sha256(p.read_bytes()).hexdigest()})
name='ar-editorial-1.0.5-rc.1.zip';archive=out/name
with zipfile.ZipFile(archive,'w',zipfile.ZIP_DEFLATED,compresslevel=9) as z:
    for p,m in zip(files,manifest):
        info=zipfile.ZipInfo(m['path'],date_time=(2026,10,23,0,0,0));info.external_attr=0o100644<<16;info.compress_type=zipfile.ZIP_DEFLATED;z.writestr(info,p.read_bytes())
with zipfile.ZipFile(archive) as z:
    assert z.testzip() is None
    assert set(z.namelist())=={m['path'] for m in manifest}
    for m in manifest:assert hashlib.sha256(z.read(m['path'])).hexdigest()==m['sha256']
sha=hashlib.sha256(archive.read_bytes()).hexdigest()
(out/'SHA256SUMS').write_text(sha+'  '+name+'\n')
(out/'manifest.json').write_text(json.dumps({'version':'1.0.5-rc.1','files':manifest},indent=2)+'\n')
(root/'MANIFEST.md').write_text('# Manifest — 1.0.5-rc.1\n\nZIP: `release/'+name+'`\n\nSHA-256: `'+sha+'`\n\n'+str(len(files))+' archivos. Una raíz `ar-editorial/`. Sin WordPress, plugins, base de datos ni uploads.\n\n| Archivo | Bytes | SHA-256 |\n|---|---:|---|\n'+'\n'.join('| '+m['path']+' | '+str(m['bytes'])+' | `'+m['sha256']+'` |' for m in manifest)+'\n\nConstrucción determinista: `python3 tools/release/build.py`. El ZIP se verifica contra cada hash antes de finalizar.\n')
print(json.dumps({'zip':str(archive),'files':len(files),'sha256':sha},indent=2))
