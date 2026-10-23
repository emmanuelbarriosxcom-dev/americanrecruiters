import urllib.request,urllib.error,re,json
from pathlib import Path
root='http://127.0.0.1:9413'
report=[]
for path,expected in [('/',200),('/blog/',200),('/blog/page/2/',200),('/qa-post-1/',200),('/category/qa-category/',200),('/?s=QA',200),('/zzzz-release-not-found-847692/',404),('/?p=999999',404),('/?p=13',404)]:
    try:r=urllib.request.urlopen(root+path);status=r.status;html=r.read().decode()
    except urllib.error.HTTPError as e:status=e.code;html=e.read().decode()
    row={'path':path,'final_url':r.geturl() if status<400 else root+path,'status':status,'h1_count':len(re.findall(r'<h1\b',html)),'canonical_count':len(re.findall(r'rel=["\']canonical',html)),'has_private_draft':'PRIVATE_DRAFT_SENTINEL' in html or 'DO_NOT_RENDER_DRAFT' in html,'php_error':bool(re.search(r'(Fatal error|Warning:|Deprecated:)',html)),'pagination':'page/2' in html,'robots_noindex':'noindex' in html}
    row['pass']=status==expected and row['h1_count']==1 and not row['has_private_draft'] and not row['php_error'] and row['canonical_count']<=1
    if path=='/':row['pass']=row['pass'] and row['canonical_count']==1
    if path=='/blog/':row['pass']=row['pass'] and row['pagination']
    report.append(row)
Path('release/qa/rc2/http-final.json').write_text(json.dumps(report,indent=2));print(json.dumps(report,indent=2));assert all(r['pass'] for r in report)
