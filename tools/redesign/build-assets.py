"""Reproduce approved V2.1 assets, without touching CMS data or forms."""
from pathlib import Path
import ast, json, hashlib, shutil
ROOT=Path(__file__).resolve().parents[2]
SOURCE=Path('/Users/emmanuelbarrios/Documents/Codex/AR-redesign-AB-20261025/design/redesign-v2-20261025')
THEME=ROOT/'wordpress/ar-editorial'
(THEME/'inc').mkdir(exist_ok=True)
css='\n'.join((SOURCE/'prototype/assets'/name).read_text() for name in ['design.css','refinements.css','refinement-v21.css'])
(THEME/'assets/css/v21.css').write_text('/* Approved design 119a7c6; ordered base, refinement and final component rules. */\n'+css)
tree=ast.parse((SOURCE/'refine-v21.py').read_text())
paths=next(ast.literal_eval(n.value) for n in tree.body if isinstance(n,ast.Assign) and any(isinstance(t,ast.Name) and t.id=='PATHS' for t in n.targets))
php="<?php\n/** Shared decorative SVGs from approved V2.1. */\nfunction ar_v21_icon($name, $extra='') {\n    $paths = "+'['+','.join("'"+k+"'=>'"+v.replace("'","\\'")+"'" for k,v in paths.items())+"];\n    if (!isset($paths[$name])) return '';\n    return '<svg class=\"icon icon-'.esc_attr($name).' '.esc_attr($extra).'\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.6\" stroke-linecap=\"round\" stroke-linejoin=\"round\" aria-hidden=\"true\" focusable=\"false\">'.$paths[$name].'</svg>';\n}\nfunction ar_v21_badge($name) { return '<span class=\"concept-icon\" aria-hidden=\"true\">'.ar_v21_icon($name).'</span>'; }\n"
(THEME/'inc/icons.php').write_text(php)
for name in ['editorial-reunion.webp','editorial-conversacion.webp']:
    shutil.copy2(SOURCE/'prototype/assets'/name,THEME/'assets/images'/name)
print(json.dumps({'css_bytes':len(css.encode()),'icons':len(paths),'source':'119a7c6a837cd176d893522db2fa42cec02922c7'}))
