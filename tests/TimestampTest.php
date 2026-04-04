<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Nexxai\Rfc3161\Exceptions\InvalidCertificateChainException;
use Nexxai\Rfc3161\Exceptions\MissingCertificatesException;
use Nexxai\Rfc3161\Models\Timestamp;
use Nexxai\Rfc3161\Providers\DigiCert;
use Nexxai\Rfc3161\Providers\FreeTsa;
use Nexxai\Rfc3161\Tests\Fixtures\Document;
use Nexxai\Rfc3161\TimestampServiceProvider;

beforeEach(function (): void {
    Schema::create('documents', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
});

it('creates and stores timestamp query and response binaries', function (): void {
    $provider = new FreeTsa;

    Http::fake([
        $provider->endpoint() => Http::response('binary-tsr', 200),
    ]);

    $filePath = tempnam(sys_get_temp_dir(), 'freetsa-test-');
    File::put($filePath, 'test content');

    $document = Document::create(['name' => 'Invoice']);

    $timestamp = Timestamp::timestampFile($filePath, $document, $provider);

    expect($timestamp->file_name)->toBe(basename($filePath))
        ->and($timestamp->provider)->toBe('freetsa')
        ->and($timestamp->tsq_binary)->not->toBe('')
        ->and($timestamp->tsr_binary)->toBe('binary-tsr')
        ->and($timestamp->timestampable_type)->toBe(Document::class)
        ->and($timestamp->timestampable_id)->toBe($document->id);

    File::delete($filePath);
});

it('provides the timestampRecords relationship from the trait', function (): void {
    $document = Document::create(['name' => 'Invoice']);

    $timestamp = Timestamp::query()->create([
        'provider' => FreeTsa::class,
        'file_name' => 'example.txt',
        'hash_algorithm' => 'sha512',
        'tsq_binary' => 'query-bytes',
        'tsr_binary' => 'response-bytes',
    ]);

    $timestamp->timestampable()->associate($document);
    $timestamp->save();

    expect($document->timestampRecords)->toHaveCount(1)
        ->and($document->timestampRecords->first()?->id)->toBe($timestamp->id);
});

it('verifies timestamp data with locally stored certificates', function (): void {
    $provider = new FreeTsa;
    $chain = $provider->certificateChain();

    Process::fake([
        '*' => Process::result(output: 'Verification: OK', exitCode: 0),
    ]);

    foreach ($chain as $certificate) {
        File::put(config('timestamp.certificates.directory').'/'.$certificate['file'], 'certificate-data');
    }

    $timestamp = Timestamp::query()->create([
        'provider' => FreeTsa::class,
        'file_name' => 'example.txt',
        'hash_algorithm' => 'sha512',
        'tsq_binary' => 'query-bytes',
        'tsr_binary' => 'response-bytes',
    ]);

    expect($timestamp->verify())->toBeTrue();
});

it('requires certificates before verification', function (): void {
    File::deleteDirectory(config('timestamp.certificates.directory'));

    $timestamp = Timestamp::query()->create([
        'file_name' => 'example.txt',
        'hash_algorithm' => 'sha512',
        'tsq_binary' => 'query-bytes',
        'tsr_binary' => 'response-bytes',
    ]);

    expect(fn () => $timestamp->verify())
        ->toThrow(MissingCertificatesException::class);
});

it('downloads certificates with the artisan command', function (): void {
    $provider = new FreeTsa;
    $chain = $provider->certificateChain();

    $responses = [];

    foreach ($chain as $index => $certificate) {
        $responses[(string) $certificate['url']] = Http::response('certificate-'.$index, 200);
    }

    Http::fake([
        ...$responses,
    ]);

    config()->set('timestamp.default_provider', FreeTsa::class);

    $this->artisan('timestamp:download-certificates')
        ->assertSuccessful();

    foreach ($chain as $index => $certificate) {
        expect(File::get(config('timestamp.certificates.directory').'/'.$certificate['file']))
            ->toBe('certificate-'.$index);
    }
});

it('can timestamp a file with the digicert provider', function (): void {
    $provider = new DigiCert;

    Http::fake([
        $provider->endpoint() => Http::response('digicert-binary-tsr', 200),
    ]);

    $filePath = tempnam(sys_get_temp_dir(), 'freetsa-test-');
    File::put($filePath, 'test content');

    $document = Document::create(['name' => 'Invoice']);

    $timestamp = Timestamp::timestampFile($filePath, $document, $provider);

    expect($timestamp->provider)->toBe('digicert')
        ->and($timestamp->tsr_binary)->toBe('digicert-binary-tsr');

    File::delete($filePath);
});

it('uses configured default provider when no provider is passed', function (): void {
    $provider = new DigiCert;

    config()->set('timestamp.default_provider', DigiCert::class);

    Http::fake([
        $provider->endpoint() => Http::response('default-provider-tsr', 200),
    ]);

    $filePath = tempnam(sys_get_temp_dir(), 'freetsa-test-');
    File::put($filePath, 'test content');

    $document = Document::create(['name' => 'Invoice']);

    $timestamp = Timestamp::timestampFile($filePath, $document);

    expect($timestamp->provider)->toBe('digicert')
        ->and($timestamp->tsr_binary)->toBe('default-provider-tsr');

    File::delete($filePath);
});

it('throws when certificate chain validation cannot recover', function (): void {
    config()->set('timestamp.validate_certificate_chain', true);

    $provider = new DigiCert;

    File::deleteDirectory(config('timestamp.certificates.directory').'/'.$provider->key());

    $invalidCertificate = File::get(__DIR__.'/Fixtures/certificates/invalid-certificate.pem');

    Http::fake([
        '*' => Http::response($invalidCertificate, 200),
    ]);

    $timestamp = Timestamp::query()->create([
        'provider' => DigiCert::class,
        'file_name' => 'example.txt',
        'hash_algorithm' => 'sha512',
        'tsq_binary' => 'query-bytes',
        'tsr_binary' => 'response-bytes',
    ]);

    expect(fn () => $timestamp->verify())
        ->toThrow(InvalidCertificateChainException::class);
});

it('prefers embedded certificates from the timestamp response when verifying', function (): void {
    config()->set('timestamp.validate_certificate_chain', false);

    File::put(config('timestamp.certificates.directory').'/cacert.pem', 'trusted-certificate');

    Process::fake([
        '*ts -reply*pkcs7 -inform DER -print_certs*' => Process::result(
            output: "subject=/CN=Timestamp Issuer\n-----BEGIN CERTIFICATE-----\nMIIB\n-----END CERTIFICATE-----\n",
            exitCode: 0,
        ),
        '*ts -verify*' => Process::result(output: 'Verification: OK', exitCode: 0),
        '*' => Process::result(exitCode: 0),
    ]);

    $timestamp = Timestamp::query()->create([
        'provider' => FreeTsa::class,
        'file_name' => 'example.txt',
        'hash_algorithm' => 'sha512',
        'tsq_binary' => 'query-bytes',
        'tsr_binary' => 'response-bytes',
    ]);

    expect($timestamp->verify())->toBeTrue();

    Process::assertRan(fn ($process): bool => str_contains($process->command, 'ts -reply')
        && str_contains($process->command, 'pkcs7 -inform DER -print_certs'));

    Process::assertRan(fn ($process): bool => str_contains($process->command, 'ts -verify')
        && str_contains($process->command, ' -untrusted '));
});

it('publishes config and migrations using documented tags', function (): void {
    $migrationPaths = TimestampServiceProvider::pathsToPublish(TimestampServiceProvider::class, 'rfc3161-migrations');
    $configPaths = TimestampServiceProvider::pathsToPublish(TimestampServiceProvider::class, 'rfc3161-config');

    expect($migrationPaths)->not->toBeEmpty()
        ->and($configPaths)->not->toBeEmpty();
});
