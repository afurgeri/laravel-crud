# Laravel CRUD

Declarative CRUD definitions, schema generation, filtering, sorting, and mutation managers for Laravel applications.

## Installation

```bash
composer require afurgeri/laravel-crud
```

The service provider is registered automatically through Laravel package discovery.

## Local development

This repository consumes the package from `modules/Crud` through a Composer path repository. Changes made in that directory are immediately available to the application after Composer's autoloader is regenerated.

## Requirements

- PHP 8.3+
- Laravel 13+

The package provides the backend contracts and managers. Frontend components and resource-specific controllers remain the responsibility of the consuming application.
