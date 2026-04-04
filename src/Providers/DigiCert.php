<?php

namespace Nexxai\Rfc3161\Providers;

use Nexxai\Rfc3161\Providers\Contracts\TimestampProvider;

class DigiCert implements TimestampProvider
{
    public function key(): string
    {
        return 'digicert';
    }

    public function endpoint(): string
    {
        return 'http://timestamp.digicert.com';
    }

    public function certificateChain(): array
    {
        return [
            [
                'file' => 'DigiCertSHA512RSA4096TimestampResponder20251.cer',
                'url' => 'https://knowledge.digicert.com/content/dam/kb/attachments/time-stamp/DigiCertSHA512RSA4096TimestampResponder20251.cer',
                'trust' => false,
            ],
            [
                'file' => 'DigiCertSHA256RSA4096TimestampResponder20251.cer',
                'url' => 'https://knowledge.digicert.com/content/dam/kb/attachments/time-stamp/DigiCertSHA256RSA4096TimestampResponder20251.cer',
                'trust' => false,
            ],
            [
                'file' => 'DigiCertSHA384RSA4096TimestampResponder20251.cer',
                'url' => 'https://knowledge.digicert.com/content/dam/kb/attachments/time-stamp/DigiCertSHA384RSA4096TimestampResponder20251.cer',
                'trust' => false,
            ],
            [
                'file' => 'DigiCertTrustedG4TimeStampingRSA4096SHA2562025CA1.pem',
                'url' => 'https://knowledge.digicert.com/content/dam/kb/attachments/time-stamp/DigiCertTrustedG4TimeStampingRSA4096SHA2562025CA1.pem',
                'trust' => false,
            ],
            [
                'file' => 'DigiCertTrustedRootG4.cer',
                'url' => 'https://knowledge.digicert.com/content/dam/kb/attachments/time-stamp/DigiCertTrustedRootG4.cer',
                'trust' => true,
            ],
        ];
    }
}
