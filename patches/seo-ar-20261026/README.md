# AR — Corporate author graph • 2026-10-26

**For AQ review and integration. Not installed. Do not deploy to production.**

## What is already fixed through native Yoast configuration

- Site representation: Organization, name `American Recruiters México`, exact existing logo reused as attachment 889. No invented legal name/address.
- The real frontend now emits Article and WebSite.publisher linked to the Yoast corporate Organization on all 17 articles.
- Page 27 has explicit Yoast noindex=1, persisted after reload. Anonymous page-sitemap.xml excludes `/sistema-visual/`; that URL still returns 302 to login. No permission change.
- Page 19 description, Blog 22 pagination title/description, and descriptions for categories 4/5/6/7 saved and verified. Privacy remains a legal placeholder, without invented description.

## Remaining code issue

The legacy AJ block is limited to 11 historical IDs and the staging hostname. Six new articles still identify the visible corporate signature as Person. The patch extends the **existing Yoast graph**, keeps publisher/dates/headline/images intact, and resolves all 17 authors to one editorial Organization representing the documented collective byline. It does not assert a second legal company or output another JSON-LD script.

## Integration steps — AQ only

1. Snapshot the currently deployed theme and DB. Current UI version during AR close: `1.1.7-v2.1-aq1`; the diff was generated against the prior AL2/AQ backup's AJ block. Inspect the current file before applying; never overwrite AQ's whole functions.php.
2. Remove the entire old block beginning `/* AJ: approved corporate signature... */`, including its two anonymous author/schema filters. Keeping both would stack conflicting rules.
3. Copy `editorial-author.php` into `wordpress/ar-editorial/inc/editorial-author.php` in the theme working branch.
4. Add `require_once __DIR__.'/inc/editorial-author.php';` once to functions.php. `remove-legacy-aj.diff` demonstrates this change only. Check its context and end-of-file assumptions; integrate manually if other work has appended code.
5. Verify all 17 IDs still identify the intended posts. If migration remaps IDs, update the explicit allowlist against the approved ID mapping before deployment. No slug changes are part of this patch.
6. Run PHP syntax and fixture tests. Then deploy to an authorised isolated copy/staging in a separate AQ step. This handoff does **not** authorise production migration.
7. Check actual HTML for all 17: one Article/BlogPosting, one editorial Organization node, resolving author references, one existing publisher Organization, dates/headline/logo/URL unchanged, noindex retained on staging. Confirm no new manual JSON-LD.
8. Repeat on final-domain configuration in an isolated clone, then after an explicitly authorised launch. Allowlist covers staging and both documented final-domain hosts; entity URLs derive from home_url.

## Tests and limits

`php -l editorial-author.php` and `php test-graph.php` work with native PHP 8.0+.

Optional existing PHP-WASM runtime:

```sh
PHP_WASM_ROOT=/path/to/existing/node_modules node run-tests.mjs
```

Captured public JSON-LD fixtures: 17 articles after native publisher correction. The suite evaluates all 17 on three host configurations, idempotency, node uniqueness, preservation of non-author Article/WebPage properties, non-target page/host/archive safety, and retention of a Person still referenced elsewhere. PHP 8.0 and 8.3: **363 assertions each, zero failures**, plus syntax PASS. `test-summary.json` records the checked execution totals; running the suite generates `test-results.json` with every case (the full captured result is also in the local AR delivery).

These are PHP unit/fixture tests, **not a claim that integration is installed or that a complete WordPress clone has passed**. Native schema generation, search visibility, and Google's interpretation still require the post-integration HTML checks above.

## Rollback

Remove the one require, restore the backed-up AJ block, and restore the original functions.php only after reconciling AQ's concurrent changes. Never roll back the entire theme over AQ work. No DB changes are made by this patch. Native publisher/metadata changes have a separate field-level rollback log in the AR delivery.

## Sources

- Google Article: https://developers.google.com/search/docs/appearance/structured-data/article
- Yoast Article: https://developer.yoast.com/features/schema/pieces/article/
- Yoast graph filter: https://developer.yoast.com/features/schema/api/

Keep this branch unmerged until AQ approves integration. No credentials, private WXR/Yoast exports or admin screenshots are included.
