<?php

namespace Nexxai\FreeTsa;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Nexxai\FreeTsa\Commands\FreeTsaCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel_freetsa_table')
            ->hasCommand(FreeTsaCommand::class);
    }
}
