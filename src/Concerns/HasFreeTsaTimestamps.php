<?php

namespace Nexxai\FreeTsa\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nexxai\FreeTsa\Models\FreeTsaTimestamp;

trait HasFreeTsaTimestamps
{
    public function freeTsaTimestamps(): MorphMany
    {
        return $this->morphMany(FreeTsaTimestamp::class, 'timestampable');
    }
}
