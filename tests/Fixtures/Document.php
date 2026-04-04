<?php

namespace Nexxai\Rfc3161\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Nexxai\Rfc3161\Concerns\HasRfc3161Timestamps;

class Document extends Model
{
    use HasRfc3161Timestamps;

    protected $guarded = [];

    public $timestamps = false;
}
