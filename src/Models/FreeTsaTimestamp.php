<?php

namespace Nexxai\FreeTsa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nexxai\FreeTsa\FreeTsa;

/**
 * @property string $tsq_binary
 * @property string $tsr_binary
 */
class FreeTsaTimestamp extends Model
{
    protected $table = 'free_tsa_timestamps';

    protected $guarded = [];

    public function timestampable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function timestampFile(string $filePath, ?Model $timestampable = null): self
    {
        $freeTsa = app(FreeTsa::class);
        $timestamp = $freeTsa->requestTimestamp($filePath);

        $record = new self([
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

    public function verify(?string $queryData = null, ?string $responseData = null): bool
    {
        $queryData ??= $this->tsq_binary;
        $responseData ??= $this->tsr_binary;

        return app(FreeTsa::class)->verifyTimestamp($queryData, $responseData);
    }
}
