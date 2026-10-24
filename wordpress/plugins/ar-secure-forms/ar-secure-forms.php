<?php
/**
 * Plugin Name: American Recruiters — Secure Forms
 * Description: Independent, fail-closed business and candidate intake. Requires explicit server configuration.
 * Version: 0.1.1
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/src/Core.php';
require_once __DIR__.'/src/PrivateStore.php';
require_once __DIR__.'/src/Pdf.php';
require_once __DIR__.'/src/Plugin.php';
add_action('plugins_loaded',static function(){ \ARV\Plugin::boot(__FILE__); });
register_deactivation_hook(__FILE__,function(){ wp_clear_scheduled_hook('arv_private_cleanup'); });

