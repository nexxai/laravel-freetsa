<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Nexxai\FreeTsa\Exceptions\MissingCertificatesException;
use Nexxai\FreeTsa\FreeTsaServiceProvider;
use Nexxai\FreeTsa\Models\FreeTsaTimestamp;
use Nexxai\FreeTsa\Tests\Fixtures\Document;

beforeEach(function (): void {
    Schema::create('documents', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
});

it('creates and stores timestamp query and response binaries', function (): void {
    Http::fake([
        config('freetsa.endpoint') => Http::response('binary-tsr', 200),
    ]);

    $filePath = tempnam(sys_get_temp_dir(), 'freetsa-test-');
    File::put($filePath, 'test content');

    $document = Document::create(['name' => 'Invoice']);

    $timestamp = FreeTsaTimestamp::timestampFile($filePath, $document);

    expect($timestamp->file_name)->toBe(basename($filePath))
        ->and($timestamp->tsq_binary)->not->toBe('')
        ->and($timestamp->tsr_binary)->toBe('binary-tsr')
        ->and($timestamp->timestampable_type)->toBe(Document::class)
        ->and($timestamp->timestampable_id)->toBe($document->id);

    File::delete($filePath);
});

it('verifies timestamp data with locally stored certificates', function (): void {
    Process::fake([
        '*' => Process::result(output: 'Verification: OK', exitCode: 0),
    ]);

    File::put(config('freetsa.certificates.directory').'/'.config('freetsa.certificates.tsa_file'), 'tsa-cert');
    File::put(config('freetsa.certificates.directory').'/'.config('freetsa.certificates.ca_file'), 'ca-cert');

    $timestamp = FreeTsaTimestamp::query()->create([
        'file_name' => 'example.txt',
        'hash_algorithm' => 'sha512',
        'tsq_binary' => 'query-bytes',
        'tsr_binary' => 'response-bytes',
    ]);

    expect($timestamp->verify())->toBeTrue();
});

it('requires certificates before verification', function (): void {
    File::deleteDirectory(config('freetsa.certificates.directory'));

    $timestamp = FreeTsaTimestamp::query()->create([
        'file_name' => 'example.txt',
        'hash_algorithm' => 'sha512',
        'tsq_binary' => 'query-bytes',
        'tsr_binary' => 'response-bytes',
    ]);

    expect(fn () => $timestamp->verify())
        ->toThrow(MissingCertificatesException::class);
});

it('downloads certificates with the artisan command', function (): void {
    Http::fake([
        config('freetsa.certificates.tsa_url') => Http::response('tsa-certificate-data', 200),
        config('freetsa.certificates.ca_url') => Http::response('ca-certificate-data', 200),
    ]);

    $this->artisan('freetsa:download-certificates')
        ->assertSuccessful();

    expect(File::get(config('freetsa.certificates.directory').'/'.config('freetsa.certificates.tsa_file')))
        ->toBe('tsa-certificate-data')
        ->and(File::get(config('freetsa.certificates.directory').'/'.config('freetsa.certificates.ca_file')))
        ->toBe('ca-certificate-data');
});

it('publishes config and migrations using documented tags', function (): void {
    $migrationPaths = FreeTsaServiceProvider::pathsToPublish(FreeTsaServiceProvider::class, 'laravel-freetsa-migrations');
    $configPaths = FreeTsaServiceProvider::pathsToPublish(FreeTsaServiceProvider::class, 'laravel-freetsa-config');

    expect($migrationPaths)->not->toBeEmpty()
        ->and($configPaths)->not->toBeEmpty();
});
