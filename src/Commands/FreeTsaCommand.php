<?php

namespace Nexxai\FreeTsa\Commands;

use Illuminate\Console\Command;

class FreeTsaCommand extends Command
{
    public $signature = 'laravel-freetsa';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
