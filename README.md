# Laravel FreeTSA

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nexxai/laravel-freetsa.svg?style=flat-square)](https://packagist.org/packages/nexxai/laravel-freetsa)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nexxai/laravel-freetsa/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nexxai/laravel-freetsa/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nexxai/laravel-freetsa/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nexxai/laravel-freetsa/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nexxai/laravel-freetsa.svg?style=flat-square)](https://packagist.org/packages/nexxai/laravel-freetsa)

`nexxai/laravel-freetsa` is a thin Laravel interface for [freeTSA.org](https://freetsa.org/index_en.php). It creates RFC 3161 timestamp requests, sends them to FreeTSA, stores the request/response binary payloads, and verifies responses with FreeTSA certificates.

## Installation

You can install the package via composer:

```bash
composer require nexxai/laravel-freetsa
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-freetsa-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-freetsa-config"
```

This is the contents of the published config file:

```php
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
```

Before any timestamp verification, download and store FreeTSA certificates:

```bash
php artisan freetsa:download-certificates
```

If certificates are missing, verification will throw an exception with this command.

## Usage

```php
use App\Models\Invoice;
use Nexxai\FreeTsa\Models\FreeTsaTimestamp;

$invoice = Invoice::findOrFail(1);

// Creates a TSQ from file content, sends it to FreeTSA, and stores TSQ/TSR binary payloads.
$timestamp = FreeTsaTimestamp::timestampFile(
    storage_path('app/invoices/invoice-2026-04.pdf'),
    $invoice,
);

// Verify stored query and response with locally downloaded FreeTSA certificates.
$isValid = $timestamp->verify();

// You can also verify explicit query/response data.
$isValid = $timestamp->verify($customTsqBinary, $customTsrBinary);
```

The `free_tsa_timestamps` table includes a nullable polymorphic relation (`timestampable_type`, `timestampable_id`) so any Eloquent model can own many timestamp records.

### Polymorphic relationship example

```php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nexxai\FreeTsa\Models\FreeTsaTimestamp;

class Invoice extends Model
{
    public function timestamps(): MorphMany
    {
        return $this->morphMany(FreeTsaTimestamp::class, 'timestampable');
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [JT Smith](https://github.com/nexxai)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
