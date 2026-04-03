<?php

namespace Nexxai\FreeTsa;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use Nexxai\FreeTsa\Exceptions\MissingCertificatesException;
use RuntimeException;

class FreeTsa
{
    public function requestTimestamp(string $filePath, ?string $hashAlgorithm = null): array
    {
        if (! is_file($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException("The file [{$filePath}] does not exist or is not readable.");
        }

        $hashAlgorithm = $this->normalizeHashAlgorithm($hashAlgorithm ?? config('freetsa.hash_algorithm', 'sha512'));

        $tsqPath = $this->temporaryFilePath('tsq');

        try {
            $result = Process::run($this->buildQueryCommand($filePath, $hashAlgorithm, $tsqPath));

            if (! $result->successful()) {
                throw new InvalidArgumentException($result->errorOutput() ?: 'Failed to create timestamp query file.');
            }

            $tsqBinary = File::get($tsqPath);

            $tsrBinary = Http::accept('application/timestamp-reply')
                ->withBody($tsqBinary, 'application/timestamp-query')
                ->post(config('freetsa.endpoint'))
                ->throw()
                ->body();

            return [
                'tsq_binary' => $tsqBinary,
                'tsr_binary' => $tsrBinary,
                'hash_algorithm' => $hashAlgorithm,
            ];
        } finally {
            File::delete($tsqPath);
        }
    }

    public function verifyTimestamp(string $queryData, string $responseData): bool
    {
        $this->ensureCertificatesExist();

        $tsqPath = $this->temporaryFilePath('tsq');
        $tsrPath = $this->temporaryFilePath('tsr');

        File::put($tsqPath, $queryData);
        File::put($tsrPath, $responseData);

        try {
            return Process::run($this->buildVerifyCommand($tsqPath, $tsrPath))->successful();
        } finally {
            File::delete([$tsqPath, $tsrPath]);
        }
    }

    public function certificatesExist(): bool
    {
        return File::exists($this->tsaCertificatePath())
            && File::exists($this->caCertificatePath());
    }

    /**
     * @return array{tsa_certificate:string,ca_certificate:string}
     */
    public function downloadCertificates(): array
    {
        $directory = config('freetsa.certificates.directory');

        File::ensureDirectoryExists($directory);

        $tsaCertificate = Http::get(config('freetsa.certificates.tsa_url'))->throw()->body();
        $caCertificate = Http::get(config('freetsa.certificates.ca_url'))->throw()->body();

        File::put($this->tsaCertificatePath(), $tsaCertificate);
        File::put($this->caCertificatePath(), $caCertificate);

        return [
            'tsa_certificate' => $this->tsaCertificatePath(),
            'ca_certificate' => $this->caCertificatePath(),
        ];
    }

    public function tsaCertificatePath(): string
    {
        return rtrim(config('freetsa.certificates.directory'), '/').'/'.config('freetsa.certificates.tsa_file');
    }

    public function caCertificatePath(): string
    {
        return rtrim(config('freetsa.certificates.directory'), '/').'/'.config('freetsa.certificates.ca_file');
    }

    public function ensureCertificatesExist(): void
    {
        if ($this->certificatesExist()) {
            return;
        }

        throw MissingCertificatesException::make();
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
            escapeshellarg((string) config('freetsa.openssl_binary', 'openssl')),
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

    protected function buildVerifyCommand(string $queryPath, string $responsePath): string
    {
        return sprintf(
            '%s ts -verify -in %s -queryfile %s -CAfile %s -untrusted %s',
            escapeshellarg((string) config('freetsa.openssl_binary', 'openssl')),
            escapeshellarg($responsePath),
            escapeshellarg($queryPath),
            escapeshellarg($this->caCertificatePath()),
            escapeshellarg($this->tsaCertificatePath()),
        );
    }
}
