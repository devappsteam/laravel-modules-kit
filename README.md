# Laravel Modules Kit

Reusable Laravel package for generating and loading application modules with API, Blade, or hybrid scaffolding.

## Features

* Generates modules with `make:module`
* Supports `api`, `blade`, and `hybrid` module types
* Automatically loads module providers, routes, migrations, views, and config files
* Publishes package config and stubs for local customization
* Compatible with Laravel 10, 11 and 12

## Requirements

* PHP 8.1+
* Laravel 10.x, 11.x or 12.x

## Installation

Install the package via Composer:

```bash
composer require devapps/laravel-modules-kit
```

Or specify a version:

```bash
composer require devapps/laravel-modules-kit:^1.0
```

After installation, publish the configuration file if you want to customize the package:

```bash
php artisan vendor:publish --tag=laravel-modules-kit-config
```

Optionally, publish the stubs:

```bash
php artisan vendor:publish --tag=laravel-modules-kit-stubs
```

## Commands

```bash
php artisan make:module Billing --type=api
php artisan make:module Catalog --type=blade
php artisan make:module Orders --type=hybrid
```

## Generated structure

The package generates modules under `app/Modules/<ModuleName>` by default, including:

* model
* repository contract and repository implementation
* service
* form requests
* policy
* factory and seeder
* migration
* module service provider
* API routes, Blade routes, or both
* Blade views when the module type supports web rendering

## Runtime loading

When installed in a Laravel application, the package scans the configured modules directory and automatically:

* registers each module provider
* loads module routes from `Routes/web.php` and `Routes/api.php`
* loads module migrations from `Database/Migrations`
* loads module views from `Resources/views`
* loads module config files from `Config`

## Config highlights

The package config lets you customize:

* base modules path
* module namespace
* API prefix and middleware
* web middleware
* runtime loading toggles
* published stubs path

## Notes

* Compatible with Laravel 10.x, 11.x and 12.x
* The default module namespace is `App\\Modules`
* The default API prefix is `api/v1`
* Blade view overrides are read from `resources/views/modules/<ModuleName>`
