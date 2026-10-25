#!/usr/bin/env python3
"""Agent U's proposed rules, pure simulation only; NEVER deploys rules or sends HTTP."""
import argparse
import json
from urllib.parse import urlsplit, urlunsplit
CANONICAL = 'www.americanrecruiters.com.mx'


def proposed(address, method='GET'):
    p = urlsplit(address)
    if method not in ('GET', 'HEAD') or p.hostname not in (CANONICAL, 'americanrecruiters.com.mx'):
        return None
    path = p.path or '/'
    if path in ('/inicio', '/inicio/'):
        path = '/'
    elif path in ('/privacidad', '/servicios', '/blog', '/contacto'):
        path += '/'
    if path != (p.path or '/') or p.scheme != 'https' or p.hostname != CANONICAL:
        return urlunsplit(('https', CANONICAL, path, p.query, ''))
    return None


def validate(cases):
    results = []
    for c in cases:
        actual = proposed(c['source'], c['method'])
        expected = c['destination']
        code = 301 if actual else 200  # Source uses NO_RULE below, not a network HTTP 200.
        expected_redirect = str(c['first_model']).startswith('301')
        good = bool(actual) == expected_redirect
        if actual:
            good = good and actual == expected and proposed(actual, c['method']) is None and urlsplit(actual).query == urlsplit(c['source']).query
        results.append({'source': c['source'], 'method': c['method'], 'expected_model': c['first_model'],
                        'actual_destination': actual, 'pass': good, 'network_requests': 0})
    return results


if __name__ == '__main__':
    p = argparse.ArgumentParser(description=__doc__); p.add_argument('--cases', required=True); p.add_argument('--output', required=True)
    a = p.parse_args(); data = json.load(open(a.cases)); results = validate(data['cases'])
    with open(a.output, 'w') as f:
        json.dump({'scope': 'Synthetic model, not Apache/LiteSpeed verification', 'results': results}, f, indent=2)
    print(f'{sum(x["pass"] for x in results)}/{len(results)} synthetic redirect cases PASS')
    raise SystemExit(0 if all(x['pass'] for x in results) else 1)
