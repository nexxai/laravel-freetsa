<?php

namespace Nexxai\Rfc3161\Providers;

use Nexxai\Rfc3161\Providers\Contracts\TimestampProvider;

class FreeTsa implements TimestampProvider
{
    public function key(): string
    {
        return 'freetsa';
    }

    public function endpoint(): string
    {
        return 'https://freetsa.org/tsr';
    }

    public function certificateChain(): array
    {
        return [
            [
                'file' => 'tsa.crt',
                'url' => 'https://freetsa.org/files/tsa.crt',
                'trust' => false,
            ],
            [
                'file' => 'cacert.pem',
                'url' => 'https://freetsa.org/files/cacert.pem',
                'trust' => true,
            ],
        ];
    }
}
