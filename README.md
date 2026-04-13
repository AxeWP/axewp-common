# AxeWP Common

> Shared WordPress and WPGraphQL utilities for AxeWP plugins.

`axepress/axewp-common` is a PHP library providing reusable base classes, interfaces, and traits used the AxePress suite of WordPress plugins.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require axepress/axewp-common
```

## What's Included

### Core (`src/Core/`)

| Class / Trait       | Description                                      |
| ------------------- | ------------------------------------------------ |
| `Config`            | Hook prefix configuration for plugin integration |
| `AbstractEncryptor` | Base class for encryption utilities              |
| `AssetLoaderTrait`  | CSS/JS asset loading helpers                     |
| `AutoloaderTrait`   | Custom class autoloading                         |

### GraphQL (`src/GraphQL/`)

Abstracts for registering [WPGraphQL](https://wpgraphql.com) types with a consistent API:

**Abstracts (`src/GraphQL/Abstracts/`)**

| Abstract         | GraphQL Type                      |
| ---------------- | --------------------------------- |
| `Type`           | Base type with registration hooks |
| `ObjectType`     | Object types with fields          |
| `MutationType`   | Mutation types                    |
| `InputType`      | Input types                       |
| `EnumType`       | Enum types                        |
| `UnionType`      | Union types                       |
| `InterfaceType`  | Interface types                   |
| `ConnectionType` | Connection types                  |
| `FieldsType`     | Types with fields                 |

**Interfaces (`src/GraphQL/Interfaces/`)**

`GraphQLType`, `TypeWithFields`, `TypeWithInputFields`, `TypeWithInterfaces`, `TypeWithConnections`

**Traits (`src/GraphQL/Traits/`)**

`TypeNameTrait`, `TypeResolverTrait`

### Contracts (`src/Contracts/`)

| Interface / Trait | Description                               |
| ----------------- | ----------------------------------------- |
| `Registrable`     | Interface for classes that register hooks |
| `Singleton`       | Trait implementing the singleton pattern  |

## Development

### Quick Start

```bash

# Install the NPM dependencies (using NVM)
nvm use
npm ci

# Install the PHP dependencies (using Composer)
composer install

# Lints
## Prettier
npm run format

## PHPCS
npm run lint:php
npm run lint:php:fix

## PHPStan
npm run lint:php:stan

## TypeScript
npm run lint:js:types
```

### Testing

```bash
# Start the wp-env test environment with Xdebug code coverage enabled
npm run wp-env:test start -- --xdebug=coverage

# Run the PHPUnit tests
npm run test:php
```

## License

[GPL-3.0-or-later](./LICENSE.md)
