<?php
/**
 * Plugin Name: UCP for WooCommerce
 * Description: Enables Universal Commerce Protocol (UCP) for WooCommerce, allowing AI agents to discover products and perform checkout securely.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: ucp-for-woocommerce
 * Domain Path: /languages
 * Prefix: ucp_woo_
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is installed and active
function ucp_woo_check_woocommerce() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'ucp_woo_missing_woocommerce_notice');
        return false;
    }
    return true;
}

// Rewrite rule for .well-known/ucp
function ucp_woo_add_rewrite_rules() {
    add_rewrite_rule('^\.well-known/ucp/?$', 'index.php?rest_route=/ucp/v1/discovery', 'top');
}
add_action('init', 'ucp_woo_add_rewrite_rules');

function ucp_woo_missing_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php _e('UCP for WooCommerce requires WooCommerce to be installed and active.', 'ucp-for-woocommerce'); ?></p>
    </div>
    <?php
}

if (ucp_woo_check_woocommerce()) {
    // Define Constants
    define('UCP_WOO_VERSION', '1.0.0');
    define('UCP_WOO_FILE', __FILE__);
    define('UCP_WOO_PATH', plugin_dir_path(__FILE__));
    define('UCP_WOO_URL', plugin_dir_url(__FILE__));

    // Include the core class
    require_once UCP_WOO_PATH . 'includes/class-ucp-woo.php';

    // Load Composer dependencies (if exists)
    if (file_exists(UCP_WOO_PATH . 'vendor/autoload.php')) {
        require_once UCP_WOO_PATH . 'vendor/autoload.php';
    } 
    // Fallback: Load manually installed JWT library
    elseif (file_exists(UCP_WOO_PATH . 'includes/jwt/src/JWT.php')) {
        // Load interface first, before classes that implement it
        require_once UCP_WOO_PATH . 'includes/jwt/src/JWTExceptionWithPayloadInterface.php';
        // Load exception classes that implement the interface
        require_once UCP_WOO_PATH . 'includes/jwt/src/BeforeValidException.php';
        require_once UCP_WOO_PATH . 'includes/jwt/src/ExpiredException.php';
        require_once UCP_WOO_PATH . 'includes/jwt/src/SignatureInvalidException.php';
        // Load other classes
        require_once UCP_WOO_PATH . 'includes/jwt/src/Key.php';
        require_once UCP_WOO_PATH . 'includes/jwt/src/JWK.php';
        require_once UCP_WOO_PATH . 'includes/jwt/src/JWT.php';
        require_once UCP_WOO_PATH . 'includes/jwt/src/CachedKeySet.php';
    }

    // Activation hook
    register_activation_hook(UCP_WOO_FILE, 'ucp_woo_activate');

    function ucp_woo_activate() {
        ucp_woo_add_rewrite_rules();
        flush_rewrite_rules();
    }

    // Load plugin textdomain
    add_action('init', 'ucp_woo_load_textdomain');

    function ucp_woo_load_textdomain() {
        load_plugin_textdomain('ucp-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    // Initialize the plugin
    function ucp_woo_init() {
        \WooUCP\Main::instance();
    }
    add_action('plugins_loaded', 'ucp_woo_init');

    // Add settings link to plugins page
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ucp_woo_add_settings_link');
    function ucp_woo_add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=ucp-settings">' . __('Settings', 'ucp-for-woocommerce') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
