<?php

namespace Nexxai\Rfc3161\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexxai\Rfc3161\Timestamp
 *
 * @method static array requestTimestamp(string $filePath, ?string $hashAlgorithm = null, ?\Nexxai\Rfc3161\Providers\Contracts\TimestampProvider $provider = null)
 * @method static bool verifyTimestamp(string $queryData, string $responseData, ?\Nexxai\Rfc3161\Providers\Contracts\TimestampProvider $provider = null)
 * @method static bool certificatesExist(?\Nexxai\Rfc3161\Providers\Contracts\TimestampProvider $provider = null)
 * @method static array downloadCertificates(?\Nexxai\Rfc3161\Providers\Contracts\TimestampProvider $provider = null)
 * @method static string tsaCertificatePath(?\Nexxai\Rfc3161\Providers\Contracts\TimestampProvider $provider = null)
 * @method static string caCertificatePath(?\Nexxai\Rfc3161\Providers\Contracts\TimestampProvider $provider = null)
 * @method static void ensureCertificatesExist(?\Nexxai\Rfc3161\Providers\Contracts\TimestampProvider $provider = null)
 */
class Timestamp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nexxai\Rfc3161\Timestamp::class;
    }
}
