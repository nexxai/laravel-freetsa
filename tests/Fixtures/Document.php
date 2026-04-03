<?php

namespace Nexxai\FreeTsa\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Nexxai\FreeTsa\Concerns\HasFreeTsaTimestamps;

class Document extends Model
{
    use HasFreeTsaTimestamps;

    protected $guarded = [];

    public $timestamps = false;
}
