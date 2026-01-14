<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checkout Class for WooCommerce Integration
 */
class Checkout {

    /**
     * Create a checkout session and Return a session ID (WooCommerce Order ID)
     * 
     * @param array $params
     * @return int
     * @throws \Exception
     */
    public function create_session($params) {
        $idempotency_key = $params['idempotency_key'] ?? '';

        // Idempotency check
        if ($idempotency_key) {
            $existing_order_id = $this->get_order_by_idempotency_key($idempotency_key);
            if ($existing_order_id) {
                return $existing_order_id;
            }
        }

        if (!class_exists('WC_Checkout')) {
            throw new \Exception('WooCommerce checkout not available');
        }

        // Check if UCP gateway is enabled
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        if (!isset($gateways['ucp_gateway'])) {
            throw new \Exception(__('UCP Payment Gateway is disabled. Please enable it in WooCommerce settings.', 'ucp-for-woocommerce'));
        }

        // Basic validation
        if (empty($params['line_items'])) {
            throw new \Exception('No line items provided');
        }

        // Create a new order
        $order = wc_create_order();

        foreach ($params['line_items'] as $item) {
            $product_id = intval($item['item']['id']);
            $quantity = intval($item['quantity']);
            
            $product = wc_get_product($product_id);
            if (!$product) {
                throw new \Exception("Product ID {$product_id} not found");
            }

            // Enhanced stock validation
            if (!$product->is_in_stock()) {
                throw new \Exception(sprintf(__('Product "%s" is out of stock.', 'ucp-for-woocommerce'), $product->get_name()));
            }

            if ($product->get_stock_status() !== 'instock') {
                throw new \Exception(sprintf(__('Product "%s" is not available.', 'ucp-for-woocommerce'), $product->get_name()));
            }

            $order->add_product($product, $quantity);
        }

        // Apply coupons/discounts if provided
        if (!empty($params['discounts'])) {
            foreach ($params['discounts'] as $discount) {
                $coupon_code = $discount['code'] ?? '';
                if ($coupon_code) {
                    $result = $order->apply_coupon($coupon_code);
                    if (is_wp_error($result)) {
                        Main::log("Coupon error ({$coupon_code}): " . $result->get_error_message());
                    } else {
                        Main::log("Coupon applied: {$coupon_code}");
                    }
                }
            }
        }

        // Add buyer info if available
        if (!empty($params['buyer'])) {
            $buyer = $params['buyer'];
            $order->set_billing_first_name($buyer['full_name'] ?? '');
            $order->set_billing_email($buyer['email'] ?? '');
        }

        $order->set_currency($params['currency'] ?? 'USD');
        $order->set_payment_method('ucp_gateway');
        $order->set_payment_method_title('UCP Payment');

        if ($idempotency_key) {
            $order->update_meta_data('_ucp_woo_idempotency_key', $idempotency_key);
        }

        // Save Agent Profile if available in params (passed from API)
        if (!empty($params['agent_profile'])) {
            $order->update_meta_data('_ucp_woo_agent_profile', $params['agent_profile']);
        }

        $order->calculate_totals();

        // Security check: Max Order Total
        $max_total = floatval(Settings::get('ucp_woo_max_order_total', 500));
        if ($max_total > 0 && $order->get_total() > $max_total) {
            $order->delete(true);
            throw new \Exception(sprintf(__('Order total exceeds the maximum allowed limit (%s).', 'ucp-for-woocommerce'), $max_total));
        }

        $order->save();

        return $order->get_id();
    }

    /**
     * Get order by idempotency key
     * 
     * @param string $key
     * @return int|bool
     */
    private function get_order_by_idempotency_key($key) {
        $args = [
            'limit' => 1,
            'return' => 'ids',
            'meta_key' => '_ucp_woo_idempotency_key',
            'meta_value' => $key,
        ];
        $orders = wc_get_orders($args);
        return !empty($orders) ? $orders[0] : false;
    }

    /**
     * Calculate shipping rates (Mock implementation)
     * 
     * @param array $params
     * @return array
     */
    public function calculate_shipping_rates($params) {
        // In a real implementation, we would use WC_Cart to calculate rates
        return [
            [
                'id' => 'standard_shipping',
                'title' => 'Standard Shipping',
                'amount' => 5.00,
                'currency' => $params['currency'] ?? 'USD'
            ],
            [
                'id' => 'express_shipping',
                'title' => 'Express Shipping',
                'amount' => 15.00,
                'currency' => $params['currency'] ?? 'USD'
            ]
        ];
    }
}
