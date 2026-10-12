<?php

declare(strict_types=1);

namespace WilyCode\Container\Tests\Fixtures;

final class Foo
{
    public Bar $bar;

    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
}
