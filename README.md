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
| `AssetLoaderTrait`  | Script, style, and script-module registration    |
| `AutoloaderTrait`   | Custom class autoloading                         |
| `VIPHelpers`        | Wrappers for VIP-only functions, with fallbacks  |

#### Encrypting values

Extend `AbstractEncryptor` with the name of the `wp-config.php` constant that holds the key:

```php
final class Encryptor extends AbstractEncryptor {
	protected static function get_constant_key(): string {
		return 'MY_PLUGIN_ENCRYPTION_KEY';
	}
}

$stored = Encryptor::encrypt( $secret ); // 'enc:v1:...'
$secret = Encryptor::decrypt( $stored );
```

`encrypt()` and `decrypt()` return a `WP_Error` on failure. `decrypt()` returns `not_encrypted` if the value lacks the prefix, and `decryption_failed` if it's malformed, was tampered with, or was encrypted with a different key. `is_encrypted()` only checks the prefix.

Set the constant to a long random string, e.g. from `openssl rand -base64 32`. If it isn't set, `LOGGED_IN_KEY` is used instead (with a `_doing_it_wrong()` notice), and values become unreadable if the salts are rotated. If neither is set, a `LogicException` is thrown.

After you add the constant, values encrypted with `LOGGED_IN_KEY` still decrypt. Re-encrypt them to move them to the new key.

Subclasses can override these static properties:

| Property      | Default       | Notes                                                                                          |
| ------------- | ------------- | ---------------------------------------------------------------------------------------------- |
| `$method`     | `aes-256-gcm` | Must be an authenticated (AEAD) cipher. Other ciphers need custom `encrypt()` and `decrypt()`. |
| `$tag_length` | `16`          | Authentication tag length, in bytes.                                                           |
| `$prefix`     | `enc:v1:`     | Marks and versions encrypted values.                                                           |

Changing any of these, the constant's name, or its value means existing values can no longer be decrypted.

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

See [DEVELOPMENT.md](./docs/DEVELOPMENT.md) for the full setup, standards, and testing guide, and [CONTRIBUTING.md](./docs/CONTRIBUTING.md) before opening a PR.

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
