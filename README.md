# WilyCode Container

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Tests](https://github.com/wilycode/container/actions/workflows/tests.yml/badge.svg)](https://github.com/wilycode/container/actions/workflows/tests.yml)

**A small PSR-11 dependency injection container for PHP 8.4+.**

Register services explicitly or let the container create classes from their constructor dependencies. Services are reused after the first resolution. No framework is required.

> **WilyCode PHP Ecosystem**
>
> Part of **[WilyCode](https://github.com/wilycode/wilycode)** — independent, reusable PHP components with their own repositories, releases, and Composer packages.
>
> **[Explore the WilyCode ecosystem →](https://github.com/wilycode/wilycode)**

## Features

- Implements PSR-11 `ContainerInterface` (`get()` and `has()`).
- Registers services with `set()` or constructor definitions.
- Uses `Closure` definitions as factories.
- Autowires concrete classes through constructor type hints.
- Reuses resolved instances (singleton behavior).
- Detects circular dependencies and reports resolution errors.
- No runtime dependencies beyond `psr/container`.

## Requirements

- PHP 8.4 or newer.
- `psr/container:^2.0` (installed automatically).

## Installation

```bash
composer require wilycode/container
```

## Quick start

```php
<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use WilyCode\Container\Container;

final class GreetingService
{
    public function __construct(private ContainerInterface $container) {}

    public function greet(): string
    {
        return 'Hello, ' . $this->container->get('app.name') . '!';
    }
}

$container = new Container();
$container->set('app.name', 'WilyCode');

// GreetingService is created automatically.
$greeting = $container->get(GreetingService::class);
echo $greeting->greet(); // Hello, WilyCode!
```

`get()` returns the same resolved instance on later calls. The container also provides itself for `ContainerInterface::class` and `Container::class`.

## Registering services

### Values

Pass a value directly to `set()`. It is returned unchanged, even if the value is a callable object.

```php
$container->set('app.debug', true);
$debug = $container->get('app.debug'); // true
```

### Factories

A `Closure` is treated as a factory. The container passes itself to the factory and stores the returned value.

```php
$container->set(
    LoggerInterface::class,
    static fn (ContainerInterface $container): LoggerInterface => new FileLogger('/tmp/app.log'),
);

$logger = $container->get(LoggerInterface::class);
```

`LoggerInterface` and `FileLogger` are examples provided by your application; they are not included in this package. To resolve interfaces, register an implementation explicitly.

### Constructor autowiring

When no definition exists, the container can instantiate an existing, concrete class and resolve its class-typed constructor parameters.

```php
final class Mailer
{
    public function __construct(private Logger $logger) {}
}

$mailer = $container->get(Mailer::class);
```

`Logger` in this example must be a concrete, instantiable class (or registered under that name). The container does **not** guess interface implementations or required scalar values such as strings and integers. Provide these using factories or definitions. Optional constructor parameters may use their default values.

## Replacing or removing entries

```php
$container->set('app.mode', 'development');
$container->set('app.mode', 'production'); // Replaces the previous value.

$container->remove('app.mode');
$exists = $container->has('app.mode'); // false
```

`has()` checks whether a definition, cached entry, or existing class is known. It does not instantiate the class.

## Errors

- `NotFoundException` is thrown for missing entry IDs or class names.
- `ContainerException` is thrown for circular dependencies and failed resolutions, including missing required constructor arguments.

Both exceptions implement the corresponding PSR-11 exception interfaces. Autowired classes should have unambiguous constructor dependencies.

## Related packages

| Package | Purpose |
| --- | --- |
| [wilycode/router](https://github.com/wilycode/router) | HTTP routing with PSR-11 container integration |
| [wilycode/router-attribute](https://github.com/wilycode/router-attribute) | Route declarations using PHP attributes |

## Development

```bash
composer install
composer validate --strict
composer test
```

Tests run on GitHub Actions for PHP 8.4. The `tests/` directory remains in GitHub but is excluded from distribution archives.

## Contributing

Bug reports and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) and [GitHub Issues](https://github.com/wilycode/container/issues).

## License

MIT © 2026 Simon Piter. See [LICENSE](LICENSE).
