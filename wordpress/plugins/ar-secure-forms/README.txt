AMERICAN RECRUITERS — SECURE FORMS 0.1.1

Purpose
-------
Independent WordPress plugin. No change to RC2 theme files, editorial content,
Home, Blog, metadata, indexing or existing Fase 9 reject-only endpoints.
Public capture is OFF by default. Installing/activating does not open either
channel, assign capabilities, schedule deletion, or configure SMTP.

Integration contract
--------------------
Keep approved configuration outside the public repository. See config.example.php.
Use the actual existing Contact/Candidates page IDs. The optional the_content
filter replaces only their existing section with class="ar-f9"; the page body
stored in WordPress is not rewritten. Pages without that known section are not
silently rebuilt. Other pages and legacy draft previews are not changed.

Business and candidate flags are independent. Global settings remain server-only.
Without complete settings the controllers reject and no data is sent or stored.
The ARV_LAB transport/scanner doubles are available only in WP environment local
with a localhost/127.0.0.1 site URL. Never install the QA fixtures on a shared host.

Endpoints
---------
admin-post.php?action=arv_token&channel=business|candidate   GET; no-store
admin-post.php?action=arv_submit&channel=business|candidate  POST; Origin + signed session-bound token
admin-post.php?action=arv_cv_download                      POST; WP login, capability, record ACL, nonce
admin-post.php?action=arv_cv_delete                        POST; separate delete capability, ACL, nonce

No public CV REST route, attachment post, public file URL or sitemap entry is
created. No analytics, GTM, conversion event, external CAPTCHA or autoreply.
No user-provided recipients, sender addresses, paths or reviewer IDs are accepted.

CSRF / duplicate semantics
-------------------------
Signed HttpOnly SameSite=Strict session cookie; Secure __Host- cookie in HTTPS.
Plain HTTP is disabled except the explicit local-only lab. Tokens are bound to
session, channel, random operation ID and expiry. A normal WP guest nonce alone
is not used as a public submission gate. WordPress nonces apply to staff actions.

Each valid operation is claimed with an exclusive filesystem create before the
transport or private storage. Repeats return its recorded result; changed data
under the same operation ID returns conflict. Pending/timeout/ambiguous outcomes
are never sent again automatically. This prevents application-level duplicate
attempts; it does not promise global exactly-once delivery by email providers.

An invalid field is correctable under the same token. A rejected PDF has not
been stored, so the response supplies a fresh operation token for a user-initiated
correction. No automatic upload or send follows that response.

Fixed rate buckets are separated by channel and HMAC(IP); no raw IP is stored in
the application ledger. Validate quotas, NAT behaviour and filesystem semantics
on the intended host. Token lifetime is 1800 seconds; ledger TTL must be >=3600.
Do not restore an old ledger into an active service or reuse expired tokens.

PDF / private files
-------------------
Server upload provenance, extension, actual size, finfo MIME and signature/EOF
are only the first layer. bin/pdf_inspect.py uses pinned pypdf in strict mode,
walks indexed objects, decodes streams under bounds, rejects encrypted/malformed
documents, attachments, active actions/forms and parser warnings. It is not AV.
The clamd Unix INSTREAM adapter must return clean; absent/error/timeout rejects.

Provide Python + bin/requirements.txt and a maintained local scanner before
candidate activation. Certify the worker sandbox, CPU/memory/time limits, binary
permissions and dependency updates on native hosting. The synthetic macOS lab
uses parser bounds and wall/CPU limits; it does not certify a Linux container or
a macOS OS-level memory cap. Never expose a PDF-processing service to the web.

Private root must be canonical, outside all public roots/aliases, initially empty
or owned by this component. Directories 0700; files 0600, explicit chmod checked.
Names are unpredictable internal IDs, never original upload names. No execution
mapping is allowed. Do not map this root with an HTTP alias.

Staff must have arv_read_cv AND membership in that record's server-assigned ACL.
Deletion additionally requires arv_delete_cv. No capabilities are auto-granted,
including to administrators. Tools > CV privados is capability-protected.
Downloads are attachment, nosniff, private/no-store, never links to static files.

Retention / backups
-------------------
Approve all durations; example values of zero disable readiness. Retention of
CV records, technical logs, ledger, temporary/orphan files and tombstones is
configurable. The hook arv_private_cleanup is provided but NOT scheduled on
installation. After policy approval an operator must deliberately schedule it
or call it from an authorized system job and verify execution/failure monitoring.

Deletion is logical unlink plus a tombstone, not a promise of physical erasure
from SSDs or historical backups. Keep a current protected suppression manifest
independently of CV snapshots. Before a restore, disable intake/access, preserve
and merge CURRENT tombstones, restore into quarantine, run reconciliation and
verify denied/deleted files cannot reappear. Restoring the complete private root
from an old backup including old tombstones is NOT a safe supported operation.
Expiry/purge of tombstones must exceed the approved backup restoration horizon.
Never add private CV storage to a public WordPress export or repository.

Activation dependencies
-----------------------
Code/settings are not evidence of legal approval, real inbox reception, sender
authentication, AV effectiveness, native permissions or hosting isolation.
Business activation needs approved notice/microcopy, confirmed destination,
authenticated sender, authorized reception test and host/security verification.
Candidate activation also needs actual staff authorization, private storage,
PDF/AV certification, retention, deletion and backup/restore procedures.
No activation is authorized by this package.

Rollback
--------
First turn intake flags OFF server-side, then disable UI replacement. Confirm
valid manual POSTs cannot send/store. Deactivate only this plugin if necessary;
the original Fase 9 interfaces/guards remain. Keep private records protected;
do not delete real records, revert an old database, or overwrite RC2/editorial
changes as a rollback shortcut. Preserve configuration, hashes, queue states
and current tombstones for controlled recovery.
