<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Discovery Class for UCP
 */
class Discovery {

    /**
     * Send the UCP discovery response
     */
    public function send_response() {
        $manifest = $this->get_manifest();
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        Main::log("Discovery manifest served via " . $_SERVER['REQUEST_METHOD']);
        exit;
    }

    /**
     * Get the UCP manifest
     * 
     * @return array
     */
    public function get_manifest() {
        $rest_url = get_rest_url(null, 'ucp/v1/');

        return [
            'ucp' => [
                'version' => '2026-01-11',
                'services' => [
                    'dev.ucp.shopping' => [
                        'version' => '2026-01-11',
                        'spec' => 'https://ucp.dev/specs/shopping',
                        'rest' => [
                            'schema' => 'https://ucp.dev/services/shopping/openapi.json',
                            'endpoint' => $rest_url
                        ],
                        'shipping' => [
                            'zones' => $this->get_active_shipping_zones()
                        ]
                    ]
                ],
                'capabilities' => $this->get_enabled_capabilities()
            ],
            'payment' => [
                'handlers' => $this->get_active_payment_handlers()
            ]
        ];
    }

    /**
     * Get enabled capabilities based on settings
     * 
     * @return array
     */
    private function get_enabled_capabilities() {
        $enabled = Settings::get('ucp_woo_enabled_capabilities', ['checkout', 'discovery']);
        $capabilities = [];

        if (in_array('checkout', $enabled)) {
            $capabilities[] = [
                'name' => 'dev.ucp.shopping.checkout',
                'version' => '2026-01-11',
                'spec' => 'https://ucp.dev/specs/shopping/checkout',
                'schema' => 'https://ucp.dev/schemas/shopping/checkout.json',
                'custom_fields' => $this->get_custom_field_definitions()
            ];
        }

        if (in_array('discovery', $enabled)) {
            $capabilities[] = [
                'name' => 'dev.ucp.shopping.product_discovery',
                'version' => '2026-01-11',
                'spec' => 'https://ucp.dev/specs/shopping/discovery',
                'schema' => 'https://ucp.dev/schemas/shopping/discovery.json'
            ];
        }

        return $capabilities;
    }

    /**
     * Get definitions for custom checkout fields from mapping
     * 
     * @return array
     */
    private function get_custom_field_definitions() {
        $mapping_raw = Settings::get('ucp_woo_field_mapping');
        if (empty($mapping_raw)) {
            return [];
        }

        $mappings = array_filter(array_map('trim', explode("\n", $mapping_raw)));
        $definitions = [];

        foreach ($mappings as $line) {
            if (strpos($line, '|') === false) continue;
            list($ucp_key, $woo_key) = explode('|', $line, 2);
            $ucp_key = trim($ucp_key);
            
            $definitions[] = [
                'name' => str_replace(['_', '-'], ' ', ucfirst($ucp_key)),
                'key' => $ucp_key,
                'required' => true
            ];
        }

        return $definitions;
    }

    /**
     * Get active WooCommerce payment gateways as UCP handlers
     * 
     * @return array
     */
    private function get_active_payment_handlers() {
        if (!function_exists('WC')) {
            return [];
        }

        $gateways = WC()->payment_gateways->get_available_payment_gateways();
        $handlers = [];

        foreach ($gateways as $id => $gateway) {
            $handlers[] = [
                'id' => $id,
                'name' => 'woo.gateway.' . $id,
                'title' => $gateway->get_title(),
                'version' => '2026-01-11',
                'instrument_schemas' => [
                    'https://ucp.dev/schemas/shopping/types/card_payment_instrument.json'
                ]
            ];
        }

        return $handlers;
    }

    /**
     * Get active WooCommerce shipping zones and methods
     * 
     * @return array
     */
    private function get_active_shipping_zones() {
        if (!class_exists('WC_Shipping_Zones')) {
            return [];
        }

        $zones = \WC_Shipping_Zones::get_zones();
        $shipping_data = [];

        foreach ($zones as $zone_data) {
            $methods = [];
            foreach ($zone_data['shipping_methods'] as $method) {
                if ($method->enabled === 'yes') {
                    $methods[] = [
                        'id' => $method->id,
                        'name' => $method->get_title(),
                    ];
                }
            }

            if (!empty($methods)) {
                $shipping_data[] = [
                    'zone_name' => $zone_data['zone_name'],
                    'methods' => $methods
                ];
            }
        }

        return $shipping_data;
    }
}
