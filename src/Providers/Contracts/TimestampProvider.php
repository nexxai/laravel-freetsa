<?php

namespace Nexxai\Rfc3161\Providers\Contracts;

interface TimestampProvider
{
    public function key(): string;

    public function endpoint(): string;

    /**
     * @return array<int, array{file:string, url:?string, trust:bool}>
     */
    public function certificateChain(): array;
}
