# Agent Instructions for smartflore-services

## Quick Facts

- **Stack**: Symfony 5.4, PHP 7.4, MySQL 8, Doctrine ORM
- **Type**: API backend for mobile app (Smart'Flore) and web dashboard
- **Structure**: Single monolithic Symfony app with Entity/Repository/Service/Controller/Command layers
- **Entry point**: `bin/console` for CLI, `public/index.php` for HTTP

## Setup & Prerequisites

```bash
# Install dependencies
composer install

# Database setup (when DATABASE_URL env var is set)
symfony console doctrine:database:create
symfony console make:migration
symfony console doctrine:migrations:migrate

# Cache management (required after code changes)
symfony console cache:clear
symfony console cache:warmup

# Run dev server
symfony serve
```

**Important**: Always `cache:clear && cache:warmup` after schema/config changes, before running tests or verifying behavior.

## Database Notes

- Doctrine ORM with custom `JsonExtract` DQL function (see `config/packages/doctrine.yaml`)
- Naming strategy: `underscore_number_aware` (converts `userId` to `user_id` in DB)
- Test env appends `_test{TEST_TOKEN}` to dbname for parallel test isolation
- Custom mapping at `src/Entity/`; ensure migrations are generated before applying

## Testing

```bash
# Run all tests
./bin/phpunit

# Run single test file
./bin/phpunit tests/SomeTest.php

# With coverage
./bin/phpunit --coverage-html build/coverage

# Test env: APP_ENV=test (set in phpunit.xml.dist), uses separate DB
```

**Key fixtures**: Tests bootstrap via `tests/bootstrap.php`; check for test-specific services or DB seeds.

## Code Quality

```bash
# Type checking (level 6)
./vendor/bin/phpstan analyse

# Rector (refactoring rules)
./vendor/bin/rector process --dry-run  # preview changes
./vendor/bin/rector process             # apply changes
```

Paths analyzed: `bin/`, `config/`, `public/`, `src/`, `tests/` (see `phpstan.dist.neon`).

## Key Console Commands

- `app:import:fiches` — Import old pages from eFloreRedaction_pages.sql
- `app:import:sentiers` — Import old trails & favorites from eFloreRedaction_triples.sql
- `app:cache:refresh all` — V1 trail cache refresh
- `app:cache:build-trails-list` — V2 trail list cache build (use `--no-ansi -q` for production)

## Architecture

- **Controllers**: HTTP routing; minimal business logic
- **Services**: Core domain logic; reusable across controllers/commands
- **Repositories**: Data access via Doctrine; custom queries in Repository classes
- **Entities**: Doctrine-mapped objects in `src/Entity/`; include error handling per instructions
- **Commands**: CLI tasks for imports, cache refresh, migrations; treat as entry points

## Style & Conventions

- **Per instructions**: Always include error handling; identify 3 potential bugs post-write and correct
- **Per workflow.md**: Plan non-trivial tasks (3+ steps); verify before marking done; demand elegant solutions
- **Lang**: French comments/strings common in legacy code; respect existing patterns
- **Git**: User handles commits; do not attempt git operations beyond status checks

## Known Gotchas

1. **Cache invalidation**: Symfony caches config, DI, and routes aggressively. Clear cache after any Entity, Service, or config changes.
2. **Env isolation**: `.env.local` overrides `.env`; test env uses `.env.test`. Check both when debugging config issues.
3. **Doctrine migrations**: Generate via `make:migration` after Entity changes; never edit migrations by hand.
4. **Type coverage**: Rector configured to not enforce strict types yet (`withTypeCoverageLevel(0)`); add types gradually.
5. **DQL custom functions**: `JsonExtract` is registered in Doctrine config; used for JSON field queries.

## References

- **Project rules**: `.opencode/rules/instructions.md`, `.opencode/rules/workflow.md`
- **README**: Legacy import commands, trail cache V1/V2 usage
- **Composer.json**: Dependency versions pinned to Symfony 5.4.* and PHP >=7.2.5
