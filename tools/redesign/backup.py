"""Preserve verified WPvivid archive privately; publish only hashes and counts."""
import argparse, hashlib, io, json, pathlib, shutil, zipfile
parser=argparse.ArgumentParser()
parser.add_argument('--output-dir',default='/Users/emmanuelbarrios/Documents/Codex/AR-AC-private-20261025')
parser.add_argument('--source',default='/Users/emmanuelbarrios/Downloads/desarrollo.americanrecruiters.mx_wpvivid-b5b68a57e7503_2026-10-25-14-34_backup_all.zip')
parser.add_argument('--theme-version',default='1.0.5-rc.2')
args=parser.parse_args()
base=pathlib.Path(args.output_dir)
base.mkdir(exist_ok=True,mode=0o700)
src=pathlib.Path(args.source)
dst=base/src.name
if not dst.exists(): shutil.copy2(src,dst)
manifest={'archive':dst.name,'sha256':hashlib.sha256(dst.read_bytes()).hexdigest(),'parts':{}}
with zipfile.ZipFile(dst) as outer:
 assert outer.testzip() is None
 for name in outer.namelist():
  if not name.endswith('.zip'): continue
  data=outer.read(name)
  with zipfile.ZipFile(io.BytesIO(data)) as inner:
   assert inner.testzip() is None
   manifest['parts'][name]={'bytes':len(data),'entries':len(inner.namelist()),'crc':'PASS'}
   if '_db.zip' in name:
    sql=inner.read(next(n for n in inner.namelist() if n.endswith('.sql')))
    (base/'before.sql').write_bytes(sql)
   if '_themes.zip' in name:
    with zipfile.ZipFile(base/f'ar-editorial-{args.theme_version}-verified-rollback.zip','w',zipfile.ZIP_DEFLATED) as rollback:
     for n in inner.namelist():
      if n.startswith('themes/ar-editorial/') and not n.endswith('/'):
       rollback.writestr(n.removeprefix('themes/'),inner.read(n))
   if '_plugin.zip' in name:
    manifest['forms_plugin_files']={n:hashlib.sha256(inner.read(n)).hexdigest() for n in inner.namelist() if n.startswith('plugins/ar-secure-forms/') and not n.endswith('/')}
(base/'backup-manifest.json').write_text(json.dumps(manifest,indent=2))
print(json.dumps({'private_backup':str(dst),'sha256':manifest['sha256'],'parts':len(manifest['parts']),'forms_plugin_files':len(manifest['forms_plugin_files']),'rollback':str(base/f'ar-editorial-{args.theme_version}-verified-rollback.zip')}))
