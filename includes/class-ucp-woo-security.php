<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Class for UCP
 */
class Security {

    /**
     * Verify the UCP request signature and rate limit
     * 
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function verify_request($request) {
        if (!$this->check_rate_limit()) {
            return false;
        }

        $signature = $request->get_header('request-signature');
        $agent_header = $request->get_header('UCP-Agent');
        
        // Mode for development or "trusted" agents without full JWT check
        if (Settings::get('ucp_woo_dev_mode') && $signature === 'test') {
            return true;
        }

        if (empty($signature) || empty($agent_header)) {
            return false;
        }

        if (!class_exists('\Firebase\JWT\JWT')) {
            Main::log('firebase/php-jwt library is missing. Cannot verify JWT signatures.');
            return false;
        }

        // Extract profile URL from UCP-Agent header
        // Header format: profile="https://agent.example/profile"
        preg_match('/profile="([^"]+)"/', $agent_header, $matches);
        $profile_url = $matches[1] ?? '';

        if (empty($profile_url)) {
            return false;
        }

        // Check whitelist
        $whitelist_raw = Settings::get('ucp_woo_agent_whitelist');
        if (!empty($whitelist_raw)) {
            $whitelist = array_filter(array_map('trim', explode("\n", $whitelist_raw)));
            if (!empty($whitelist) && !in_array($profile_url, $whitelist)) {
                Main::log("Agent profile blocked by whitelist: {$profile_url}");
                return false;
            }
        }

        try {
            $public_keys = $this->get_agent_public_keys($profile_url);
            if (empty($public_keys)) {
                return false;
            }

            // Verify the JWS signature using the fetched JWKs
            // We use the request body as the payload for verification if it's a detached signature,
            // or decode the JWS directly if it's a standard JWT.
            // Note: UCP typically uses JWS with detached payload or standard JWS.
            $keys = \Firebase\JWT\JWK::parseKeySet($public_keys);
            
            // Assuming standard JWS for now as per usual JWT library usage
            // In a real UCP scenario, we might need to handle detached payloads.
            $decoded = \Firebase\JWT\JWT::decode($signature, $keys);

            return (bool) $decoded;
        } catch (\Exception $e) {
            Main::log('Security Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch and cache agent public keys
     * 
     * @param string $profile_url
     * @return array
     */
    private function get_agent_public_keys($profile_url) {
        // Mocking for "dress rehearsal" testing
        if (strpos($profile_url, 'mock-agent.com') !== false) {
            $jwk_path = plugin_dir_path(dirname(__FILE__)) . 'tests/public_keys.json';
            if (file_exists($jwk_path)) {
                $keys = json_decode(file_get_contents($jwk_path), true);
                if ($keys) {
                    return $keys;
                }
            }
        }

        $cache_key = 'ucp_woo_keys_' . md5($profile_url);
        $keys = get_transient($cache_key);

        if ($keys !== false && strpos($profile_url, 'mock-agent.com') === false) {
            return $keys;
        }

        $response = wp_remote_get($profile_url);
        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // UCP profile should have a 'signing_keys' field with JWK set
        $keys = $data['signing_keys'] ?? [];

        if (!empty($keys)) {
            set_transient($cache_key, $keys, HOUR_IN_SECONDS);
        }

        return $keys;
    }

    /**
     * Sanitize incoming data
     * 
     * @param array $data
     * @return array
     */
    public function sanitize_data($data) {
        if (!is_array($data)) {
            return [];
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[sanitize_key($key)] = $this->sanitize_data($value);
            } else {
                $sanitized[sanitize_key($key)] = sanitize_text_field($value);
            }
        }
        return $sanitized;
    }

    /**
     * Simple transient-based rate limiter
     * 
     * @return bool
     */
    private function check_rate_limit() {
        $limit = intval(Settings::get('ucp_woo_api_rate_limit', 60));
        if ($limit <= 0) {
            return true;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $cache_key = 'ucp_rate_limit_' . md5($ip);
        $count = get_transient($cache_key);

        if ($count === false) {
            set_transient($cache_key, 1, MINUTE_IN_SECONDS);
            return true;
        }

        if ($count >= $limit) {
            Main::log("Rate limit exceeded for IP: {$ip}");
            return false;
        }

        set_transient($cache_key, $count + 1, MINUTE_IN_SECONDS);
        return true;
    }
}
