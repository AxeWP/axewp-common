# Development Guidelines - AxeWP Common

This document covers local setup, code standards, testing, and releasing for `axepress/axewp-common`.

## Table of Contents

- [Directory Structure](#directory-structure)
- [Local setup](#local-setup)
- [Code Contributions (Pull Requests)](#code-contributions-pull-requests)
- [Running Tests](#running-tests)
- [Releasing](#releasing)

## Directory Structure

| Path            | Description                                                                            |
| --------------- | -------------------------------------------------------------------------------------- |
| `src/`          | The library source, autoloaded under the `AxeWP\Common\` PSR-4 namespace.              |
| `src/Contracts` | Shared interfaces and traits (`Registrable`, `Singleton`).                             |
| `src/Core`      | Framework-agnostic utilities (`Config`, `AbstractEncryptor`, asset/autoloader traits). |
| `src/GraphQL`   | WPGraphQL type abstracts, interfaces, and traits.                                      |
| `tests/phpunit` | PHPUnit tests, autoloaded under the `AxeWP\Common\Tests\` namespace.                   |
| `tools/phpstan` | Concrete implementations of the library's traits, so PHPStan can analyze them.         |
| `docs/`         | Project documentation.                                                                 |

## Local setup

### Prerequisites

- [Node.js](https://nodejs.org/): v24.12.0+ ([NVM](https://nvm.sh/) recommended)
- npm: v11.14.1+
- [Docker](https://www.docker.com/)
- Optional: [Composer](https://getcomposer.org/) (if you prefer to run the Composer tools locally instead of using wp-env's built-in Composer)

You can use Docker and the `wp-env` tool to set up a local development environment, instead of manually installing the specific testing versions of WordPress, PHP, and Composer. For more information, see the [wp-env documentation](https://developer.wordpress.org/block-editor/packages/packages-env/).

### Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/AxeWP/axewp-common.git
   ```

2. Change into the project folder and install the NPM dependencies.

   ```bash
   # If you're using NVM, make sure to use the correct Node.js version:
   nvm install && nvm use

   # Then install the NPM dependencies:
   npm install
   ```

3. Start the local development environment:

   ```bash
   npm run wp-env start
   ```

   This will start a local WordPress environment with the library (and WPGraphQL) installed, and the following default configuration:

   - Site URL: <http://localhost:8888>
   - WP Admin URL: <http://localhost:8888/wp-admin/>
     - WP Admin Username: `admin`
     - WP Admin Password: `password`

4. Install the PHP dependencies using Composer, using either your local Composer installation or wp-env's built-in Composer:

   ```bash
   # With wp-env:
   npm run wp-env:cli -- composer install

   # Or with local Composer:
   composer install
   ```

### Useful Commands

#### Accessing the Local Environment

- `npm run wp-env start`: Start the local development environment.
- `npm run wp-env stop`: Stop the local development environment.
- `npm run wp-env:cli -- {YOUR_CMD_HERE}`: Run WP-CLI/Composer commands in the local environment.
- `npm run wp-env:test run cli -- --env-cwd=wp-content/plugins/axewp-common {YOUR_CMD_HERE}`: Run tooling in the tests container.
- `npm run wp-env clean all`: Resets the wp-env database.

#### Linting and Formatting

- `npm run format`: Formats JSON/Markdown/YAML using Prettier.
- `npm run lint:php`: Runs PHPCS linting on the PHP code.
- `npm run lint:php:fix`: Autofixes PHPCS linting issues.
- `npm run lint:php:stan`: Runs PHPStan static analysis on the PHP code.
- `npm run lint:js:types`: Runs TypeScript's `tsc` to check for type errors.
- `npm run prepare`: Installs git hooks with Lefthook.

## Code Contributions (Pull Requests)

### Workflow

This repository uses a single long-lived branch: `main`. Always create a new branch from `main` when working on a feature or bug fix.

Branches should be prefixed with the type of change (e.g. `feat`, `chore`, `tests`, `fix`, etc.) followed by a short description of the change. For example, a branch for a new feature called "Add new feature" could be named `feat/add-new-feature`.

Pull requests are **squash-merged** into `main`. Use a [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/) title (for example, `feat: add settings page`) so the squash commit on `main` can drive automated releases.

### Code Quality / Code Standards

#### PHP_CodeSniffer

This project uses [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer/) with the [WPGraphQL Coding Standards](https://github.com/AxeWP/WPGraphQL-Coding-Standards) ruleset. Our specific ruleset is defined in [`.phpcs.xml.dist`](../.phpcs.xml.dist).

```bash
npm run lint:php      # check
npm run lint:php:fix  # autofix what can be autofixed
```

#### PHPStan

This project uses [PHPStan](https://phpstan.org/) for static analysis. Our configuration is defined in [`phpstan.neon.dist`](../phpstan.neon.dist).

```bash
npm run lint:php:stan
```

Traits must be implemented for PHPStan to analyze them, which is what [`tools/phpstan/`](../tools/phpstan/) is for. When you add a new trait to `src/`, add a corresponding concrete implementation there.

#### Prettier

This project uses [wp-prettier](https://www.npmjs.com/package/wp-prettier), a WordPress-specific fork of Prettier that is compatible with WordPress Coding Standards. Our configuration is defined in [`.prettierrc.mjs`](../.prettierrc.mjs).

```bash
npm run format
```

#### TypeScript

There is no JavaScript source in this library, but `tsc` runs in CI to typecheck any config or tooling files.

```bash
npm run lint:js:types
```

### Pre-commit Hooks

This project uses [Lefthook](https://lefthook.dev/) to manage Git hooks. The configuration is defined in [`.lefthook.yml`](../.lefthook.yml).

By default, lefthook calls [lint-staged](https://github.com/okonet/lint-staged) to run linters on staged files before each commit. The lint-staged configuration is defined in [`.lintstagedrc.mjs`](../.lintstagedrc.mjs).

## Running Tests

### PHPUnit

PHPUnit tests can be run using the following command:

```bash
npm run test:php
```

To generate a code coverage report, make sure to start the testing environment with coverage mode enabled:

```bash
npm run wp-env:test start -- --xdebug=coverage

npm run test:php
```

> [!NOTE]
> The flag is `--xdebug=coverage`. `--xdebug-mode=coverage` is silently ignored by wp-env, so Xdebug never loads and the run fails with "No code coverage driver available".

You should see the HTML coverage report in the `tests/_output/html` directory and the clover XML report in `tests/_output/php-coverage.xml`.

### GitHub Workflows

GitHub workflows run the lints and tests on pull requests and on `main`. The entrypoint is [`.github/workflows/ci.yml`](../.github/workflows/ci.yml), which delegates to the reusable workflows in [AxeWP/plugin-infra](https://github.com/AxeWP/plugin-infra/tree/main/.github/workflows).

## Releasing

Releases are automated with [Release Please](https://github.com/googleapis/release-please-action) and Conventional Commits.

1. Merge pull requests into `main` with **squash merge** and a Conventional Commit title.
2. Release Please updates and maintains a release PR from `main` using:
   - [`.github/workflows/release.yml`](../.github/workflows/release.yml)
   - [`release-please-config.json`](../release-please-config.json)
   - [`.release-please-manifest.json`](../.release-please-manifest.json)
3. Merge the release PR when you're ready to ship. Packagist picks up the new tag automatically.
