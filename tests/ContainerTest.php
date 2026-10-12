<?php

declare(strict_types=1);

namespace WilyCode\Container\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use WilyCode\Container\Exception\ContainerException;
use WilyCode\Container\Container;
use WilyCode\Container\Exception\NotFoundException;
use WilyCode\Container\Tests\Fixtures\Bar;
use WilyCode\Container\Tests\Fixtures\Foo;
use WilyCode\Container\Tests\Fixtures\OptionalArguments;
use WilyCode\Container\Tests\Fixtures\RequiredScalar;
use WilyCode\Container\Tests\Fixtures\UnionArguments;

final class ContainerTest extends TestCase
{
    public function testReturnsContainerItself(): void
    {
        $container = new Container();

        self::assertSame($container, $container->get(ContainerInterface::class));
        self::assertSame($container, $container->get(Container::class));
    }

    public function testStoresValuesAndCallableObjectsWithoutGuessing(): void
    {
        $callable = new class {
            public function __invoke(): string { return 'called'; }
        };
        $container = new Container(['answer' => 42, 'callable' => $callable]);

        self::assertSame(42, $container->get('answer'));
        self::assertSame($callable, $container->get('callable'));
    }

    public function testClosureIsAnExplicitSingletonFactory(): void
    {
        $calls = 0;
        $container = new Container([
            'service' => static function (ContainerInterface $container) use (&$calls): ContainerInterface {
                ++$calls;
                return $container;
            },
        ]);

        self::assertSame($container, $container->get('service'));
        self::assertSame($container, $container->get('service'));
        self::assertSame(1, $calls);
    }

    public function testSetReplacesResolvedEntryAndRemoveDeletesIt(): void
    {
        $container = new Container(['value' => 1]);
        self::assertSame(1, $container->get('value'));

        $container->set('value', 2);
        self::assertSame(2, $container->get('value'));

        $container->remove('value');
        self::assertFalse($container->has('value'));
    }

    public function testAutowiresAndCachesClasses(): void
    {
        $container = new Container();
        $foo = $container->get(Foo::class);

        self::assertInstanceOf(Bar::class, $foo->bar);
        self::assertSame($foo, $container->get(Foo::class));
    }

    public function testRejectsCircularDependencies(): void
    {
        $container = new Container([
            'a' => static fn (ContainerInterface $container): mixed => $container->get('b'),
            'b' => static fn (ContainerInterface $container): mixed => $container->get('a'),
        ]);

        $this->expectException(ContainerException::class);
        $container->get('a');
    }

    public function testThrowsNotFoundWhenMissing(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $container->get('missing');
    }

    public function testAutowiringUsesDefaultValuesForOptionalParameters(): void
    {
        $container = new Container();

        self::assertSame('default', $container->get(OptionalArguments::class)->mode);
    }

    public function testMissingRequiredScalarThrowsContainerException(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Unable to autowire');
        $container->get(RequiredScalar::class);
    }

    public function testUnionConstructorParameterIsNotGuessed(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('ambiguous union or intersection type');
        $container->get(UnionArguments::class);
    }

    public function testMissingInterfaceCanBeRegisteredExplicitly(): void
    {
        $container = new Container();

        self::assertFalse($container->has(\Psr\Container\NotFoundExceptionInterface::class));
        $error = new NotFoundException('missing');
        $container->set(\Psr\Container\NotFoundExceptionInterface::class, $error);
        self::assertSame($error, $container->get(\Psr\Container\NotFoundExceptionInterface::class));
    }

    public function testResolutionErrorDoesNotLeaveCircularDependencyFlagSet(): void
    {
        $container = new Container([
            'service' => static fn (): never => throw new \LogicException('Failure'),
        ]);

        try {
            $container->get('service');
            self::fail('The factory should fail.');
        } catch (ContainerException $e) {
            self::assertStringContainsString('Failure', $e->getMessage());
        }

        $container->set('service', 123);
        self::assertSame(123, $container->get('service'));
    }
}
