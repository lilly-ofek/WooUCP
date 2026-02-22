<?php
/**
 * Browser-runnable script to generate JWT keys and token.
 * Access this via your browser to bypass terminal hangs.
 */

// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Locate autoloader
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die("Error: vendor/autoload.php not found. Please run 'composer install'.");
}
require_once $autoload;

use Firebase\JWT\JWT;

$testsDir = __DIR__;
$keyFile = $testsDir . '/private_key.pem';
$jwkFile = $testsDir . '/public_keys.json';

echo "<h2>UCP Security Simulation - Key Generator</h2>";

// 1. Generate keys
if (!file_exists($keyFile) || isset($_GET['regen'])) {
    echo "<p>Generating RSA key pair (2048 bits)...</p>";
    $res = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);
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
    echo "<p style='color:green;'>Keys and public_keys.json created successfully!</p>";
} else {
    $privateKey = file_get_contents($keyFile);
    echo "<p>Using existing keys. (Add ?regen=1 to the URL to regenerate)</p>";
}

// 2. Generate JWT
$payload = [
    "iss" => "https://mock-agent.com",
    "iat" => time(),
    "exp" => time() + 7200, // 2 hours
    "jti" => bin2hex(random_bytes(10))
];

$token = JWT::encode($payload, $privateKey, 'RS256', 'rehearsal-key-1');

echo "<h3>Your Postman Setup:</h3>";
echo "<b>1. URL:</b> <code>POST https://staging.iscpdvet.com/wp-json/ucp/v1/checkout-sessions</code><br><br>";
echo "<b>2. Headers:</b><br>";
echo " - <code>UCP-Agent</code>: <code>profile=\"https://mock-agent.com\"</code><br>";
echo " - <code>request-signature</code>: <br><textarea style='width:100%; height:100px;'>" . $token . "</textarea><br><br>";
echo "<b>3. Body:</b> (JSON format)<br><br>";
echo "<p style='color:orange;'><b>חשוב:</b> וודא שביטלת את ה-Dev Mode בהגדרות התוסף לפני הבדיקה!</p>";
