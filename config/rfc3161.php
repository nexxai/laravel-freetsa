<?php

use Illuminate\Support\Env;
use Nexxai\Rfc3161\Providers\DigiCert;

return [
    'default_provider' => Env::get('TIMESTAMP_PROVIDER', DigiCert::class),

    'hash_algorithm' => Env::get('TIMESTAMP_HASH_ALGORITHM', 'sha512'),

    'openssl_binary' => Env::get('TIMESTAMP_OPENSSL_BINARY', 'openssl'),

    'validate_certificate_chain' => Env::get('TIMESTAMP_VALIDATE_CERTIFICATE_CHAIN', true),

    'certificates' => [
        'directory' => Env::get('TIMESTAMP_CERTIFICATES_DIRECTORY', storage_path('app/timestamp/certificates')),
    ],
];
