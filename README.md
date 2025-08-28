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
