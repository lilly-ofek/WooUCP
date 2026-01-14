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
                        ]
                    ]
                ],
                'capabilities' => $this->get_enabled_capabilities()
            ],
            'payment' => [
                'handlers' => [
                    [
                        'id' => 'mock_payment_handler',
                        'name' => 'dev.ucp.mock_payment',
                        'version' => '2026-01-11',
                        'spec' => 'https://ucp.dev/specs/mock',
                        'config_schema' => 'https://ucp.dev/schemas/mock.json',
                        'instrument_schemas' => [
                            'https://ucp.dev/schemas/shopping/types/card_payment_instrument.json'
                        ],
                        'config' => [
                            'supported_tokens' => ['success_token', 'fail_token']
                        ]
                    ]
                ]
            ]
        ];
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
                'schema' => 'https://ucp.dev/schemas/shopping/checkout.json'
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
}
