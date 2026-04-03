<?php

return [
    'endpoint' => env('FREETSA_ENDPOINT', 'https://freetsa.org/tsr'),

    'hash_algorithm' => env('FREETSA_HASH_ALGORITHM', 'sha512'),

    'openssl_binary' => env('FREETSA_OPENSSL_BINARY', 'openssl'),

    'certificates' => [
        'directory' => env('FREETSA_CERTIFICATES_DIRECTORY', storage_path('app/freetsa/certificates')),
        'tsa_url' => env('FREETSA_TSA_CERTIFICATE_URL', 'https://freetsa.org/files/tsa.crt'),
        'ca_url' => env('FREETSA_CA_CERTIFICATE_URL', 'https://freetsa.org/files/cacert.pem'),
        'tsa_file' => env('FREETSA_TSA_CERTIFICATE_FILE', 'tsa.crt'),
        'ca_file' => env('FREETSA_CA_CERTIFICATE_FILE', 'cacert.pem'),
    ],
];
