<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UCP Payment Gateway
 */
class PaymentGateway extends \WC_Payment_Gateway {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'ucp_gateway';
        $this->icon = ''; // Optional: Add a UCP icon URL
        $this->has_fields = false;
        $this->method_title = __('UCP Payment', 'ucp-for-woocommerce');
        $this->method_description = __('Handles payments processed through the Universal Commerce Protocol.', 'ucp-for-woocommerce');

        // Load settings
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', 'UCP Payment');
        $this->description = $this->get_option('description', 'Secure payment via AI Agent.');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
    }

    /**
     * Initial Gateway Settings Form Fields
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'ucp-for-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable UCP Payment', 'ucp-for-woocommerce'),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __('Title', 'ucp-for-woocommerce'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'ucp-for-woocommerce'),
                'default'     => __('UCP Payment', 'ucp-for-woocommerce'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'ucp-for-woocommerce'),
                'type'        => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'ucp-for-woocommerce'),
                'default'     => __('Secure payment via AI Agent.', 'ucp-for-woocommerce'),
            ],
        ];
    }

    /**
     * Process Payment
     * 
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $status = Settings::get('ucp_woo_default_order_status', 'processing');

        // Mark as configured status (we've already processed it via UCP)
        $order->update_status($status, __('Payment confirmed via UCP.', 'ucp-for-woocommerce'));

        // Reduce stock levels
        wc_reduce_stock_levels($order_id);

        // Remove cart
        WC()->cart->empty_cart();

        // Return thank you redirect
        return [
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        ];
    }
}
