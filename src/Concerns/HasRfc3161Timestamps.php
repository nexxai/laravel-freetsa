<?php

namespace Nexxai\Rfc3161\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nexxai\Rfc3161\Models\Timestamp;

trait HasRfc3161Timestamps
{
    public function timestampRecords(): MorphMany
    {
        return $this->morphMany(Timestamp::class, 'timestampable');
    }
}
