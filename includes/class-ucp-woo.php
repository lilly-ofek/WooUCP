<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
final class Main {

    /**
     * @var Main|null
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return Main
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once UCP_WOO_PATH . 'includes/class-ucp-woo-api.php';
        require_once UCP_WOO_PATH . 'includes/class-ucp-woo-discovery.php';
        require_once UCP_WOO_PATH . 'includes/class-ucp-woo-security.php';
        require_once UCP_WOO_PATH . 'includes/class-ucp-woo-checkout.php';
        require_once UCP_WOO_PATH . 'includes/class-ucp-woo-payment-gateway.php';
        require_once UCP_WOO_PATH . 'includes/class-ucp-woo-settings.php';
        require_once UCP_WOO_PATH . 'includes/class-ucp-woo-admin-orders.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('init', [$this, 'handle_well_known_discovery']);
        add_filter('woocommerce_payment_gateways', [$this, 'register_gateway']);

        if (is_admin()) {
            new Settings();
            new AdminOrders();
        }
    }

    /**
     * Register UCP Gateway
     */
    public function register_gateway($gateways) {
        $gateways[] = __NAMESPACE__ . '\PaymentGateway';
        return $gateways;
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        $api = new API();
        $api->register_routes();
    }

    /**
     * Handle /.well-known/ucp discovery
     * Simple redirect or direct response for discovery
     */
    public function handle_well_known_discovery() {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/.well-known/ucp') !== false) {
            $discovery = new Discovery();
            $discovery->send_response();
            exit;
        }
    }

    /**
     * Log message for debugging
     * 
     * @param string $message
     */
    public static function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG && Settings::get('ucp_woo_debug_mode')) {
            error_log('WooUCP: ' . $message);
        }
    }
}
