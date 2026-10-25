"""Compare private pre-change SQL inventory with a user-authorized WXR export."""
import argparse,json,pathlib,xml.etree.ElementTree as ET
p=argparse.ArgumentParser();p.add_argument('directory');args=p.parse_args()
base=pathlib.Path(args.directory);before=json.loads((base/'baseline.json').read_text())
ns={'wp':'http://wordpress.org/export/1.2/','content':'http://purl.org/rss/1.0/modules/content/','excerpt':'http://wordpress.org/export/1.2/excerpt/','dc':'http://purl.org/dc/elements/1.1/'}
channel=ET.parse(base/'after-content.xml').getroot().find('channel')
authors={e.findtext('wp:author_login',namespaces=ns):e.findtext('wp:author_id',namespaces=ns) for e in channel.findall('wp:author',ns)}
items={e.findtext('wp:post_id',namespaces=ns):e for e in channel.findall('item')}
def norm(s):return (s or '').replace('\r\n','\n').replace('\r','\n')
fields={'title':'title','slug':'wp:post_name','status':'wp:status','date':'wp:post_date','date_gmt':'wp:post_date_gmt','excerpt':'excerpt:encoded','parent':'wp:post_parent','type':'wp:post_type','content':'content:encoded'}
result={'compared':0,'unchanged_identity_fields':True,'expected_body_changes':[],'unexpected_changes':[],'critical_meta_changes':[],'attachment_alt_changes':[],'editorial_drafts':[]}
old_meta={(r[1],r[2]):r[3] for r in before['meta']}
for post in before['posts']:
 if post['status']=='trash':continue
 post_id=post['ID'];item=items.get(post_id);result['compared']+=1
 if item is None:result['unexpected_changes'].append([post_id,'missing']);continue
 for name,path in fields.items():
  actual=item.findtext(path,default='',namespaces=ns)
  if norm(post[name])!=norm(actual):
   if name=='content' and post_id in ['15','19']:result['expected_body_changes'].append(post_id)
   else:result['unexpected_changes'].append([post_id,name])
 if post['author']!=authors.get(item.findtext('dc:creator',namespaces=ns)):result['unexpected_changes'].append([post_id,'author'])
 metas={m.findtext('wp:meta_key',namespaces=ns):m.findtext('wp:meta_value',default='',namespaces=ns) for m in item.findall('wp:postmeta',ns)}
 for (pid,key),value in old_meta.items():
  critical=key in ['_thumbnail_id','_wp_page_template','_ar_source_url','_yoast_wpseo_title','_yoast_wpseo_metadesc','_yoast_wpseo_canonical'] or key.startswith(('_yoast_wpseo_meta-robots','_yoast_wpseo_opengraph','_yoast_wpseo_twitter'))
  if pid==post_id and critical and norm(value)!=norm(metas.get(key,'')):result['critical_meta_changes'].append([pid,key])
for post_id,value in before.get('attachment_alt',{}).items():
 item=items.get(post_id)
 if item is None:result['attachment_alt_changes'].append([post_id,'missing']);continue
 metas={m.findtext('wp:meta_key',namespaces=ns):m.findtext('wp:meta_value',default='',namespaces=ns) for m in item.findall('wp:postmeta',ns)}
 if norm(value)!=norm(metas.get('_wp_attachment_image_alt','')):result['attachment_alt_changes'].append([post_id,'alt'])
ids=[156,159,718,762,766,769,772,775,778,781,783,808,816,818,820,822,824]
for post_id in ids:
 item=items.get(str(post_id));result['editorial_drafts'].append({'id':post_id,'status':item.findtext('wp:status',namespaces=ns) if item is not None else 'missing'})
result['unchanged_identity_fields']=not result['unexpected_changes']
(base/'content-preservation-final.json').write_text(json.dumps(result,ensure_ascii=False,indent=2))
print(json.dumps(result,ensure_ascii=False))
