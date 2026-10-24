ISOLATED QA ONLY — NO SHARED WORDPRESS / NO REAL MAIL / NO REAL CV

Install package.json dependencies with the official npm registry. Use Node 24,
the official cached WordPress ZIP ~/.wordpress-playground/7.1.3.zip (or 6.6.2.zip),
Python 3.9+ and the pinned plugin bin/requirements.txt. No production backup,
wp-config, account credentials, SMTP settings or client CV is needed.

Set ARV_LAB_DIR to a NEW dedicated private directory outside this repo, public
roots and aliases. Optional contact-content.html / candidate-content.html in
that directory may contain authorized reference HTML for local visual QA only.
Otherwise minimal synthetic placeholders are used. They are not published.

Environment:
QA_PHP=8.3 (or 8.0)
QA_WP=7.1.3 (or 6.6.2)
ARV_PYTHON=/absolute/path/to/python3
ARV_PYTHONPATH=/private/path/to/pinned/python/dependencies (if needed)
ARV_PORT=9420 (choose a free port)
ARV_RUN_TESTS=1
ARV_EXIT=1 to stop after tests
ARV_DEFAULTS=1 for the separate default-off HTTP smoke test

Execute node tools/forms-v2/lab.mjs from the repository root.

The native listener binds ONLY to 127.0.0.1; incoming non-loopback clients are
rejected. WordPress outbound mail is intercepted; HTTP from WordPress is blocked.
The transport/scanner are explicit synthetic doubles in the local-only fixture.
The actual PHP ProcessInspector calls the real pinned Python parser through a
restricted WASM argv-to-native bridge. No shell or arbitrary binary is allowed.
WordPress installer mail, if attempted, is blocked and recorded separately.

Tests cover both request handlers and the underlying controls. Six independent
PHP WASM instances test exclusive claims and rate slots on the shared native
filesystem, beyond the HTTP handler's single-runtime queue. This is not MySQL,
NFS, native PHP-FPM, clamd certification or shared-host parity.

The lab serves existing static files normally; a positive synthetic public-PDF
control proves accidental public uploads would be detectable. Only nonexistent
PDF paths receive a static 404 instead of WordPress's trailing-slash fallback.
No canonical/rewrite filters are installed in the theme or shared WordPress.

For browser QA /qa/view.php contains a LIVE same-origin WordPress iframe with
selectable 360,390,768,1024,1440 px widths. Verify the CHILD window.innerWidth
and scrollWidth, not the outer browser width. It is not a static HTML copy or a
physical mobile device. Capture errors, success, keyboard and both forms.

Runtime config, evidence, synthetic storage, cookies and screenshots stay outside
the repo. Do not upload the lab directory. Remove synthetic CV records through
the tested deletion flow or authorized fixture cleanup before retiring the lab.
