# Contributing to Encapsula

Thank you for considering contributing to Encapsula.

## Development Setup

```bash
git clone https://github.com/shudhuiami/Encapsula.git
cd Encapsula
composer install
```

## Running Tests

```bash
vendor/bin/phpunit
```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) with the `laravel` preset.

```bash
# Check style
vendor/bin/pint --test

# Fix style
vendor/bin/pint
```

## Static Analysis

This project uses [Larastan](https://github.com/larastan/larastan) at level 6.

```bash
vendor/bin/phpstan analyse
```

## Pull Request Guidelines

1. Fork the repository and create a branch from `main`.
2. Add tests for any new functionality.
3. Ensure all tests pass and code style is clean.
4. Run static analysis with zero errors.
5. Write a clear commit message and PR description.

## Reporting Issues

Open an issue on GitHub with:

- A clear title and description.
- Steps to reproduce the problem.
- Expected vs actual behavior.
- PHP and Laravel versions.
