<?php

namespace Nexxai\FreeTsa;

use Nexxai\FreeTsa\Commands\FreeTsaCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FreeTsaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-freetsa')
            ->hasConfigFile()
            ->hasMigration('create_free_tsa_timestamps_table')
            ->hasCommand(FreeTsaCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(FreeTsa::class, fn (): FreeTsa => new FreeTsa);
    }
}
