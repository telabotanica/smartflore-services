# Smartflore-services

## Description

New API for mobile app Smart'Flore & Smart'form dashboard

## Installation

- PHP 7.4
- Symfony 5.4

```bash
composer install
```

## Configuration

### Database

- MySQL 8 database

## Run

```bash
symfony console doctrine:database:create
symfony console make:migration
symfony console doctrine:migrations:migrate
symfony console cache:clear
symfony console cache:warmup
```

```bash
symfony serve
```

## Usage
### Old pages import

Import eFloreRedaction_pages.sql file into the new database then:

```bash
symfony console app:import:fiches
```

### Old Trails & user favorites import

Import eFloreRedaction_triples.sql file into the new database then:

```bash
symfony console app:import:sentiers
```

### Build cache for trails list
#### V1
```bash
symfony console app:cache:refresh all
```

#### V2 (2026)
```bash
# Première construction ou reconstruction complète
symfony console app:cache:build-trails-list

# En production (sans interaction)
php bin/console app:cache:build-trails-list --no-ansi -q
```
## Tests
### Tous les tests
```bash
./bin/phpunit
```

### Tous les tests des contrôleurs
```bash
./bin/phpunit tests/Controller/
```

### Un fichier de test spécifique
```bash
./bin/phpunit tests/Controller/AdminControllerTest.php
```

### Un test spécifique
```bash
./bin/phpunit --filter "testPublishTrail$" tests/Controller/AdminControllerTest.php
```