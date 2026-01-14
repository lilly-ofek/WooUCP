<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Orders Class to handle order metaboxes
 */
class AdminOrders {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_ucp_metabox']);
    }

    /**
     * Add UCP Agent metadata box
     */
    public function add_ucp_metabox() {
        add_meta_box(
            'ucp_agent_info',
            __('UCP Agent Info', 'ucp-for-woocommerce'),
            [$this, 'render_metabox'],
            'shop_order',
            'side',
            'default'
        );
    }

    /**
     * Render the metabox content
     * 
     * @param \WP_Post $post
     */
    public function render_metabox($post) {
        $order = wc_get_order($post->ID);
        $agent_profile = $order->get_meta('_ucp_woo_agent_profile');
        $idempotency_key = $order->get_meta('_ucp_woo_idempotency_key');

        if (empty($agent_profile) && empty($idempotency_key)) {
            echo '<p>' . __('This order was not placed via UCP.', 'ucp-for-woocommerce') . '</p>';
            return;
        }

        echo '<div class="ucp-meta-box">';
        if ($agent_profile) {
            echo '<p><strong>' . __('Agent Profile:', 'ucp-for-woocommerce') . '</strong><br>';
            echo '<pre style="white-space: pre-wrap; word-break: break-all; background: #f9f9f9; padding: 5px;">' . esc_url($agent_profile) . '</pre></p>';
        }
        if ($idempotency_key) {
            echo '<p><strong>' . __('Idempotency Key:', 'ucp-for-woocommerce') . '</strong><br>';
            echo '<code>' . esc_html($idempotency_key) . '</code></p>';
        }
        echo '</div>';
    }
}
