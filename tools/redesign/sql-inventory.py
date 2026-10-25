"""Read only a local WPvivid backup; emit a private baseline without credentials."""
import argparse,re,json,pathlib,hashlib
parser=argparse.ArgumentParser()
parser.add_argument('--output-dir',default='/Users/emmanuelbarrios/Documents/Codex/AR-AC-private-20261025')
parser.add_argument('--quiet',action='store_true')
args=parser.parse_args()
base=pathlib.Path(args.output_dir)
s=(base/'before.sql').read_text()
def rows(table):
 for match in re.finditer(r'INSERT INTO `'+table+r'` VALUES (.*?);\n',s,re.S):
  text=match.group(1);i=0
  while i<len(text):
   if text[i]!='(':i+=1;continue
   i+=1;row=[]
   while i<len(text):
    if text[i]=="'":
     i+=1;value=''
     while i<len(text):
      if text[i]=='\\':i+=1;value+={'n':'\n','r':'\r','t':'\t','0':'\0'}.get(text[i],text[i]);i+=1
      elif text[i]=="'":i+=1;break
      else:value+=text[i];i+=1
    else:
     j=i
     while i<len(text) and text[i] not in ',)':i+=1
     value=text[j:i]
    row.append(value)
    if text[i]==')':i+=1;break
    i+=1
   yield row
posts=[dict(zip(['ID','author','date','date_gmt','content','title','excerpt','status','comment_status','ping_status','password','slug','to_ping','pinged','modified','modified_gmt','filtered','parent','guid','order','type','mime','comments'],r)) for r in rows('wpvx_posts')]
posts=[p for p in posts if p['type'] in ['page','post'] and p['status']!='auto-draft']
meta=[r for r in rows('wpvx_postmeta') if r[1] in [p['ID'] for p in posts] and (r[2].startswith('_yoast') or r[2] in ['_thumbnail_id','_wp_page_template','_ar_source_url'])]
options={r[1]:r[2] for r in rows('wpvx_options') if r[1] in ['template','stylesheet','blog_public','active_plugins','page_on_front','page_for_posts'] or r[1].startswith('ar_secure')}
attachment_alt={r[1]:r[3] for r in rows('wpvx_postmeta') if r[2]=='_wp_attachment_image_alt'}
(base/'baseline.json').write_text(json.dumps({'posts':posts,'meta':meta,'options':options,'attachment_alt':attachment_alt},ensure_ascii=False,indent=2))
print(json.dumps({'posts_count':len(posts)} if args.quiet else {'posts':[{k:p[k] for k in ['ID','title','slug','status','type']} for p in posts],'options':options},ensure_ascii=False))
