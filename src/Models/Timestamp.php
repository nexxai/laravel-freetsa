<?php

namespace Nexxai\Rfc3161\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;
use Nexxai\Rfc3161\Providers\Contracts\TimestampProvider;
use Nexxai\Rfc3161\Timestamp as TimestampManager;

/**
 * @property string $provider
 * @property string $tsq_binary
 * @property string $tsr_binary
 */
class Timestamp extends Model
{
    protected $table = 'timestamps';

    protected $guarded = [];

    public function timestampable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function timestampFile(string $filePath, ?Model $timestampable = null, ?TimestampProvider $provider = null): self
    {
        $timestampManager = app(TimestampManager::class);
        $timestamp = $timestampManager->requestTimestamp($filePath, provider: $provider);

        $record = new self([
            'provider' => $timestamp['provider'],
            'file_name' => basename($filePath),
            'hash_algorithm' => $timestamp['hash_algorithm'],
            'tsq_binary' => $timestamp['tsq_binary'],
            'tsr_binary' => $timestamp['tsr_binary'],
        ]);

        if ($timestampable !== null) {
            $record->timestampable()->associate($timestampable);
        }

        $record->save();

        return $record;
    }

    public function verify(?string $queryData = null, ?string $responseData = null, ?TimestampProvider $provider = null): bool
    {
        $timestampManager = app(TimestampManager::class);

        $queryData ??= $this->tsq_binary;
        $responseData ??= $this->tsr_binary;
        $provider ??= $this->providerFromRecord();

        return $timestampManager->verifyTimestamp($queryData, $responseData, $provider);
    }

    protected function providerFromRecord(): ?TimestampProvider
    {
        if (trim($this->provider) === '') {
            return null;
        }

        if (! is_a($this->provider, TimestampProvider::class, true)) {
            throw new InvalidArgumentException('Timestamp provider must be stored as a provider class name.');
        }

        return app($this->provider);
    }
}
