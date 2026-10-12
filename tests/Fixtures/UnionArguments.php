<?php

declare(strict_types=1);

namespace WilyCode\Container\Tests\Fixtures;

final class UnionArguments
{
    public function __construct(public Bar|Foo $dependency)
    {
    }
}
