<?php 
/**
 * Plugin Name: Heyzine Content Display
 * Description: Embed Heyzine flipbooks and display flipbook lists with Gutenberg blocks and shortcodes.
 * Version: 1.0.0
 * Author: Michael Ilg
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: heyzine-content-display
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'admin.php';
require_once plugin_dir_path(__FILE__) . 'api.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'blocks.php';
require_once plugin_dir_path(__FILE__) . 'common.php';

function hcd_load_textdomain() {
    load_plugin_textdomain(
        'heyzine-content-display',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'hcd_load_textdomain');

// Bump this when the plugin's rewrite rules change independently of their settings.
define('HCD_REWRITE_RULES_VERSION', '2');

function hcd_get_rewrite_rules_signature() {
    $pretty_urls_enabled = hcd_is_pretty_urls_enabled();

    return HCD_REWRITE_RULES_VERSION . ':'
        . ($pretty_urls_enabled ? 'pretty:' . hcd_get_rewrite_slug() : 'query');
}

function hcd_refresh_rewrite_rules_if_needed() {
    $rewrite_rules_signature = hcd_get_rewrite_rules_signature();
    if (get_option('hcd_rewrite_rules_version') === $rewrite_rules_signature) {
        return;
    }

    flush_rewrite_rules();
    update_option('hcd_rewrite_rules_version', $rewrite_rules_signature, false);
}
add_action('init', 'hcd_refresh_rewrite_rules_if_needed', 99);

function hcd_activate_plugin() {
    hcd_register_rewrite_rules();
    flush_rewrite_rules();
    update_option('hcd_rewrite_rules_version', hcd_get_rewrite_rules_signature(), false);
}
register_activation_hook(__FILE__, 'hcd_activate_plugin');

function hcd_deactivate_plugin() {
    delete_option('hcd_rewrite_rules_version');
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'hcd_deactivate_plugin');
