<?php

use Illuminate\Support\Env;

return [
    'endpoint' => Env::get('FREETSA_ENDPOINT', 'https://freetsa.org/tsr'),

    'hash_algorithm' => Env::get('FREETSA_HASH_ALGORITHM', 'sha512'),

    'openssl_binary' => Env::get('FREETSA_OPENSSL_BINARY', 'openssl'),

    'certificates' => [
        'directory' => Env::get('FREETSA_CERTIFICATES_DIRECTORY', storage_path('app/freetsa/certificates')),
        'tsa_url' => Env::get('FREETSA_TSA_CERTIFICATE_URL', 'https://freetsa.org/files/tsa.crt'),
        'ca_url' => Env::get('FREETSA_CA_CERTIFICATE_URL', 'https://freetsa.org/files/cacert.pem'),
        'tsa_file' => Env::get('FREETSA_TSA_CERTIFICATE_FILE', 'tsa.crt'),
        'ca_file' => Env::get('FREETSA_CA_CERTIFICATE_FILE', 'cacert.pem'),
    ],
];
