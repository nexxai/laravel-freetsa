# Laravel FreeTSA

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nexxai/laravel-freetsa.svg?style=flat-square)](https://packagist.org/packages/nexxai/laravel-freetsa)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nexxai/laravel-freetsa/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nexxai/laravel-freetsa/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nexxai/laravel-freetsa/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nexxai/laravel-freetsa/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nexxai/laravel-freetsa.svg?style=flat-square)](https://packagist.org/packages/nexxai/laravel-freetsa)

`nexxai/laravel-freetsa` is a thin Laravel interface for [freeTSA.org](https://freetsa.org/index_en.php). It creates RFC 3161 timestamp requests, sends them to FreeTSA, stores the request/response binary payloads, and verifies responses with FreeTSA certificates.

## Installation

### Requirements

- PHP must be able to execute an `openssl` CLI binary for RFC 3161 verification.
- By default this package calls `openssl` from your `PATH`; set `FREETSA_OPENSSL_BINARY` if your binary lives elsewhere.

You can install the package via composer:

```bash
composer require nexxai/laravel-freetsa
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-freetsa-migrations"
php artisan migrate
```

Add the package trait to your user model so it gets the `freeTsaTimestamps()` polymorphic relationship:

```php
use Nexxai\FreeTsa\Concerns\HasFreeTsaTimestamps;

class User extends Authenticatable
{
    use HasFreeTsaTimestamps;
}
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
use Nexxai\FreeTsa\Concerns\HasFreeTsaTimestamps;

class Invoice extends Model
{
    use HasFreeTsaTimestamps;
}
```

After adding the trait, access records with `$user->freeTsaTimestamps` (or call `$user->freeTsaTimestamps()` for the relation query).

## Testing

```bash
composer test
```

## Releases

This package uses Release Please to create and publish semver releases from `main`.

Use Conventional Commits in merged PRs so the version bump is predictable:

- `fix: ...` -> patch release (`x.y.Z`)
- `feat: ...` -> minor release (`x.Y.0`)
- `feat!: ...` or a `BREAKING CHANGE:` footer -> major release (`X.0.0`)

Example:

```text
feat: add certificate download command
fix: handle missing cert files before verify
feat!: rename timestampFile API to createFromFile
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
