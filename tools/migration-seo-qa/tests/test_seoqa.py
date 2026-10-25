import json
import sys
import tempfile
import threading
import unittest
from unittest.mock import patch
from http.server import BaseHTTPRequestHandler, HTTPServer
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from seoqa import Audit, Page, Transport, compare, csv_safe, main, normal, public_url, report, robots_rules, sitemap_xml
from redirect_model import proposed


class Fixture(BaseHTTPRequestHandler):
    calls = []
    def log_message(self, *args): pass
    def do_POST(self):
        Fixture.calls.append(('POST', self.path)); self.send_response(405); self.end_headers()
    def do_GET(self):
        Fixture.calls.append(('GET', self.path))
        root = 'http://' + self.headers['Host']
        status, headers, body = 200, {'Content-Type': 'text/html'}, ''
        if self.path == '/robots.txt':
            headers['Content-Type'] = 'text/plain'; body = f'User-agent: *\nDisallow: /private/\nSitemap: {root}/sitemap.xml'
        elif self.path == '/sitemap.xml':
            headers['Content-Type'] = 'application/xml'; body = f'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>{root}/good/</loc></url></urlset>'
        elif self.path.startswith('/old'):
            status, headers = 301, {'Location': '/good/' + ('?' + self.path.split('?', 1)[1] if '?' in self.path else '')}
        elif self.path == '/chain': status, headers = 302, {'Location': '/old'}
        elif self.path == '/loop': status, headers = 301, {'Location': '/loop'}
        elif self.path == '/missing-location': status = 301
        elif self.path == '/blocked': status = 429
        elif self.path == '/foreign': status, headers = 301, {'Location': 'https://example.com/'}
        elif self.path == '/protected': status, headers = 301, {'Location': '/wp-admin/'}
        elif self.path == '/gone': status = 410
        elif self.path == '/missing': status = 404
        elif self.path == '/image.png': headers['Content-Type'] = 'image/png'; body = 'PNG'
        elif self.path.startswith('/good/'):
            body = f'<html><head><title>Good</title><meta name="description" content="Description"><link rel="canonical" href="{root}/good/"><meta name="robots" content="noindex, follow"></head><body><h1>Good page</h1><a href="/old">Service</a><img src="/image.png"></body></html>'
        else: body = '<head><title>Bad</title><title>Duplicate</title></head><h1>One</h1><h1>Two</h1>'
        self.send_response(status)
        for k, v in headers.items(): self.send_header(k, v)
        self.end_headers(); self.wfile.write(body.encode())


class QA(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.server = HTTPServer(('127.0.0.1', 0), Fixture)
        cls.thread = threading.Thread(target=cls.server.serve_forever, daemon=True); cls.thread.start()
        cls.root = 'http://127.0.0.1:' + str(cls.server.server_port)
    @classmethod
    def tearDownClass(cls): cls.server.shutdown(); cls.server.server_close(); cls.thread.join()
    def transport(self, budget=50): return Transport({'127.0.0.1'}, interval=0, budget=budget, local=True)
    def config(self, path='/good/', **extra):
        row = {'id': 'X', 'source_url': self.root + path, 'staging_url': self.root + path, 'action': 'CONSERVAR', **extra}
        return {'urls': [row], 'sitemap_candidates': [self.root + '/sitemap.xml']}
    def audit(self, path='/good/', env='staging', approval=None, **extra):
        return Audit(self.config(path, **extra), self.root, env, 'verify', self.transport(), approval).run()
    def test_redirect_onehop_query(self):
        r = self.transport().follow(self.root + '/old?utm_source=fixture&x=1')
        self.assertEqual(len(r['chain']), 2); self.assertTrue(r['final_url'].endswith('/good/?utm_source=fixture&x=1'))
    def test_loop(self): self.assertEqual(self.transport().follow(self.root + '/loop')['error'], 'redirect_loop')
    def test_chain(self): self.assertEqual(len(self.transport().follow(self.root + '/chain')['chain']), 3)
    def test_missing_location(self): self.assertEqual(self.transport().follow(self.root + '/missing-location')['error'], 'redirect_without_location')
    def test_external_redirect_not_fetched(self):
        t = self.transport(); r = t.follow(self.root + '/foreign'); self.assertEqual(t.count, 1); self.assertEqual(r['error'], 'blocked_by_safety_policy')
    def test_admin_not_fetched(self):
        t = self.transport(); t.follow(self.root + '/protected'); self.assertEqual(t.count, 1)
    def test_circuit_breaker(self):
        t = self.transport(); t.get(self.root + '/blocked'); self.assertIn('429', t.get(self.root + '/good/')['error']); self.assertEqual(t.count, 1)
    def test_budget(self):
        t = self.transport(1); t.get(self.root + '/good/'); self.assertEqual(t.get(self.root + '/other')['error'], 'request_budget')
    def test_cache(self):
        t = self.transport(); t.get(self.root + '/good/'); t.get(self.root + '/good/'); self.assertEqual(t.count, 1)
    def test_safe_policy(self):
        for path in ['/wp-admin/', '/cv/test.pdf', '/uploads/x.docx', '/?preview=true', '/?email=x']:
            self.assertFalse(public_url(self.root + path, {'127.0.0.1'}, True))
        self.assertFalse(public_url('file:///etc/passwd', {'127.0.0.1'}, True))
    def test_page_extraction(self):
        p = Page('<head><title>X &amp; Y</title><meta name="description" content="D"><link rel="canonical" href="/good/"></head><h1>A <span>B</span></h1>', self.root)
        self.assertEqual(p.titles, ['X & Y']); self.assertEqual(p.h1, ['A B']); self.assertEqual(p.canonicals, [self.root + '/good/'])
    def test_duplicate_canonical_header(self):
        p = Page('<link rel="canonical" href="/a">', self.root, {'link': '</a>; rel="canonical"'})
        self.assertEqual(len(p.canonicals), 2)
    def test_svg_title_is_not_seo_title(self):
        p = Page('<head><title>SEO</title></head><svg><title>Icon accessible name</title></svg>', self.root)
        self.assertEqual(p.titles, ['SEO'])
    def test_robots_directives(self):
        self.assertTrue(Page('', self.root, {'x-robots-tag': 'googlebot: noindex'}).noindex)
        self.assertFalse(Page('', self.root, {'x-robots-tag': 'bingbot: noindex'}).noindex)
        self.assertTrue(Page('<meta name="robots" content="none">', self.root).noindex)
    def test_rep_longest(self):
        robots = 'User-agent: *\nDisallow: /\nAllow: /good/\nDisallow: /*.pdf$'
        self.assertTrue(robots_rules(robots, '/good/')); self.assertFalse(robots_rules(robots, '/bad/'))
    def test_rep_specific(self):
        self.assertTrue(robots_rules('User-agent: *\nDisallow: /\nUser-agent: googlebot\nAllow: /', '/'))
    def test_xml_image_not_page(self):
        x = '<urlset xmlns:image="urn:image"><url><loc>https://x/a/</loc><image:image><image:loc>https://x/a.jpg</image:loc></image:image></url></urlset>'
        self.assertEqual(sitemap_xml(x), ('urlset', ['https://x/a/']))
    def test_xml_entity_rejected(self):
        with self.assertRaises(ValueError): sitemap_xml('<!DOCTYPE urlset><urlset/>')
    def test_staging_noindex_pass(self):
        a = self.audit(); self.assertEqual(next(f['status'] for f in a.findings if f['check'] == 'indexability'), 'PASS')
    def test_staging_indexable_fail(self):
        a = self.audit('/bad'); self.assertEqual(next(f['status'] for f in a.findings if f['check'] == 'indexability'), 'FAIL')
    def test_prod_requires_approval(self):
        a = self.audit(env='production'); self.assertTrue(any(f['check'] == 'approval' and f['status'] == 'PENDING' for f in a.findings))
    def test_prod_approved_noindex_fail(self):
        ap = {'target': self.root, 'evidence': 'fixture', 'urls': {'X': {'approved': True, 'evidence': 'fixture', 'indexable': True, 'expected_http': 200}}}
        a = self.audit(env='production', approval=ap); self.assertTrue(any(f['check'] == 'indexability' and f['status'] == 'FAIL' for f in a.findings))
    def test_draft404_not_error(self):
        a = self.audit('/missing', draft=True); self.assertEqual(next(f['status'] for f in a.findings if f['check'] == 'non_200'), 'PASS')
    def test_legitimate_gone(self):
        a = self.audit('/gone', action='REVISAR'); self.assertEqual(next(f['status'] for f in a.findings if f['check'] == 'non_200'), 'PASS')
    def test_articles_not_requested_without_publication(self):
        t = self.transport(); a = Audit(self.config(draft=True), self.root, 'production', 'articles', t).run()
        self.assertFalse(a.pages); self.assertTrue(any(f['check'] == 'publication_gate' for f in a.findings))
    def test_report_escape_and_comparison(self):
        a = self.audit(); a.add({'id': 'X'}, 'html', 'WARN', 'low', '<script>alert(1)</script>')
        with tempfile.TemporaryDirectory() as d:
            r = report(a, d); text = (Path(d) / 'report.html').read_text()
            self.assertNotIn('<script>alert', text); self.assertEqual(r['overall'], 'INCOMPLETE')
            self.assertEqual(compare(r, r), []); self.assertTrue((Path(d) / 'results.csv').exists())
        self.assertEqual(csv_safe('=SUM(A1)'), "'=SUM(A1)")
    def test_model_rules(self):
        self.assertEqual(proposed('http://americanrecruiters.com.mx/inicio/?x=1'), 'https://www.americanrecruiters.com.mx/?x=1')
        self.assertIsNone(proposed('https://www.americanrecruiters.com.mx/contacto', 'POST'))
        self.assertIsNone(proposed('https://desarrollo.americanrecruiters.mx/inicio'))
    def test_no_post_sent(self): self.assertTrue(all(method == 'GET' for method, path in Fixture.calls))
    def test_cli_integration(self):
        with tempfile.TemporaryDirectory() as d:
            cfg = self.config(); cfg['allowed_targets'] = [self.root]
            path = Path(d) / 'config.json'; path.write_text(json.dumps(cfg))
            with patch('seoqa.Transport', side_effect=lambda *args, **kwargs: self.transport()):
                code = main(['--target', self.root, '--urls', str(path), '--environment', 'staging', '--mode', 'verify', '--output', str(Path(d) / 'out')])
            result = json.loads((Path(d) / 'out/results.json').read_text())
            self.assertEqual(code, 2); self.assertEqual(result['urls_observed'], 1)
            self.assertTrue(result['network_requests'] > 0)
    def test_complete_approved_contract(self):
        ap = {'target': self.root, 'evidence': 'fixture', 'urls': {'X': {'approved': True, 'evidence': 'fixture', 'indexable': False, 'expected_http': 200}}}
        a = self.audit(approval=ap)
        self.assertFalse(any(f['status'] in ('FAIL', 'PENDING') for f in a.findings))
    def test_broken_internal_link(self):
        a = self.audit(); p = Page('<a href="/missing">Missing</a>', self.root)
        a.resources({'id': 'X', 'url': self.root}, p, self.root)
        self.assertTrue(any(f['check'].startswith('internal_link:') and f['observed'] == '404' and f['status'] == 'WARN' for f in a.findings))
    def test_failed_check_not_retested(self):
        old = {'findings': [{'id': 'X', 'check': 'http', 'status': 'FAIL', 'observed': '500'}]}
        self.assertEqual(compare({'findings': []}, old)[0]['classification'], 'not_retested')
    def test_production_sitemap_exclusion(self):
        cfg = self.config(); cfg['production_sitemap_exclusions'] = [self.root + '/good/']
        a = Audit(cfg, self.root, 'production', 'verify', self.transport()).run()
        self.assertTrue(any(f['check'] == 'sitemap_excluded_url' and f['status'] == 'FAIL' for f in a.findings))


if __name__ == '__main__': unittest.main(verbosity=2)
