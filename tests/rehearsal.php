<?php
/**
 * Security Rehearsal Tool for WooUCP
 * This script generates keys and signatures for local security testing.
 */

// 1. Robust WordPress loading
if (!defined('ABSPATH')) {
    $current_dir = __DIR__;
    // Go up levels until we find wp-load.php
    while (!file_exists($current_dir . '/wp-load.php')) {
        $current_dir = dirname($current_dir);
        if ($current_dir === '/' || $current_dir === '.' || strlen($current_dir) < 3) {
            die("Error: Could not find wp-load.php. Please access this tool via the WordPress dashboard link.");
        }
    }
    require_once $current_dir . '/wp-load.php';
}

if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// 2. Locate autoloader or manual JWT library
$plugin_path = plugin_dir_path(__DIR__);
if (file_exists($plugin_path . 'vendor/autoload.php')) {
    require_once $plugin_path . 'vendor/autoload.php';
} elseif (file_exists($plugin_path . 'includes/jwt/src/JWT.php')) {
    require_once $plugin_path . 'includes/jwt/src/JWTExceptionWithPayloadInterface.php';
    require_once $plugin_path . 'includes/jwt/src/BeforeValidException.php';
    require_once $plugin_path . 'includes/jwt/src/ExpiredException.php';
    require_once $plugin_path . 'includes/jwt/src/SignatureInvalidException.php';
    require_once $plugin_path . 'includes/jwt/src/Key.php';
    require_once $plugin_path . 'includes/jwt/src/JWK.php';
    require_once $plugin_path . 'includes/jwt/src/JWT.php';
} else {
    wp_die("Error: JWT library not found in " . esc_html($plugin_path) . "includes/jwt/src/JWT.php");
}

if (!class_exists('\Firebase\JWT\JWT')) {
    wp_die("Error: JWT class not found after loading. Please check the library installation.");
}

use Firebase\JWT\JWT;

$testsDir = __DIR__;
$keyFile = $testsDir . '/private_key.pem';
$jwkFile = $testsDir . '/public_keys.json';

// Handle Regeneration
if (isset($_POST['regen_keys'])) {
    check_admin_referer('ucp_regen_keys');
    @unlink($keyFile);
    @unlink($jwkFile);
}

// 1. Generate or Load keys
$privateKey = false;
if (file_exists($keyFile)) {
    $privateKey = file_get_contents($keyFile);
    // Validate the existing key
    if (!openssl_pkey_get_private($privateKey)) {
        $privateKey = false; // Force regeneration if invalid
    }
}

if (!$privateKey) {
    if (file_exists($keyFile)) @unlink($keyFile);
    if (file_exists($jwkFile)) @unlink($jwkFile);

    $res = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);
    
    if (!$res) {
        wp_die("Error: OpenSSL could not generate keys. Please check your PHP OpenSSL extension configuration.");
    }

    openssl_pkey_export($res, $privateKey);
    file_put_contents($keyFile, $privateKey);

    $details = openssl_pkey_get_details($res);
    
    $jwk = [
        "keys" => [
            [
                "kty" => "RSA",
                "n" => str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($details['rsa']['n'])),
                "e" => str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($details['rsa']['e'])),
                "kid" => "rehearsal-key-1",
                "use" => "sig",
                "alg" => "RS256"
            ]
        ]
    ];
    file_put_contents($jwkFile, json_encode($jwk, JSON_PRETTY_PRINT));
}

// 2. Generate JWT
$payload = [
    "iss" => "https://mock-agent.com",
    "iat" => time(),
    "exp" => time() + 7200,
    "jti" => bin2hex(random_bytes(10))
];

$token = JWT::encode($payload, $privateKey, 'RS256', 'rehearsal-key-1');

// UI Rendering
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <title><?php _e('Security Rehearsal - WooUCP', 'ucp-for-woocommerce'); ?></title>
    <link rel="stylesheet" href="<?php echo admin_url('css/common.css'); ?>">
    <link rel="stylesheet" href="<?php echo admin_url('css/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo admin_url('css/buttons.css'); ?>">
    <link rel="stylesheet" href="<?php echo plugins_url('assets/css/admin.css', __DIR__); ?>">
    <style>
        body { background: #f0f2f5; }
        .rehearsal-container {
            max-width: 900px;
            margin: 50px auto;
            padding: 40px;
        }
        .rehearsal-card {
            background: #fff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.1);
            border: 1px solid #e1e7ef;
        }
        .code-box {
            background: #1e293b;
            color: #e2e8f0;
            padding: 24px;
            border-radius: 12px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 14px;
            line-height: 1.6;
            overflow-x: auto;
            margin: 20px 0;
            position: relative;
            border: 1px solid #334155;
        }
        .code-box label {
            position: absolute;
            top: 0;
            right: 0;
            background: #334155;
            color: #94a3b8;
            padding: 4px 12px;
            font-size: 10px;
            text-transform: uppercase;
            border-radius: 0 12px 0 12px;
        }
        .step-badge {
            display: inline-block;
            background: var(--ucp-primary);
            color: #fff;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            border-radius: 50%;
            margin-right: 12px;
            font-size: 14px;
            font-weight: 700;
        }
        h2 { margin-top: 0; display: flex; align-items: center; font-size: 24px; }
        .regen-btn { margin-top: 20px; color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-block; }
        .regen-btn:hover { color: #dc2626; }
    </style>
</head>
<body class="ucp-settings-wrap">
    <div class="rehearsal-container">
        <div class="ucp-header">
            <div>
                <h1><?php _e('Security Rehearsal', 'ucp-for-woocommerce'); ?></h1>
                <p><?php _e('Testing your JWT integration with local simulation keys.', 'ucp-for-woocommerce'); ?></p>
            </div>
            <div class="version-tag">Simulation Mode</div>
        </div>

        <div class="rehearsal-card">
            <h2><span class="step-badge">1</span> <?php _e('Your Public Keys (JWKS)', 'ucp-for-woocommerce'); ?></h2>
            <p><?php _e('The plugin is currently set to trust these keys for "mock-agent.com" requests.', 'ucp-for-woocommerce'); ?></p>
            <div class="code-box">
                <label>public_keys.json</label>
                <pre><?php echo esc_html(file_get_contents($jwkFile)); ?></pre>
            </div>

            <h2 style="margin-top:40px;"><span class="step-badge">2</span> <?php _e('Generate Postman Token', 'ucp-for-woocommerce'); ?></h2>
            <p><?php _e('Copy this token and use it as your <strong>Bearer Token</strong> in Postman.', 'ucp-for-woocommerce'); ?></p>
            <div class="code-box">
                <label>Signed JWT</label>
                <code style="word-break: break-all;"><?php echo esc_html($token); ?></code>
            </div>

            <div style="margin-top:30px; padding: 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                <strong><?php _e('Postman Setup:', 'ucp-for-woocommerce'); ?></strong>
                <ul style="margin: 10px 0 0 20px; color: #475569; font-size: 14px;">
                    <li><strong>Method:</strong> POST</li>
                    <li><strong>URL:</strong> <code><?php echo esc_url(get_rest_url(null, 'ucp/v1/checkout-sessions')); ?></code></li>
                    <li><strong>Header:</strong> <code>UCP-Agent: profile="https://mock-agent.com/agent"</code></li>
                    <li><strong>Auth:</strong> Bearer Token (paste the code above)</li>
                </ul>
            </div>

            <form method="post" style="text-align: center;">
                <?php wp_nonce_field('ucp_regen_keys'); ?>
                <button type="submit" name="regen_keys" class="regen-btn" style="background:none; border:none; cursor:pointer;" onclick="return confirm('<?php _e('Are you sure? This will invalidate previous tokens.', 'ucp-for-woocommerce'); ?>');">
                    ⚠ <?php _e('Regenerate All Security Keys', 'ucp-for-woocommerce'); ?>
                </button>
            </form>
        </div>
        
        <p style="text-align: center; margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=ucp-settings'); ?>" style="text-decoration: none; color: #64748b; font-weight: 500;">
                ← <?php _e('Back to UCP Settings', 'ucp-for-woocommerce'); ?>
            </a>
        </p>
    </div>
</body>
</html>
