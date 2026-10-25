#!/usr/bin/env python3
"""Import the authorized Agent U folder locally. Never upload its contents automatically."""
import argparse
import csv
import hashlib
import json
from collections import Counter
from pathlib import Path
from urllib.parse import urlsplit, urlunsplit
from seoqa import public_url


def read(p):
    return list(csv.DictReader(p.open(encoding='utf-8-sig')))


def build(source, output):
    source, output = Path(source), Path(output)
    output.mkdir(parents=True, exist_ok=True)
    manifest = json.loads((source / 'MANIFEST_SHA256.json').read_text())
    for f in manifest['files']:
        p = source / f['path']
        if not p.is_relative_to(source) or hashlib.sha256(p.read_bytes()).hexdigest() != f['sha256']:
            raise ValueError('Source manifest mismatch: ' + f['path'])
    original = read(source / 'URLs_39.csv')
    if len(original) != 39 or len({x['ID'] for x in original}) != 39:
        raise ValueError('Expected exactly 39 distinct U rows')
    routes = {x['ID']: x for x in read(source / 'Rutas_y_pruebas.csv')}
    publication = read(source / 'Publicación.csv')
    drafts = {x['URL final escenario A']: x for x in publication if x['Estado WP corte'] == 'draft' and x['Tipo'] in ('Histórico', 'Nuevo')}
    if len(drafts) != 17:
        raise ValueError('Expected exactly 17 documented draft articles')
    rows = []
    for r in original:
        action, destination = r['Acción Fase5 intacta'], r['Destino previsto (escenario)']
        destination = destination if destination.startswith('http') else None
        expected = 301 if action == 'REDIRECCIONAR 301' else 200 if action in ('CONSERVAR', 'CONSOLIDAR') else None
        if r['ID'] == 'U14':
            expected = None  # Functional thank-you endpoint remains undecided.
        rows.append({'id': r['ID'], 'source_url': r['Origen original'], 'destination': destination,
                     'proposed_http': expected, 'canonical_proposed': destination if expected in (200, 301) else None,
                     'action': action, 'original_risk': r['Riesgo original'], 'execution_priority': routes[r['ID']]['Prioridad'],
                     'approval': 'PENDING — U did not authorise deployment/indexation',
                     'staging_url': r['Equivalencia staging'] if r['Equivalencia staging'].startswith('http') else None,
                     'draft': r['Origen original'] in drafts,
                     'draft_id': drafts.get(r['Origen original'], {}).get('ID WP'),
                     'observed_http_u': r['HTTP actual completo'], 'observed_canonical_u': r['Canonical actual final'],
                     'observed_staging_u': r['Estado staging'], 'observed_at_u': r['Hora UTC'],
                     'dependencies': r['Dependencias'], 'source_evidence': r['Evidencia'],
                     'source_required_result': routes[r['ID']]['Resultado requerido en lanzamiento'],
                     'notes': r['Validación / diferencia']})
    if sum(r['proposed_http'] == 301 for r in rows) != 9:
        raise ValueError('All nine original redirect proposals must survive import')
    common = {'schema_version': 1, 'source': 'Agent U 2026-10-24; Scenario A unapproved',
              'allowed_targets': ['https://www.americanrecruiters.com.mx', 'https://desarrollo.americanrecruiters.mx'],
              'production_alias_hosts': ['www.americanrecruiters.com.mx', 'americanrecruiters.com.mx'],
              'production_sitemap_exclusions': [x['URL final escenario A'] for x in publication if x['ID WP'] == '27'],
              'sitemap_candidates': ['https://www.americanrecruiters.com.mx/sitemap_index.xml', 'https://www.americanrecruiters.com.mx/wp-sitemap.xml']}
    data = dict(common, urls=rows)
    (output / 'matrix39.json').write_text(json.dumps(data, ensure_ascii=False, indent=2))
    with (output / 'matrix39.csv').open('w', encoding='utf-8-sig', newline='') as f:
        w = csv.DictWriter(f, fieldnames=list(rows[0])); w.writeheader(); w.writerows(rows)
    article_rows = []
    for address, r in drafts.items():
        article_rows.append({'id': 'ARTICLE-' + r['ID WP'], 'source_url': address, 'destination': address,
                             'staging_url': address.replace('www.americanrecruiters.com.mx', 'desarrollo.americanrecruiters.mx'),
                             'action': 'PUBLISH_ONLY_AFTER_APPROVAL', 'draft': True, 'draft_id': r['ID WP'],
                             'proposed_http': 200, 'canonical_proposed': address,
                             'approval': r['Publicación por U'], 'source_evidence': 'Publicación.csv, ID WP ' + r['ID WP'],
                             'notes': r['Acción propuesta']})
    (output / 'articles17.json').write_text(json.dumps(dict(common, urls=article_rows), ensure_ascii=False, indent=2))
    # Keep U's expected results verbatim. No new retirement/410 map.
    simulations = read(source / 'SIMULACION_REGLAS.csv')
    (output / 'redirect_cases_u.json').write_text(json.dumps({'source': 'SIMULACION_REGLAS.csv; synthetic model only', 'cases': simulations}, ensure_ascii=False, indent=2))
    live_cases = []
    for i, c in enumerate(simulations, 1):
        if i > 32:
            continue  # Eight synthetic exclusions include invented media/admin/POST: never probe them.
        if c['method'] != 'GET' or not public_url(c['source'], set(common['production_alias_hosts'])):
            continue
        is_redirect = c['first_model'] == '301'
        destination = c['destination'] if is_redirect else c['source']
        missing = '/ar-u-ruta-inexistente-20261024/' in destination
        p = urlsplit(destination)
        live_cases.append({'id': f'CASE-{i:02}', 'source_url': c['source'], 'staging_url': None,
            'action': 'PROPOSED_RULE_TEST', 'approval': 'PENDING', 'destination': destination,
            'proposed_http': 301 if is_redirect else (404 if missing else 200),
            'proposed_final_http': 404 if missing else 200,
            'canonical_proposed': None if missing else urlunsplit((p.scheme, p.netloc, p.path, '', '')),
            'source_evidence': f'SIMULACION_REGLAS.csv row {i+1}; 404 contract from Rutas y pruebas R27'})
    for key in ('R25', 'R27', 'R50'):
        c = routes[key]
        live_cases.append({'id': key, 'source_url': c['URL o caso'],
            'staging_url': c['URL o caso'].replace('www.americanrecruiters.com.mx', 'desarrollo.americanrecruiters.mx'),
            'action': 'LEGITIMATE_404_CONTROL', 'approval': 'PENDING', 'destination': c['URL o caso'],
            'proposed_http': 404, 'canonical_proposed': None, 'source_evidence': 'Rutas_y_pruebas.csv ' + key})
    (output / 'redirect-live-cases.json').write_text(json.dumps(dict(common, urls=live_cases), ensure_ascii=False, indent=2))
    route_cases = [r for r in routes.values() if not r['ID'].startswith('U')]
    (output / 'route_cases_u.json').write_text(json.dumps(route_cases, ensure_ascii=False, indent=2))
    (output / 'approvals.pending.json').write_text(json.dumps({'target': 'https://www.americanrecruiters.com.mx', 'evidence': '',
         'urls': {r['id']: {'approved': False, 'evidence': ''} for r in rows + article_rows}}, indent=2))
    (output / 'provenance.json').write_text(json.dumps({'source_cut': manifest['created_utc'], 'manifest_files_verified': len(manifest['files']),
         'source_hashes': {f['path']: f['sha256'] for f in manifest['files'] if '/' not in f['path']},
         'rows': len(rows), 'actions': dict(Counter(x['action'] for x in rows)), 'draft_articles': len(article_rows),
         'redirect_model_cases': len(simulations), 'note': 'No approval inferred from observed HTTP or current published status.'}, ensure_ascii=False, indent=2))
    return data


if __name__ == '__main__':
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--source', required=True); p.add_argument('--output', required=True)
    a = p.parse_args(); result = build(a.source, a.output)
    print('Imported', len(result['urls']), 'rows. Deployment remains pending.')
