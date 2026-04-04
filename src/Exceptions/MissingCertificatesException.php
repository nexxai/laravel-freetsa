<?php

namespace Nexxai\Rfc3161\Exceptions;

use RuntimeException;

class MissingCertificatesException extends RuntimeException
{
    public static function make(string $provider = 'freetsa'): self
    {
        return new self("[{$provider}] certificates are missing. Run [php artisan timestamp:download-certificates] before verifying timestamps.");
    }
}
