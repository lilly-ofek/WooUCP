<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';
use Firebase\JWT\JWT;

$res = openssl_pkey_new([
    "private_key_bits" => 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
]);
openssl_pkey_export($res, $privateKey);
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

file_put_contents(__DIR__ . '/private_key.pem', $privateKey);
file_put_contents(__DIR__ . '/public_keys.json', json_encode($jwk, JSON_PRETTY_PRINT));

$payload = [
    "iss" => "https://mock-agent.com",
    "iat" => time(),
    "exp" => time() + 3600,
    "jti" => bin2hex(random_bytes(10))
];

$token = JWT::encode($payload, $privateKey, 'RS256', 'test-key-1');
file_put_contents(__DIR__ . '/token.txt', $token);

echo "SUCCESS";
