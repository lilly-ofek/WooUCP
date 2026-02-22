<?php
/**
 * Quick script to generate a valid test set without complex output.
 */
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) exit;
require_once $autoload;

use Firebase\JWT\JWT;

$res = openssl_pkey_new([
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
]);
openssl_pkey_export($res, $priv);
$details = openssl_pkey_get_details($res);

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

file_put_contents(__DIR__ . '/private_key.pem', $priv);
file_put_contents(__DIR__ . '/public_keys.json', json_encode($jwk));

$payload = ["iss" => "https://mock-agent.com", "iat" => time(), "exp" => time() + 7200];
$token = JWT::encode($payload, $priv, 'RS256', 'test-key-1');
file_put_contents(__DIR__ . '/token.txt', $token);
echo "DONE";
