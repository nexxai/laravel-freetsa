<?php

namespace Nexxai\FreeTsa\Commands;

use Illuminate\Console\Command;
use Nexxai\FreeTsa\FreeTsa;

class FreeTsaCommand extends Command
{
    public $signature = 'freetsa:download-certificates';

    public $description = 'Download the FreeTSA TSA and CA certificates';

    public function handle(): int
    {
        $paths = app(FreeTsa::class)->downloadCertificates();

        $this->info('Certificates downloaded successfully.');
        $this->line('TSA certificate: '.$paths['tsa_certificate']);
        $this->line('CA certificate: '.$paths['ca_certificate']);

        return self::SUCCESS;
    }
}
