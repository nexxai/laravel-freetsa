<?php

namespace Nexxai\Rfc3161;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Nexxai\Rfc3161\Exceptions\InvalidCertificateChainException;
use Nexxai\Rfc3161\Exceptions\MissingCertificatesException;
use Nexxai\Rfc3161\Providers\Contracts\TimestampProvider;
use RuntimeException;

class Timestamp
{
    /**
     * Requests a timestamp from the specified provider for the given file.
     *
     * @return array{provider:string,tsq_binary:string,tsr_binary:string,hash_algorithm:string}
     */
    public function requestTimestamp(string $filePath, ?string $hashAlgorithm = null, ?TimestampProvider $provider = null): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException("The file [{$filePath}] does not exist or is not readable.");
        }

        $provider = $this->provider($provider);

        $this->ensureCertificateChainIsValid($provider);
        $hashAlgorithm = $this->normalizeHashAlgorithm($hashAlgorithm ?? config('timestamp.hash_algorithm', 'sha512'));

        $tsqPath = $this->temporaryFilePath('tsq');

        try {
            $result = Process::run($this->buildQueryCommand($filePath, $hashAlgorithm, $tsqPath));

            if (! $result->successful()) {
                throw new InvalidArgumentException($result->errorOutput() ?: 'Failed to create timestamp query file.');
            }

            $tsqBinary = File::get($tsqPath);

            $tsrBinary = Http::accept('application/timestamp-reply')
                ->withBody($tsqBinary, 'application/timestamp-query')
                ->post($provider->endpoint())
                ->throw()
                ->body();

            return [
                'provider' => $provider->key(),
                'tsq_binary' => $tsqBinary,
                'tsr_binary' => $tsrBinary,
                'hash_algorithm' => $hashAlgorithm,
            ];
        } finally {
            File::delete($tsqPath);
        }
    }

    public function verifyTimestamp(string $queryData, string $responseData, ?TimestampProvider $provider = null): bool
    {
        $provider = $this->provider($provider);

        $this->ensureCertificateChainIsValid($provider, true);

        $tsqPath = $this->temporaryFilePath('tsq');
        $tsrPath = $this->temporaryFilePath('tsr');

        File::put($tsqPath, $queryData);
        File::put($tsrPath, $responseData);

        $trustedBundlePath = $this->temporaryBundlePath($this->trustedCertificatePaths($provider));
        $untrustedPaths = $this->untrustedCertificatePaths($provider);
        $untrustedBundlePath = $untrustedPaths === [] ? null : $this->temporaryBundlePath($untrustedPaths);

        try {
            return Process::run($this->buildVerifyCommand($tsqPath, $tsrPath, $trustedBundlePath, $untrustedBundlePath))->successful();
        } finally {
            File::delete(array_filter([$tsqPath, $tsrPath, $trustedBundlePath, $untrustedBundlePath]));
        }
    }

    public function certificatesExist(?TimestampProvider $provider = null): bool
    {
        $provider = $this->provider($provider);

        foreach ($this->certificatePaths($provider) as $path) {
            if (! File::exists($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{provider:string,certificates:array<int,string>}
     */
    public function downloadCertificates(?TimestampProvider $provider = null): array
    {
        $provider = $this->provider($provider);

        File::ensureDirectoryExists($this->certificateDirectory($provider));

        $paths = [];

        foreach ($this->certificateChain($provider) as $certificate) {
            if ($certificate['url'] === null || $certificate['url'] === '') {
                throw new InvalidArgumentException("The [{$provider->key()}] certificate [{$certificate['file']}] is missing a download URL.");
            }

            $path = $this->certificateDirectory($provider).'/'.$certificate['file'];
            $contents = Http::get($certificate['url'])->throw()->body();

            File::put($path, $contents);

            $paths[] = $path;
        }

        return [
            'provider' => $provider->key(),
            'certificates' => $paths,
        ];
    }

    public function tsaCertificatePath(?TimestampProvider $provider = null): string
    {
        $provider = $this->provider($provider);

        $paths = $this->untrustedCertificatePaths($provider);

        if ($paths !== []) {
            return $paths[0];
        }

        $allPaths = $this->certificatePaths($provider);

        if ($allPaths === []) {
            throw new InvalidArgumentException("The [{$provider->key()}] provider does not define any certificates.");
        }

        return $allPaths[0];
    }

    public function caCertificatePath(?TimestampProvider $provider = null): string
    {
        $provider ??= app(TimestampProvider::class);
        $paths = $this->trustedCertificatePaths($provider);

        if ($paths === []) {
            throw new InvalidArgumentException("The [{$provider->key()}] provider must include at least one trusted certificate.");
        }

        return $paths[0];
    }

    public function ensureCertificatesExist(?TimestampProvider $provider = null): void
    {
        $provider ??= app(TimestampProvider::class);

        if ($this->certificatesExist($provider)) {
            return;
        }

        throw MissingCertificatesException::make($provider->key());
    }

    protected function temporaryFilePath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'freetsa-');

        if ($path === false) {
            throw new RuntimeException('Unable to create temporary file.');
        }

        $newPath = $path.'.'.$extension;

        File::move($path, $newPath);

        return $newPath;
    }

    protected function buildQueryCommand(string $filePath, string $hashAlgorithm, string $outputPath): string
    {
        return sprintf(
            '%s ts -query -data %s -no_nonce -cert -%s -out %s',
            escapeshellarg((string) config('timestamp.openssl_binary', 'openssl')),
            escapeshellarg($filePath),
            $hashAlgorithm,
            escapeshellarg($outputPath),
        );
    }

    protected function normalizeHashAlgorithm(string $hashAlgorithm): string
    {
        $normalized = strtolower(ltrim($hashAlgorithm, '-'));
        $allowed = ['sha1', 'sha224', 'sha256', 'sha384', 'sha512'];

        if (! in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported hash algorithm. Allowed values: '.implode(', ', $allowed).'.');
        }

        return $normalized;
    }

    protected function buildVerifyCommand(string $queryPath, string $responsePath, string $trustedBundlePath, ?string $untrustedBundlePath): string
    {
        $command = sprintf(
            '%s ts -verify -in %s -queryfile %s -CAfile %s',
            escapeshellarg((string) config('timestamp.openssl_binary', 'openssl')),
            escapeshellarg($responsePath),
            escapeshellarg($queryPath),
            escapeshellarg($trustedBundlePath),
        );

        if ($untrustedBundlePath !== null) {
            $command .= ' -untrusted '.escapeshellarg($untrustedBundlePath);
        }

        return $command;
    }

    protected function certificateDirectory(TimestampProvider $provider): string
    {
        $directory = rtrim((string) config('timestamp.certificates.directory', storage_path('app/timestamp/certificates')), '/');

        if ($provider->key() === 'freetsa') {
            return $directory;
        }

        return $directory.'/'.$provider->key();
    }

    /**
     * @return array<int, array{file:string, url:?string, trust:bool}>
     */
    protected function certificateChain(TimestampProvider $provider): array
    {
        $chain = [];

        foreach ($provider->certificateChain() as $certificate) {
            $file = trim($certificate['file']);

            if ($file === '') {
                throw new InvalidArgumentException("The [{$provider->key()}] provider certificate chain contains an invalid file name.");
            }

            $url = $certificate['url'];

            $chain[] = [
                'file' => $file,
                'url' => $url,
                'trust' => $certificate['trust'],
            ];
        }

        if ($chain === []) {
            throw new InvalidArgumentException("The [{$provider->key()}] provider must define at least one certificate.");
        }

        foreach ($chain as $certificate) {
            if ($certificate['trust'] === true) {
                return $chain;
            }
        }

        throw new InvalidArgumentException("The [{$provider->key()}] provider must define at least one trusted certificate.");
    }

    /**
     * @return array<int, string>
     */
    protected function certificatePaths(TimestampProvider $provider): array
    {
        return array_map(
            fn (array $certificate): string => $this->certificateDirectory($provider).'/'.$certificate['file'],
            $this->certificateChain($provider),
        );
    }

    /**
     * @return array<int, string>
     */
    protected function trustedCertificatePaths(TimestampProvider $provider): array
    {
        $paths = [];

        foreach ($this->certificateChain($provider) as $certificate) {
            if (! $certificate['trust']) {
                continue;
            }

            $paths[] = $this->certificateDirectory($provider).'/'.$certificate['file'];
        }

        return $paths;
    }

    /**
     * @return array<int, string>
     */
    protected function untrustedCertificatePaths(TimestampProvider $provider): array
    {
        $paths = [];

        foreach ($this->certificateChain($provider) as $certificate) {
            if ($certificate['trust']) {
                continue;
            }

            $paths[] = $this->certificateDirectory($provider).'/'.$certificate['file'];
        }

        return $paths;
    }

    protected function ensureCertificateChainIsValid(TimestampProvider $provider, bool $requireCertificatesWhenValidationDisabled = false): void
    {
        if (! (bool) config('timestamp.validate_certificate_chain', true)) {
            if ($requireCertificatesWhenValidationDisabled) {
                $this->ensureCertificatesExist($provider);
            }

            return;
        }

        if ($this->certificateChainIsValid($provider)) {
            return;
        }

        if ($this->canDownloadCertificateChain($provider)) {
            $this->downloadCertificates($provider);

            if ($this->certificateChainIsValid($provider)) {
                return;
            }
        }

        throw InvalidCertificateChainException::make($provider->key());
    }

    protected function canDownloadCertificateChain(TimestampProvider $provider): bool
    {
        foreach ($this->certificateChain($provider) as $certificate) {
            if ($certificate['url'] === null || trim($certificate['url']) === '') {
                return false;
            }
        }

        return true;
    }

    /** @phpstan-impure */
    protected function certificateChainIsValid(TimestampProvider $provider): bool
    {
        if (! $this->certificatesExist($provider)) {
            return false;
        }

        foreach ($this->certificatePaths($provider) as $path) {
            if (! Process::run($this->buildCertificateCheckCommand($path))->successful()) {
                return false;
            }
        }

        $trustedPaths = $this->trustedCertificatePaths($provider);
        $untrustedPaths = $this->untrustedCertificatePaths($provider);
        $leafPath = $untrustedPaths[0] ?? $trustedPaths[0] ?? null;

        if ($leafPath === null) {
            return false;
        }

        $trustedBundlePath = $this->temporaryBundlePath($trustedPaths);
        $untrustedBundlePath = null;
        $intermediatePaths = array_slice($untrustedPaths, 1);

        if ($intermediatePaths !== []) {
            $untrustedBundlePath = $this->temporaryBundlePath($intermediatePaths);
        }

        try {
            return Process::run($this->buildChainVerifyCommand($leafPath, $trustedBundlePath, $untrustedBundlePath))->successful();
        } finally {
            File::delete(array_filter([$trustedBundlePath, $untrustedBundlePath]));
        }
    }

    protected function buildCertificateCheckCommand(string $certificatePath): string
    {
        return sprintf(
            '%s x509 -in %s -noout -checkend 0',
            escapeshellarg((string) config('timestamp.openssl_binary', 'openssl')),
            escapeshellarg($certificatePath),
        );
    }

    protected function buildChainVerifyCommand(string $leafPath, string $trustedBundlePath, ?string $untrustedBundlePath): string
    {
        $command = sprintf(
            '%s verify -purpose any -CAfile %s',
            escapeshellarg((string) config('timestamp.openssl_binary', 'openssl')),
            escapeshellarg($trustedBundlePath),
        );

        if ($untrustedBundlePath !== null) {
            $command .= ' -untrusted '.escapeshellarg($untrustedBundlePath);
        }

        return $command.' '.escapeshellarg($leafPath);
    }

    /**
     * @param  array<int, string>  $paths
     */
    protected function temporaryBundlePath(array $paths): string
    {
        $bundlePath = $this->temporaryFilePath('pem');
        $bundle = '';

        foreach ($paths as $path) {
            $bundle .= File::get($path).PHP_EOL;
        }

        File::put($bundlePath, $bundle);

        return $bundlePath;
    }

    protected function provider(?TimestampProvider $provider = null): TimestampProvider
    {
        if ($provider instanceof TimestampProvider) {
            return $provider;
        }

        return app(TimestampProvider::class);
    }
}
