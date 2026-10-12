<?php

declare(strict_types=1);

namespace WilyCode\Container\Tests\Fixtures;

final class RequiredScalar
{
    public function __construct(public string $token)
    {
    }
}
