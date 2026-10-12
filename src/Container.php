<?php

declare(strict_types=1);

namespace WilyCode\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;
use WilyCode\Container\Exception\ContainerException;
use WilyCode\Container\Exception\NotFoundException;

/**
 * Small PSR-11 container with singleton definitions and constructor autowiring.
 *
 * A Closure is always a factory and receives the container. Every other value,
 * including callable objects, is stored as-is. Resolved entries are singletons.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $definitions;

    /** @var array<string, mixed> */
    private array $entries = [];

    /** @var array<string, true> */
    private array $resolving = [];

    /** @param array<string, mixed> $definitions */
    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
        $this->entries[ContainerInterface::class] = $this;
        $this->entries[self::class] = $this;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->entries)) {
            return $this->entries[$id];
        }

        if (isset($this->resolving[$id])) {
            throw new ContainerException('Circular dependency detected while resolving "' . $id . '".');
        }

        $this->resolving[$id] = true;

        try {
            if (array_key_exists($id, $this->definitions)) {
                return $this->entries[$id] = $this->resolveDefinition($this->definitions[$id]);
            }

            if (!class_exists($id)) {
                throw new NotFoundException('No entry or class found for "' . $id . '".');
            }

            return $this->entries[$id] = $this->autowire($id);
        } catch (NotFoundException|ContainerException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContainerException(
                sprintf('Failed to resolve "%s": %s', $id, $e->getMessage()),
                0,
                $e,
            );
        } finally {
            unset($this->resolving[$id]);
        }
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries)
            || array_key_exists($id, $this->definitions)
            || class_exists($id);
    }

    public function set(string $id, mixed $definition): self
    {
        $this->definitions[$id] = $definition;
        unset($this->entries[$id]);

        return $this;
    }

    public function remove(string $id): void
    {
        unset($this->definitions[$id], $this->entries[$id]);
    }

    private function resolveDefinition(mixed $definition): mixed
    {
        return $definition instanceof Closure
            ? $definition($this)
            : $definition;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function autowire(string $class): object
    {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException('Class "' . $class . '" is not instantiable.');
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($class, $parameter);
        }

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(string $class, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type === null) {
            return $this->defaultValueOrFail(
                $class,
                $parameter,
                'has no type',
            );
        }

        if ($type instanceof ReflectionIntersectionType || $type instanceof ReflectionUnionType) {
            return $this->defaultValueOrFail(
                $class,
                $parameter,
                'uses an ambiguous union or intersection type',
            );
        }

        if (!$type instanceof ReflectionNamedType) {
            return $this->defaultValueOrFail($class, $parameter, 'uses an unsupported type');
        }

        if ($type->isBuiltin()) {
            return $this->defaultValueOrFail(
                $class,
                $parameter,
                'uses built-in type ' . $type->getName(),
            );
        }

        $dependency = $type->getName();
        if ($this->has($dependency)) {
            return $this->get($dependency);
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        return $this->defaultValueOrFail(
            $class,
            $parameter,
            'cannot resolve ' . $dependency,
        );
    }

    private function defaultValueOrFail(
        string $class,
        ReflectionParameter $parameter,
        string $reason,
    ): mixed {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException(sprintf(
            'Unable to autowire %s: parameter $%s %s and has no default value.',
            $class,
            $parameter->getName(),
            $reason,
        ));
    }
}
