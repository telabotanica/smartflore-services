# Symfony Documentation

This directory contains framework-specific documentation for modern Symfony development patterns.

## Contents

### API Platform
- [API Platform Overview](api-platform.md) - Resource configuration, operations, and best practices
- [State Providers & Processors](state-providers-processors.md) - Custom data retrieval and persistence
- [DTO Resources](dto-resources.md) - API design decoupled from entities

### Doctrine ORM
- [Entity Relationships](doctrine-relationships.md) - OneToMany, ManyToMany, and eager loading
- [Transactions](doctrine-transactions.md) - Consistency and rollback strategies
- [Performance](doctrine-performance.md) - Fetch modes, batch processing, indexing

### Symfony Core
- [Messenger](messenger.md) - Async processing, handlers, retry strategies
- [Security](security.md) - Voters, access control, firewalls
- [Cache](cache.md) - Cache pools, tags, HTTP caching

### Architecture
- [Hexagonal Architecture](hexagonal.md) - Ports & Adapters pattern
- [CQRS](cqrs.md) - Command/Query separation
- [Value Objects](value-objects.md) - Immutable domain objects

### Testing
- [TDD Workflow](tdd.md) - RED-GREEN-REFACTOR cycle
- [Functional Tests](functional-tests.md) - WebTestCase patterns
- [API Tests](api-tests.md) - Testing API Platform resources

## Supported Versions

| Component | Versions |
|-----------|----------|
| Symfony | 6.4 LTS, 7.x, 8.0 |
| API Platform | 3.x, 4.x |
| PHP | 8.2, 8.3, 8.4 |

## Quick Links

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [API Platform Documentation](https://api-platform.com/docs/)
- [Doctrine ORM Documentation](https://www.doctrine-project.org/projects/orm.html)
