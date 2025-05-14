# Development Guidelines

This document provides information about development tools and processes for the DbipUpdater plugin.

## Project Structure

The project is organized as follows:

- `/DbipUpdater/` - Contains the actual Matomo plugin code
  - `Plugin.php` - Main plugin class
  - `Settings.php` - Plugin settings
  - `Task/` - Background tasks
  - `plugin.json` - Plugin metadata
- Root directory - Contains development tools and documentation
  - `composer.json` - Dependencies and development scripts
  - `phpcs.xml` - PHP coding standards configuration
  - `markdownlint.json` - Markdown linting configuration
  - `DEVELOPMENT.md` - Development documentation
  - `CHANGELOG.md` - Version history

## Code Quality Tools

This project uses linting tools to maintain code quality and consistent style:

### PHP Linting

We use PHP_CodeSniffer to enforce PSR-12 coding standards:

```bash
# Check PHP code style
composer run lint-php

# Automatically fix PHP code style issues
composer run lint-php-fix
```

### Markdown Linting

We use markdownlint-cli to ensure consistent Markdown formatting:

```bash
# Check Markdown files
composer run lint-md
```

### Running All Linters

To run all linting checks at once:

```bash
composer run lint
```

## Setup for Development

1. Clone the repository
2. Run `composer install` to install dependencies
3. Install markdownlint-cli: `npm install -g markdownlint-cli`

## Configuration Files

- `phpcs.xml` - PHP_CodeSniffer configuration
- `markdownlint.json` - Markdown linter configuration
- `composer.json` - Composer dependencies and scripts

## Continuous Integration

It's recommended to run the linting checks before creating a pull request:

```bash
composer run lint
```

This helps maintain code quality and consistent style across the project.
