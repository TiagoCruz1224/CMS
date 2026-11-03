<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// JWT key pair for the test environment (paths are set in .env.test)
$jwtDir = dirname(__DIR__).'/var/jwt-test';
if (!is_file($jwtDir.'/private.pem')) {
    @mkdir($jwtDir, 0777, true);
    // Windows PHP builds often have no default openssl.cnf; an empty one is enough here
    touch($jwtDir.'/openssl.cnf');
    $options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => $jwtDir.'/openssl.cnf'];
    $key = openssl_pkey_new($options);
    openssl_pkey_export($key, $privateKey, null, $options);
    file_put_contents($jwtDir.'/private.pem', $privateKey);
    file_put_contents($jwtDir.'/public.pem', openssl_pkey_get_details($key)['key']);
}
