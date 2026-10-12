<?php

declare(strict_types=1);

namespace WilyCode\Container\Tests\Fixtures;

final class OptionalArguments
{
    public function __construct(public string $mode = 'default')
    {
    }
}
