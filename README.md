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
symfony console doctrine:migrations:migrate
symfony console doctrine:fixtures:load
symfony console cache:clear
symfony console cache:warmup
```

```bash
symfony serve
```

## Usage
