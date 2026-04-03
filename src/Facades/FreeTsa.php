<?php

namespace Nexxai\FreeTsa\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexxai\FreeTsa\FreeTsa
 *
 * @method static array requestTimestamp(string $filePath, ?string $hashAlgorithm = null)
 * @method static bool verifyTimestamp(string $queryData, string $responseData)
 * @method static bool certificatesExist()
 * @method static array downloadCertificates()
 * @method static string tsaCertificatePath()
 * @method static string caCertificatePath()
 * @method static void ensureCertificatesExist()
 */
class FreeTsa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nexxai\FreeTsa\FreeTsa::class;
    }
}
