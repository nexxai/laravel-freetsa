<?php

namespace Nexxai\Rfc3161\Exceptions;

use RuntimeException;

class InvalidCertificateChainException extends RuntimeException
{
    public static function make(string $provider): self
    {
        return new self("The [{$provider}] certificate chain is missing or invalid. The package attempted a fresh download but the chain is still not valid.");
    }
}
