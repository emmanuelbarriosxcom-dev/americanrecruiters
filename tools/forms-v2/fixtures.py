import io, json, sys
from pathlib import Path
from pypdf import PdfWriter

root=Path(sys.argv[1]);root.mkdir(parents=True,exist_ok=True)
def pdf(name, transform=None):
    w=PdfWriter();w.add_blank_page(width=200,height=200)
    w.add_metadata({'/Title':'SYNTHETIC QA - NOT A CV'})
    if transform:transform(w)
    out=io.BytesIO();w.write(out);(root/name).write_bytes(out.getvalue())
pdf('valid.pdf')
pdf('encrypted.pdf',lambda w:w.encrypt('synthetic-only'))
pdf('active.pdf',lambda w:w.add_js("app.alert('SYNTHETIC QA');"))
pdf('attachment.pdf',lambda w:w.add_attachment('synthetic.txt',b'SYNTHETIC QA'))
(root/'renamed.pdf').write_bytes(b'This is text, not a PDF.')
(root/'false-signature.pdf').write_bytes(b'%PDF-1.7\nTHIS IS NOT A STRUCTURALLY VALID PDF\n%%EOF\n')
(root/'empty.pdf').write_bytes(b'')
(root/'truncated.pdf').write_bytes((root/'valid.pdf').read_bytes()[:200])
(root/'too-large.pdf').write_bytes(b'%PDF-1.7\n'+b'x'*(5*1024*1024)+b'\n%%EOF\n')
# Valid PNG signature and bytes, intentionally renamed as PDF.
import base64
(root/'mime-mismatch.pdf').write_bytes(base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/a9kAAAAASUVORK5CYII='))
print(json.dumps({p.name:len(p.read_bytes()) for p in root.glob('*.pdf')}))
