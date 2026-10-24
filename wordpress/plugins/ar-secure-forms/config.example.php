<?php
/* EXAMPLE ONLY. Keep the completed configuration outside a public repository.
 * None of these acknowledgements is an approval, verification or permission.
 * Defaults deliberately do not activate capture, assign roles, or schedule jobs.
 */
define('ARV_CONFIG', [
    'private_root'=>'', // Canonical absolute path outside ALL webroots and aliases.
    'webroots'=>[],    // Additional canonical public roots on the same hosting account.
    'python'=>'',      // Absolute Python executable; install bin/requirements.txt separately.
    'scanner_socket'=>'', // Local clamd Unix socket, e.g. unix:///run/clamav/clamd.ctl.
    'replace_demo'=>false,
    'page_ids'=>['business'=>0,'candidate'=>0], // Confirm actual existing pages; do not create pages.
    'ledger_ttl'=>0,   // Must exceed CSRF lifetime. Minimum supported: 3600 seconds.
    'rate_limit'=>0,'rate_window'=>0,
    'log_retention_days'=>0,'tombstone_days'=>0,'temporary_ttl'=>0,
    'cv_access_enabled'=>false,
    'business'=>[
        'enabled'=>false,'legal_approved'=>false,'security_verified'=>false,
        'transport_enabled'=>false,'recipient_confirmed'=>false,'sender_authenticated'=>false,
        'to'=>'','from'=>'','subject'=>'',
        'notice_version'=>'','notice_url'=>'','privacy_text'=>'',
        'ack_mode'=>'','ack_text'=>'',
    ],
    'candidate'=>[
        'enabled'=>false,'legal_approved'=>false,'security_verified'=>false,
        'storage_verified'=>false,'scanner_verified'=>false,
        'retention_approved'=>false,'backup_policy_confirmed'=>false,
        'retention_days'=>0,'max_bytes'=>0,'reviewers'=>[],
        'notice_version'=>'','notice_url'=>'','privacy_text'=>'',
        'ack_mode'=>'','ack_text'=>'',
    ],
]);
