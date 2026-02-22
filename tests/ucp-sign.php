<?php
/**
 * JWT/JWS Signing Script for UCP Testing
 * This script generates keys and signs a payload.
 */

// Load Composer autoloader
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die("Error: vendor/autoload.php not found. Please run 'composer install' in the plugin directory.\n");
}
require_once $autoload;

use Firebase\JWT\JWT;

$testsDir = __DIR__;
$keyFile = $testsDir . '/private_key.pem';
$jwkFile = $testsDir . '/public_keys.json';

// 1. Generate keys if they don't exist
if (!file_exists($keyFile)) {
    echo "Generating RSA key pair...\n";
    $res = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($res, $privateKey);
    file_put_contents($keyFile, $privateKey);

    $details = openssl_pkey_get_details($res);
    
    // Create JWK set
    $jwk = [
        "keys" => [
            [
                "kty" => "RSA",
                "n" => str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($details['rsa']['n'])),
                "e" => str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($details['rsa']['e'])),
                "kid" => "test-key-1",
                "use" => "sig",
                "alg" => "RS256"
            ]
        ]
    ];
    file_put_contents($jwkFile, json_encode($jwk, JSON_PRETTY_PRINT));
    echo "Keys and public_keys.json created.\n";
} else {
    $privateKey = file_get_contents($keyFile);
}

// 2. Define Payload
$payload = [
    "iss" => "https://mock-agent.com",
    "iat" => time(),
    "exp" => time() + 3600,
    "jti" => bin2hex(random_bytes(10))
];

// 3. Sign and Output
$token = JWT::encode($payload, $privateKey, 'RS256', 'test-key-1');

echo "\n--- JWT SIGNATURE ---\n";
echo $token . "\n";
echo "---------------------\n";
echo "1. Set header 'UCP-Agent' to: profile=\"https://mock-agent.com\"\n";
echo "2. Set header 'request-signature' to the JWT above.\n";
echo "3. Ensure Dev Mode is OFF to test real verification.\n";
