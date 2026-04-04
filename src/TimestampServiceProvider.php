<?php

namespace Nexxai\Rfc3161;

use Nexxai\Rfc3161\Commands\TimestampCommand;
use Nexxai\Rfc3161\Providers\Contracts\TimestampProvider;
use Nexxai\Rfc3161\Providers\DigiCert;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TimestampServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-rfc3161')
            ->hasConfigFile('rfc3161')
            ->hasMigration('create_timestamps_table')
            ->hasCommand(TimestampCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->app->singleton(Timestamp::class, fn (): Timestamp => new Timestamp);

        $this->app->bind(TimestampProvider::class, function (): TimestampProvider {
            $provider = config('timestamp.default_provider', DigiCert::class);

            if (! is_string($provider) || ! is_a($provider, TimestampProvider::class, true)) {
                $provider = DigiCert::class;
            }

            return app($provider);
        });

        $this->mergeConfigFrom(
            __DIR__.'/../config/rfc3161.php',
            'rfc3161'
        );
    }

    public function packageBooted(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishTagAlias('rfc3161-migrations', 'rfc3161-migrations');
        $this->publishTagAlias('rfc3161-config', 'rfc3161-config');

    }

    protected function publishTagAlias(string $sourceTag, string $targetTag): void
    {
        $paths = static::pathsToPublish(static::class, $sourceTag);

        if ($paths === []) {
            return;
        }

        $this->publishes($paths, $targetTag);
    }
}
