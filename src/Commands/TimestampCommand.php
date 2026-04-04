<?php

namespace Nexxai\Rfc3161\Commands;

use Illuminate\Console\Command;
use Nexxai\Rfc3161\Timestamp;

class TimestampCommand extends Command
{
    public $signature = 'timestamp:download-certificates';

    public $description = 'Download timestamp provider certificate chain';

    public function handle(): int
    {
        $timestamp = app(Timestamp::class);
        $paths = $timestamp->downloadCertificates();

        $this->info('Certificates downloaded successfully.');
        $this->line('Provider: '.$paths['provider']);

        foreach ($paths['certificates'] as $path) {
            $this->line('Certificate: '.$path);
        }

        return self::SUCCESS;
    }
}
