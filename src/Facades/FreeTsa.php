<?php

namespace Nexxai\FreeTsa\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nexxai\FreeTsa\FreeTsa
 */
class FreeTsa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nexxai\FreeTsa\FreeTsa::class;
    }
}
