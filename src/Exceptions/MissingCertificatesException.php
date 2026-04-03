<?php

namespace Nexxai\FreeTsa\Exceptions;

use RuntimeException;

class MissingCertificatesException extends RuntimeException
{
    public static function make(): self
    {
        return new self('FreeTSA certificates are missing. Run [php artisan freetsa:download-certificates] before verifying timestamps.');
    }
}
