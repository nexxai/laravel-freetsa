<?php

namespace Nexxai\FreeTsa\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Nexxai\FreeTsa\Models\FreeTsaTimestamp;

class Document extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function timestamps(): MorphMany
    {
        return $this->morphMany(FreeTsaTimestamp::class, 'timestampable');
    }
}
