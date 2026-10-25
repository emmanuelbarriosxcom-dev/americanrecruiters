#!/usr/bin/env python3
"""Read-only migration QA. Python 3.10+, standard library, GET only."""
import argparse
import csv
import hashlib
import html
import ipaddress
import json
import re
import socket
import time
import urllib.error
import urllib.parse as url
import urllib.request
import xml.etree.ElementTree as ET
from collections import Counter
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path

VERSION = '1.0.0'
UA = 'AR-Migration-SEO-QA/1.0 (read-only; sequential GET)'
REDIRECTS = {301, 302, 303, 307, 308}
BLOCK = re.compile(r'/(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|private|cv|cvs|curriculum|curriculums)(/|$)', re.I)
DOCUMENT = re.compile(r'\.(pdf|docx?|xlsx?|zip|csv|sql|env)(?:$)', re.I)


def now():
    return datetime.now(timezone.utc).isoformat()


def normal(s):
    p = url.urlsplit(s)
    return url.urlunsplit((p.scheme.lower(), p.netloc.lower(), p.path or '/', p.query, ''))


def remap(s, target):
    p, t = url.urlsplit(s), url.urlsplit(target)
    return url.urlunsplit((t.scheme, t.netloc, p.path or '/', p.query, ''))


def public_url(s, hosts, local=False):
    p = url.urlsplit(s)
    if p.scheme not in ('http', 'https') or not p.hostname or p.username or p.password:
        return False
    if p.hostname not in hosts or (p.port and p.port not in (80, 443) and not local):
        return False
    path = url.unquote(p.path)
    if BLOCK.search(path) or DOCUMENT.search(path):
        return False
    if any(k.lower() in ('preview', 'preview_nonce', 'token', 'password', 'nonce', 'action', 'email', 'phone', 'name') for k, v in url.parse_qsl(p.query)):
        return False
    return True


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class Transport:
    """Single connection at a time. No cookies, auth, proxies, POST or retries."""
    def __init__(self, hosts, interval=2.0, budget=80, local=False, synthetic_dns=False):
        self.hosts, self.interval, self.budget, self.local = hosts, interval, budget, local
        self.synthetic_dns = synthetic_dns
        self.count, self.last, self.stopped = 0, 0, ''
        self.cache, self.log = {}, []
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect())

    def get(self, address, image=False):
        address = normal(address)
        key = (address, image)
        if key in self.cache:
            return self.cache[key]
        if not public_url(address, self.hosts, self.local):
            return {'url': address, 'status': None, 'error': 'blocked_by_safety_policy', 'headers': {}, 'body': ''}
        if self.stopped or self.count >= self.budget:
            return {'url': address, 'status': None, 'error': self.stopped or 'request_budget', 'headers': {}, 'body': ''}
        try:
            if not self.local:
                ips = socket.getaddrinfo(url.urlsplit(address).hostname, None)
                def permitted(ip):
                    value = ipaddress.ip_address(ip)
                    return value.is_global or (self.synthetic_dns and value.version == 4 and value in ipaddress.ip_network('240.0.0.0/4'))
                if any(not permitted(x[4][0]) for x in ips):
                    raise ValueError('non-public DNS address blocked')
            time.sleep(max(0, self.interval - (time.monotonic() - self.last)))
            self.count += 1
            self.last = time.monotonic()
            headers = {'User-Agent': UA, 'Accept': 'text/html,application/xml,text/plain,*/*;q=0.1', 'Accept-Encoding': 'identity'}
            if image:
                headers['Range'] = 'bytes=0-2047'
            req = urllib.request.Request(address, headers=headers, method='GET')
            try:
                response = self.opener.open(req, timeout=20)
            except urllib.error.HTTPError as exc:
                response = exc
            with response:
                pairs = response.headers.items()
                hs = {}
                for k, v in pairs:
                    hs[k.lower()] = hs.get(k.lower(), '') + (', ' if k.lower() in hs else '') + v
                status = response.code
                # Never store bodies of authentication/error responses or images.
                limit = 2048 if image else 2_000_000
                content_type = hs.get('content-type', '').lower()
                readable = not image and any(x in content_type for x in ('text/', 'xml', 'html')) and status == 200
                raw = response.read(limit + 1) if readable or image else b''
                charset = response.headers.get_content_charset() or 'utf-8'
                body = raw[:limit].decode(charset, errors='replace') if readable else ''
                result = {'url': address, 'status': status, 'headers': hs, 'body': body,
                          'truncated': len(raw) > limit, 'sample_bytes': min(len(raw), limit)}
                if status in (403, 429, 503):
                    self.stopped = f'circuit_breaker_HTTP_{status}'
        except (OSError, ValueError, urllib.error.URLError) as exc:
            result = {'url': address, 'status': None, 'error': str(exc), 'headers': {}, 'body': ''}
        self.cache[key] = result
        self.log.append({'time': now(), 'method': 'GET', 'url': address, 'status': result['status'],
                         'error': result.get('error', ''), 'bytes_read': result.get('sample_bytes', 0)})
        return result

    def follow(self, address, image=False, hops=5):
        chain, seen = [], set()
        for _ in range(hops + 1):
            address = normal(address)
            if address in seen:
                return {'chain': chain, 'final_url': address, 'error': 'redirect_loop'}
            seen.add(address)
            r = self.get(address, image)
            chain.append(r)
            if r['status'] not in REDIRECTS:
                return {'chain': chain, 'final_url': address, 'error': r.get('error', '')}
            location = r['headers'].get('location')
            if not location:
                return {'chain': chain, 'final_url': address, 'error': 'redirect_without_location'}
            address = url.urljoin(address, location)
        return {'chain': chain, 'final_url': address, 'error': 'redirect_hop_limit'}


class Page(HTMLParser):
    def __init__(self, body, address, headers=None):
        super().__init__(convert_charrefs=True)
        self.address, self.base = address, address
        self.titles, self.descriptions, self.h1, self.canonicals, self.robots = [], [], [], [], []
        self.links, self.images, self.ids = [], [], set()
        self.capture, self.skip = None, 0
        self.in_head, self.svg_depth = False, 0
        self.feed(body)
        headers = headers or {}
        for m in re.finditer(r'<([^>]+)>\s*;\s*rel\s*=\s*["\']?canonical', headers.get('link', ''), re.I):
            self.canonicals.append(url.urljoin(address, m.group(1)))
        self.xrobots = headers.get('x-robots-tag', '')

    def handle_starttag(self, tag, attributes):
        a = dict(attributes)
        if tag == 'head':
            self.in_head = True
        if tag == 'svg':
            self.svg_depth += 1
        if a.get('id'):
            self.ids.add(a['id'])
        if tag in ('script', 'style', 'template'):
            self.skip += 1
        if tag == 'base' and a.get('href'):
            self.base = url.urljoin(self.address, a['href'])
        if (tag == 'title' and self.in_head and not self.svg_depth) or (tag == 'h1' and not self.svg_depth):
            self.capture = (tag, [])
        if tag == 'meta':
            name = a.get('name', '').lower()
            if name == 'description':
                self.descriptions.append(a.get('content', '').strip())
            if name in ('robots', 'googlebot'):
                self.robots.append(a.get('content', '').lower())
        if tag == 'link' and 'canonical' in a.get('rel', '').lower().split():
            self.canonicals.append(url.urljoin(self.base, a.get('href', '')) if a.get('href') else '')
        if tag == 'a' and a.get('href'):
            self.links.append(url.urljoin(self.base, a['href']))
        if tag == 'img':
            for key in ('src', 'data-src', 'data-lazy-src'):
                if a.get(key):
                    self.images.append(url.urljoin(self.base, a[key]))
            for key in ('srcset', 'data-srcset', 'data-lazy-srcset'):
                for candidate in a.get(key, '').split(','):
                    parts = candidate.split()
                    if parts and not parts[0].startswith('data:'):
                        self.images.append(url.urljoin(self.base, parts[0]))

    def handle_data(self, value):
        if self.capture and not self.skip:
            self.capture[1].append(value)

    def handle_endtag(self, tag):
        if tag == 'head':
            self.in_head = False
        if tag == 'svg':
            self.svg_depth = max(0, self.svg_depth - 1)
        if tag in ('script', 'style', 'template'):
            self.skip = max(0, self.skip - 1)
        if self.capture and tag == self.capture[0]:
            value = ' '.join(''.join(self.capture[1]).split())
            (self.titles if tag == 'title' else self.h1).append(value)
            self.capture = None

    @property
    def noindex(self):
        meta = ','.join(self.robots)
        # Apply generic and Googlebot X-Robots only, not an unrelated crawler's rule.
        relevant, agent = [], None
        for part in self.xrobots.lower().split(','):
            if ':' in part:
                label, value = part.split(':', 1)
                if label.strip() not in ('unavailable_after', 'max-snippet', 'max-image-preview', 'max-video-preview'):
                    agent, part = label.strip(), value
            if agent in (None, 'googlebot'):
                relevant.append(part)
        return bool(set(re.split(r'[\s,;]+', meta + ',' + ','.join(relevant))) & {'noindex', 'none'})


def robots_rules(text, path, agent='googlebot'):
    """Google-style longest match, wildcard and $; Allow wins equal length."""
    groups, agents, rules = [], [], []
    for line in text.splitlines() + ['User-agent: __end__']:
        line = line.split('#', 1)[0].strip()
        if ':' not in line:
            continue
        k, v = (x.strip() for x in line.split(':', 1))
        if k.lower() == 'user-agent':
            if rules:
                groups.append((agents, rules)); agents, rules = [], []
            agents.append(v.lower())
        elif k.lower() in ('allow', 'disallow') and agents:
            rules.append((k.lower(), v))
    if rules:
        groups.append((agents, rules))
    specific = [r for agents, r in groups if agent in agents]
    chosen = specific or [r for agents, r in groups if '*' in agents]
    matches = []
    for group in chosen:
        for kind, pattern in group:
            if not pattern:
                continue
            expression = '^' + re.escape(pattern).replace(r'\*', '.*')
            if expression.endswith(r'\$'):
                expression = expression[:-2] + '$'
            if re.search(expression, path):
                matches.append((len(pattern.replace('*', '').rstrip('$')), kind == 'allow'))
    return max(matches)[1] if matches else True


def sitemap_xml(body):
    if re.search(r'<!DOCTYPE|<!ENTITY', body, re.I):
        raise ValueError('DTD/ENTITY not allowed')
    root = ET.fromstring(body)
    local = lambda tag: tag.rsplit('}', 1)[-1]
    kind = local(root.tag)
    if kind not in ('sitemapindex', 'urlset'):
        raise ValueError('not a sitemap XML root')
    expected = 'sitemap' if kind == 'sitemapindex' else 'url'
    locs = []
    for child in root:
        if local(child.tag) != expected:
            continue
        for loc in child:
            if local(loc.tag) == 'loc' and loc.text:
                locs.append(loc.text.strip())
    return kind, locs


class Audit:
    def __init__(self, config, target, environment, mode, transport, approvals=None, links=2, images=2):
        self.config, self.target, self.env, self.mode, self.t = config, target.rstrip('/'), environment, mode, transport
        self.approvals, self.link_limit, self.image_limit = approvals or {}, links, images
        self.findings, self.pages, self.sitemap_urls = [], [], set()
        self.robots, self.sitemap_complete = None, True

    def add(self, row, check, status, severity, observed='', expected='', detail=''):
        self.findings.append({'id': row.get('id', 'SITE'), 'url': row.get('url', self.target + '/'),
                              'check': check, 'status': status, 'severity': severity,
                              'observed': str(observed), 'expected': str(expected), 'detail': detail})

    def approved(self, row):
        entry = self.approvals.get('urls', {}).get(row['id'], {})
        if (self.approvals.get('target', '').rstrip('/') == self.target and
            self.approvals.get('evidence') and entry.get('evidence') and entry.get('approved') is True):
            return entry
        return {}

    def site(self):
        row = {'id': 'SITE', 'url': self.target + '/robots.txt'}
        trace = self.t.follow(row['url'])
        r = trace['chain'][-1]
        if trace['error'] or r['status'] != 200:
            self.add(row, 'robots_fetch', 'PENDING' if trace['error'] else 'WARN', 'medium', r['status'], 200, trace['error'])
        elif 'html' in r['headers'].get('content-type', '').lower():
            self.add(row, 'robots_format', 'FAIL', 'high', 'HTML', 'text/plain')
        elif r.get('truncated'):
            self.add(row, 'robots_coverage', 'PENDING', 'high', 'Response exceeds read cap')
        else:
            self.robots = r['body']
            self.add(row, 'robots_fetch', 'PASS', 'info', 200, 200)
        declared = re.findall(r'^\s*Sitemap:\s*(\S+)', self.robots or '', re.I | re.M)
        seeds = declared or [remap(s, self.target) for s in self.config.get('sitemap_candidates', [])]
        visited, members = set(), set()
        def walk(address, depth=0):
            if address in visited:
                return
            if depth > 3 or len(visited) >= 10:
                self.sitemap_complete = False
                self.add({'id': 'SITE', 'url': address}, 'sitemap_limit', 'PENDING', 'medium', 'depth/file cap'); return
            visited.add(address)
            row = {'id': 'SITE', 'url': address}
            trace = self.t.follow(address)
            r = trace['chain'][-1]
            if trace['error'] or r['status'] != 200:
                self.sitemap_complete = False
                self.add(row, 'sitemap_fetch', 'PENDING' if trace['error'] else 'WARN', 'high', r['status'], 200, trace['error']); return
            try:
                if r.get('truncated'):
                    raise ValueError('XML exceeds byte cap')
                kind, locs = sitemap_xml(r['body'])
                self.add(row, 'sitemap_xml', 'PASS', 'info', f'{kind}: {len(locs)} entries')
                if kind == 'sitemapindex':
                    for child in locs:
                        walk(child, depth + 1)
                else:
                    members.update(map(normal, locs))
            except (ValueError, ET.ParseError) as exc:
                self.sitemap_complete = False
                self.add(row, 'sitemap_xml', 'FAIL', 'high', str(exc))
        for seed in seeds:
            walk(seed)
            if not declared and members:
                break  # A working core sitemap is valid; Yoast filename is not required.
        self.sitemap_urls = members
        if not seeds or not members:
            self.sitemap_complete = False
            self.add(row, 'sitemap_membership_coverage', 'PENDING', 'high', 'No complete nonempty sitemap read')
        for member in sorted(members):
            if url.urlsplit(member).hostname != url.urlsplit(self.target).hostname:
                self.add({'id': 'SITE', 'url': member}, 'sitemap_host', 'WARN', 'high', member, self.target)
            if self.env == 'production' and self.mode != 'baseline':
                excluded = {normal(remap(s, self.target)) for s in self.config.get('production_sitemap_exclusions', [])}
                if normal(member) in excluded or not public_url(member, self.t.hosts, self.t.local):
                    self.add({'id': 'SITE', 'url': member}, 'sitemap_excluded_url', 'FAIL', 'high', member, 'Not listed in production sitemap')

    def page(self, source):
        row = dict(source)
        address = row.get('staging_url') if self.env == 'staging' else row['source_url']
        if self.env == 'staging' and not address:
            row['url'] = row['source_url']
            self.add(row, 'staging_equivalence', 'PENDING', 'medium', 'Not documented by U'); return
        address = remap(address, self.target) if self.env == 'staging' else address
        row['url'] = address
        approval = self.approved(row)
        strict = self.mode != 'baseline' and bool(approval)
        draft_pending = row.get('draft') and not approval.get('published')
        if self.mode != 'baseline' and not approval:
            self.add(row, 'approval', 'PENDING', 'high', row.get('approval', 'pending'), 'documented per-URL approval')
        if self.mode == 'articles' and draft_pending:
            self.add(row, 'publication_gate', 'PENDING', 'high', 'Draft at U cut; publication not confirmed')
            return
        trace = self.t.follow(address)
        r, first = trace['chain'][-1], trace['chain'][0]
        status, final = r['status'], trace['final_url']
        record = {'id': row['id'], 'url': address, 'initial_status': first['status'], 'final_status': status,
                  'final_url': final, 'hops': len(trace['chain']) - 1, 'error': trace['error'],
                  'chain': [{'url': x['url'], 'status': x['status'], 'location': x['headers'].get('location', '')} for x in trace['chain']]}
        self.pages.append(record)
        if trace['error']:
            fatal = trace['error'] in ('redirect_loop', 'redirect_hop_limit', 'redirect_without_location')
            self.add(row, 'http_transport', 'FAIL' if fatal else 'PENDING', 'critical' if fatal else 'high', trace['error']); return
        self.add(row, 'http_observed', 'PASS', 'info', f'{first["status"]} → {status}', detail=f'Final: {final}')
        if status in (403, 429, 503):
            self.add(row, 'access', 'PENDING', 'high', status, 'No bypass; circuit breaker stops run'); return
        if status and status >= 500:
            self.add(row, 'server_error', 'FAIL', 'critical', status, 'No 5xx'); return
        proposed = approval.get('expected_http', row.get('proposed_http'))
        dest = approval.get('destination', row.get('destination'))
        if self.env == 'staging':
            proposed = approval.get('expected_http', None)
            dest = remap(dest, self.target) if dest and dest.startswith('http') else None
        if proposed is not None:
            good = first['status'] == proposed
            self.add(row, 'expected_http', 'PASS' if good else ('FAIL' if strict else 'WARN'), 'high', first['status'], proposed,
                     'Approved expectation' if strict else 'Proposal only; not a launch approval')
        if proposed in REDIRECTS and dest:
            expected_final = approval.get('final_http', row.get('proposed_final_http', 200))
            good = normal(final) == normal(dest) and status == expected_final and len(trace['chain']) == 2
            self.add(row, 'redirect_contract', 'PASS' if good else ('FAIL' if strict else 'WARN'), 'high', record['chain'], dest + f' in 1 hop → {expected_final}')
        if len(trace['chain']) > 2:
            self.add(row, 'redirect_chain', 'FAIL' if strict else 'WARN', 'high', record['hops'], '<=1')
        if url.urlsplit(address).query != url.urlsplit(final).query:
            self.add(row, 'query_preservation', 'FAIL' if strict else 'WARN', 'high', url.urlsplit(final).query, url.urlsplit(address).query)
        if status != 200:
            legitimate = status in (404, 410) and (draft_pending or row.get('action') == 'REVISAR' or proposed == status or row.get('proposed_final_http') == status)
            self.add(row, 'non_200', 'PASS' if legitimate else ('FAIL' if strict else 'WARN'), 'info' if legitimate else 'high', status,
                     detail='Observed absence; no retirement decision inferred' if legitimate else 'Needs review')
            return
        if 'html' not in r['headers'].get('content-type', '').lower():
            self.add(row, 'content_type', 'FAIL', 'high', r['headers'].get('content-type'), 'HTML'); return
        if r.get('truncated'):
            self.add(row, 'html_coverage', 'PENDING', 'high', '2 MB read cap')
        p = Page(r['body'], final, r['headers'])
        record.update({'title': p.titles, 'description': p.descriptions, 'h1': p.h1, 'canonical': p.canonicals,
                       'meta_robots': p.robots, 'x_robots_tag': p.xrobots, 'noindex': p.noindex,
                       'html_sha256': hashlib.sha256(r['body'].encode()).hexdigest()})
        for name, values in [('title', p.titles), ('meta_description', p.descriptions), ('h1', p.h1)]:
            good = len(values) == 1 and bool(values[0])
            self.add(row, name, 'PASS' if good else ('FAIL' if strict else 'WARN'), 'high' if strict else 'medium', values, 'one nonempty element')
        canonical_expected = approval.get('canonical') or (remap(row['canonical_proposed'], self.target) if self.env == 'staging' and row.get('canonical_proposed') else row.get('canonical_proposed'))
        # Canonical absence on a staging noindex document is recorded, not a blind fix.
        if self.env == 'staging' and p.noindex and not p.canonicals:
            self.add(row, 'canonical', 'WARN', 'low', [], 'Pending staging canonical policy')
        else:
            good = len(p.canonicals) == 1 and bool(p.canonicals[0]) and normal(p.canonicals[0]) == normal(canonical_expected or final)
            self.add(row, 'canonical', 'PASS' if good else ('FAIL' if strict else 'WARN'), 'high', p.canonicals, canonical_expected or final)
        if self.env == 'staging':
            self.add(row, 'indexability', 'PASS' if p.noindex else 'FAIL', 'critical', 'noindex' if p.noindex else 'indexable', 'noindex')
        elif strict and 'indexable' in approval:
            good = p.noindex != approval['indexable']
            self.add(row, 'indexability', 'PASS' if good else 'FAIL', 'critical', 'noindex' if p.noindex else 'indexable', approval['indexable'])
        else:
            self.add(row, 'indexability', 'PENDING' if self.mode != 'baseline' else 'PASS', 'medium',
                     'noindex' if p.noindex else 'no explicit noindex', 'No final policy approved' if not strict else 'pending')
        if self.robots is not None:
            parts = url.urlsplit(final)
            allowed = robots_rules(self.robots, parts.path + ('?' + parts.query if parts.query else ''))
            self.add(row, 'robots_allowed_googlebot', 'FAIL' if not allowed and strict and approval.get('indexable') else ('PASS' if allowed else 'WARN'), 'high', allowed, detail='Parsed REP, not Google fetch/index confirmation')
        else:
            self.add(row, 'robots_allowed_googlebot', 'PENDING', 'high', 'robots.txt unavailable')
        member = normal(final) in self.sitemap_urls
        if strict and approval.get('indexable') and approval.get('sitemap', True):
            self.add(row, 'sitemap_membership', 'PASS' if member else ('FAIL' if self.sitemap_complete else 'PENDING'), 'high', member, True)
        elif self.env == 'production' and member and p.noindex:
            self.add(row, 'sitemap_noindex', 'FAIL' if strict else 'WARN', 'high', 'Noindex URL listed')
        if self.env == 'staging' and draft_pending and member:
            self.add(row, 'draft_in_sitemap', 'WARN', 'high', final, 'Confirm publication state; U cut was draft')
        if re.search(r'page not found|página no encontrada|no se ha encontrado', ' '.join(p.titles + p.h1), re.I):
            self.add(row, 'soft_404_signal', 'WARN', 'high', p.titles + p.h1, 'Manual confirmation needed')
        self.resources(row, p, final)

    def resources(self, row, page, final):
        for kind, values, cap in [('internal_link', page.links, self.link_limit), ('image', page.images, self.image_limit)]:
            unique = sorted(set(values))
            candidates, excluded, external = [], 0, 0
            for v in unique:
                if v.startswith(('tel:', 'mailto:', 'data:', 'javascript:')):
                    excluded += 1; continue
                if self.env == 'production' and 'desarrollo.' in v:
                    self.add(row, 'staging_link', 'WARN', 'high', v)
                if url.urlsplit(v).hostname not in self.t.hosts:
                    external += 1; continue
                if normal(v) == normal(final) and url.urlsplit(v).fragment:
                    fragment = url.unquote(url.urlsplit(v).fragment)
                    if fragment not in page.ids:
                        self.add(row, 'fragment', 'WARN', 'medium', v, 'Existing ID; JS/tab fragments need manual review')
                    continue
                if public_url(v, self.t.hosts, self.t.local):
                    candidates.append(normal(v))
                else:
                    excluded += 1
            candidates = list(dict.fromkeys(candidates))
            for address in candidates[:cap]:
                trace = self.t.follow(address, image=kind == 'image')
                r = trace['chain'][-1]
                if trace['error']:
                    state = 'PENDING'
                else:
                    good = r['status'] in ((200, 206) if kind == 'image' else (200,))
                    if kind == 'image':
                        good = good and r['headers'].get('content-type', '').lower().startswith('image/')
                    state = 'PASS' if good else 'WARN'
                self.add(row, kind + ':' + address, state, 'medium', r['status'], '200 image/HTML resource', trace['error'])
            self.add(row, kind + '_coverage', 'WARN' if len(candidates) > cap or external else 'PASS', 'low',
                     f'{min(cap, len(candidates))}/{len(candidates)} eligible; {external} external; {excluded} excluded',
                     detail='Bounded sampling; not full resource/render validation')

    def run(self):
        self.site()
        for row in self.config['urls']:
            self.page(row)
        return self


def compare(current, previous):
    key = lambda x: (x['id'], x['check'])
    old = {key(x): x for x in previous.get('findings', [])}
    new = {key(x): x for x in current['findings']}
    changes = []
    for k in sorted(old.keys() | new.keys()):
        a, b = old.get(k), new.get(k)
        if not a or not b or (a['status'], a['observed']) != (b['status'], b['observed']):
            changes.append({'id': k[0], 'check': k[1], 'before': a, 'after': b,
                            'classification': 'not_retested' if not b else ('new_failure' if b['status'] == 'FAIL' and (not a or a['status'] != 'FAIL') else 'changed')})
    return changes


def csv_safe(value):
    text = str(value)
    return "'" + text if text.lstrip().startswith(('=', '+', '-', '@')) else text


def report(audit, output, previous=None):
    out = Path(output); out.mkdir(parents=True, exist_ok=True)
    counts = dict(Counter(x['status'] for x in audit.findings))
    overall = 'FAIL' if counts.get('FAIL') else ('INCOMPLETE' if counts.get('PENDING') else 'PASS_WITH_WARNINGS' if counts.get('WARN') else 'PASS')
    result = {'version': VERSION, 'time_utc': now(), 'environment': audit.env, 'mode': audit.mode,
              'target': audit.target, 'overall': overall, 'counts': counts, 'urls_configured': len(audit.config['urls']),
              'synthetic_dns_routing': audit.t.synthetic_dns,
              'script_sha256': hashlib.sha256(Path(__file__).read_bytes()).hexdigest(),
              'urls_observed': len(audit.pages), 'network_requests': audit.t.count, 'circuit_breaker': audit.t.stopped,
              'config_sha256': hashlib.sha256(json.dumps(audit.config, sort_keys=True).encode()).hexdigest(),
              'sitemap_members': sorted(audit.sitemap_urls), 'sitemap_complete': audit.sitemap_complete,
              'pages': audit.pages, 'findings': audit.findings, 'requests': audit.t.log,
              'critical_errors': [f for f in audit.findings if f['status'] == 'FAIL' and f['severity'] == 'critical'],
              'warnings': [f for f in audit.findings if f['status'] == 'WARN'],
              'affected_urls': sorted({f['url'] for f in audit.findings if f['status'] in ('FAIL', 'WARN', 'PENDING')}),
              'limitations': ['HTTP source only; no JavaScript rendering or Search Console indexing proof.',
                              'Limited link/image samples; no forms, POST, admin, CVs or private files.',
                              'PASS does not authorise deployment or close Agent U business/operational gates.']}
    if previous:
        prior = json.loads(Path(previous).read_text())
        if (prior['target'], prior['environment'], prior['mode']) != (result['target'], result['environment'], result['mode']):
            result['comparison_warning'] = 'Different target/environment/mode: changes are descriptive, not a regression verdict.'
        if prior.get('script_sha256') != result['script_sha256']:
            result['comparison_engine_warning'] = 'Different or unrecorded checker revision: do not attribute parsing differences to website changes.'
        result['comparison'] = compare(result, prior)
    (out / 'results.json').write_text(json.dumps(result, ensure_ascii=False, indent=2))
    fields = ['id', 'url', 'check', 'status', 'severity', 'observed', 'expected', 'detail']
    with (out / 'results.csv').open('w', newline='', encoding='utf-8-sig') as f:
        w = csv.DictWriter(f, fieldnames=fields); w.writeheader()
        w.writerows({k: csv_safe(row[k]) for k in fields} for row in audit.findings)
    esc = lambda s: html.escape(str(s))
    rows = ''.join('<tr>' + ''.join('<td>' + esc(r[k]) + '</td>' for k in fields) + '</tr>' for r in audit.findings)
    summary = {k: result[k] for k in ('time_utc', 'target', 'environment', 'mode', 'overall', 'counts', 'urls_configured', 'urls_observed', 'network_requests', 'circuit_breaker')}
    (out / 'summary.json').write_text(json.dumps(summary, ensure_ascii=False, indent=2))
    (out / 'report.html').write_text('<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Migration SEO QA</title><style>body{font:16px system-ui;margin:2rem;color:#142c42}h1{font-size:2rem}pre{white-space:pre-wrap;background:#edf4f7;padding:1rem}table{border-collapse:collapse;font-size:13px}td,th{border:1px solid #ccd5dd;padding:8px;vertical-align:top;overflow-wrap:anywhere;max-width:28rem}.scroll{overflow:auto}th{background:#16394e;color:white}</style><h1>Migration SEO QA — ' + esc(overall) + '</h1><pre>' + esc(json.dumps(summary, ensure_ascii=False, indent=2)) + '</pre><p>HTTP público de solo lectura. No equivale a autorización de lanzamiento ni confirma indexación en Google. Las aprobaciones pendientes permanecen abiertas.</p><h2>Errores críticos y advertencias por URL</h2><div class="scroll"><table><thead><tr>' + ''.join('<th>'+esc(k)+'</th>' for k in fields) + '</tr></thead><tbody>' + rows + '</tbody></table></div><h2>Comparación</h2><pre>' + esc(json.dumps(result.get('comparison', []), ensure_ascii=False, indent=2)) + '</pre><p>' + esc(' '.join(result['limitations'])) + '</p></html>')
    return result


def main(argv=None):
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--target', required=True)
    p.add_argument('--urls', required=True, help='JSON matrix, not an invented crawl inventory')
    p.add_argument('--environment', required=True, choices=['staging', 'production'])
    p.add_argument('--mode', required=True, choices=['baseline', 'verify', 'articles'])
    p.add_argument('--output', required=True)
    p.add_argument('--approvals'); p.add_argument('--previous')
    p.add_argument('--interval', type=float, default=2)
    p.add_argument('--budget', type=int, default=80)
    p.add_argument('--links-per-page', type=int, default=2)
    p.add_argument('--images-per-page', type=int, default=2)
    p.add_argument('--synthetic-dns-routing', action='store_true', help='For managed environments mapping approved public hostnames into 240.0.0.0/4; TLS stays verified, host allowlist unchanged.')
    args = p.parse_args(argv)
    config = json.loads(Path(args.urls).read_text())
    allowed = config.get('allowed_targets', [])
    if args.target.rstrip('/') not in [s.rstrip('/') for s in allowed]:
        p.error('Target not in source configuration allowed_targets; explicitly review hostname first.')
    if args.interval < 1.5 or not 1 <= args.budget <= 500 or not 0 <= args.links_per_page <= 20 or not 0 <= args.images_per_page <= 20:
        p.error('Safety limits: interval >=1.5s; budget 1..500; resource samples 0..20.')
    ids = [r['id'] for r in config['urls']]
    if len(ids) != len(set(ids)):
        p.error('Duplicate row IDs')
    hosts = {url.urlsplit(args.target).hostname}
    if args.environment == 'production':
        hosts.update(config.get('production_alias_hosts', []))
    approvals = json.loads(Path(args.approvals).read_text()) if args.approvals else {}
    audit = Audit(config, args.target, args.environment, args.mode, Transport(hosts, args.interval, args.budget, synthetic_dns=args.synthetic_dns_routing), approvals,
                  args.links_per_page, args.images_per_page).run()
    result = report(audit, args.output, args.previous)
    print(json.dumps({k: result[k] for k in ('overall', 'counts', 'urls_observed', 'network_requests')}, ensure_ascii=False))
    return 1 if result['counts'].get('FAIL') else 2 if result['counts'].get('PENDING') else 0


if __name__ == '__main__':
    raise SystemExit(main())
