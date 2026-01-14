<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Class for UCP REST Routes
 */
class API {

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('ucp/v1', '/discovery', [
            'methods' => 'GET',
            'callback' => [$this, 'get_discovery'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('ucp/v1', '/shipping-rates', [
            'methods' => 'POST',
            'callback' => [$this, 'get_shipping_rates'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('ucp/v1', '/checkout-sessions', [
            'methods' => 'POST',
            'callback' => [$this, 'create_checkout_session'],
            'permission_callback' => '__return_true', // Validation happens in the handler
        ]);

        register_rest_route('ucp/v1', '/products', [
            'methods' => 'GET',
            'callback' => [$this, 'get_products'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Get Shipping Rates
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_shipping_rates($request) {
        $params = $request->get_json_params();
        $checkout = new Checkout();
        
        try {
            $rates = $checkout->calculate_shipping_rates($params);
            return new \WP_REST_Response(['rates' => $rates], 200);
        } catch (\Exception $e) {
            Main::log("Shipping calculation error: " . $e->getMessage());
            return new \WP_REST_Response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get Discovery Manifest
     */
    public function get_discovery() {
        $discovery = new Discovery();
        return new \WP_REST_Response($discovery->get_manifest(), 200);
    }

    /**
     * Create Checkout Session
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function create_checkout_session($request) {
        if (!$this->is_capability_enabled('checkout')) {
            return new \WP_REST_Response(['error' => 'Checkout capability is disabled'], 403);
        }

        $security = new Security();
        
        // Verify signature
        if (!$security->verify_request($request)) {
            return new \WP_REST_Response(['error' => 'Invalid signature'], 401);
        }

        $params = $request->get_json_params();
        $idempotency_header = $request->get_header('idempotency-key');
        if ($idempotency_header) {
            $params['idempotency_key'] = $idempotency_header;
        }

        // Extract profile for metadata
        $agent_header = $request->get_header('UCP-Agent');
        preg_match('/profile="([^"]+)"/', $agent_header, $matches);
        $params['agent_profile'] = $matches[1] ?? '';

        $checkout = new Checkout();
        
        try {
            $session_id = $checkout->create_session($params);
            Main::log("Checkout session created: {$session_id}");
            return new \WP_REST_Response(['checkout_id' => $session_id], 201);
        } catch (\Exception $e) {
            Main::log("Checkout creation error: " . $e->getMessage());
            return new \WP_REST_Response(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get Products for AI Discovery
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_products($request) {
        if (!$this->is_capability_enabled('discovery')) {
            return new \WP_REST_Response(['error' => 'Product discovery is disabled'], 403);
        }

        $args = [
            'status' => 'publish',
            'limit' => 10, // Limit for better performance
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $products = wc_get_products($args);
        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->get_id(),
                'title' => $product->get_name(),
                'description' => wp_strip_all_tags($product->get_short_description() ?: $product->get_description()),
                'price' => $product->get_price(),
                'currency' => get_woocommerce_currency(),
                'url' => $product->get_permalink(),
                'image' => wp_get_attachment_url($product->get_image_id()),
                'stock' => $product->get_stock_status(),
            ];
        }

        return new \WP_REST_Response(['products' => $data], 200);
    }

    /**
     * Check if a capability is enabled in settings
     * 
     * @param string $cap
     * @return bool
     */
    private function is_capability_enabled($cap) {
        $enabled = Settings::get('ucp_woo_enabled_capabilities', ['checkout', 'discovery']);
        return is_array($enabled) && in_array($cap, $enabled);
    }
}
