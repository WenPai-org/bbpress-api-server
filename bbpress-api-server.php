<?php
/**
 * Plugin Name: bbPress API Server
 * Plugin URI: https://wenpai.org/plugins/bbpress-api-server
 * Description: Provides comprehensive API endpoints for bbPress forums, topics, and replies with creation capabilities
 * Version: 2.0.0
 * Author: WPTopic.com
 * Author URI: https://wptopic.com
 * Text Domain: bbpress-api-server
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires Plugins: bbpress
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BBPAS_VERSION', '2.0.0');
define('BBPAS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BBPAS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BBPAS_API_NAMESPACE', 'bbpas/v2');

// Load dependent files
require_once BBPAS_PLUGIN_DIR . 'includes/endpoints.php';
require_once BBPAS_PLUGIN_DIR . 'includes/helpers.php';
require_once BBPAS_PLUGIN_DIR . 'includes/admin.php';

// Check if bbPress is active
function bbpas_check_required_plugins() {
    if (!class_exists('bbPress')) {
        add_action('admin_notices', 'bbpas_admin_notice');
        return false;
    }
    return true;
}

// Display admin notice if bbPress is not active
function bbpas_admin_notice() {
    echo '<div class="notice notice-error"><p>' . __('bbPress API Server requires bbPress to be installed and activated.', 'bbpress-api-server') . '</p></div>';
}

// Load plugin text domain for translations
function bbpas_load_textdomain() {
    load_plugin_textdomain(
        'bbpress-api-server',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'bbpas_load_textdomain');

// Initialize plugin
add_action('plugins_loaded', function() {
    if (bbpas_check_required_plugins()) {
        add_action('rest_api_init', 'bbpas_register_routes');
    }
});

// Activation hook
register_activation_hook(__FILE__, function() {
    if (!class_exists('bbPress')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('bbPress API Server requires bbPress to be installed and activated.', 'bbpress-api-server'));
    }
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Add admin menu
add_action('admin_menu', function() {
    if (!bbpas_check_required_plugins()) {
        return;
    }
    add_submenu_page(
        'options-general.php',
        __('bbPress API Server', 'bbpress-api-server'),
        __('bbPress API', 'bbpress-api-server'),
        'manage_options',
        'bbpress-api-server',
        'bbpas_admin_page'
    );
});

// Integrate UpdatePulse Server for updates using PUC v5.3
require_once plugin_dir_path(__FILE__) . 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5p3\PucFactory;

$BbpressApiServerUpdateChecker = PucFactory::buildUpdateChecker(
    'https://updates.weixiaoduo.com/bbpress-api-server.json',
    __FILE__,
    'bbpress-api-server'
);
