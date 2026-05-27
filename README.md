# Laravel Modules Kit

Reusable Laravel package for generating and loading application modules with API, Blade, or hybrid scaffolding.

## Features

- Generates modules with `make:module`.
- Supports `api`, `blade`, and `hybrid` module types.
- Loads module providers, routes, migrations, views, and config files automatically.
- Publishes package config and stubs for local customization.

## Local development installation

Add a path repository in the host Laravel project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "pacote/laravel-modules-kit",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "devapps/laravel-modules-kit": "dev-main"
    }
}
```

Then run:

```bash
composer update devapps/laravel-modules-kit
```

## Commands

```bash
php artisan make:module Billing --type=api
php artisan make:module Catalog --type=blade
php artisan make:module Orders --type=hybrid
```

## Generated structure

The package generates modules under `app/Modules/<ModuleName>` by default, including:

- model
- repository contract and repository implementation
- service
- form requests
- policy
- factory and seeder
- migration
- module service provider
- API routes, Blade routes, or both
- Blade views when the module type supports web rendering

## Runtime loading

When installed in a Laravel application, the package scans the configured modules directory and automatically:

- registers each module provider
- loads module routes from `Routes/web.php` and `Routes/api.php`
- loads module migrations from `Database/Migrations`
- loads module views from `Resources/views`
- loads module config files from `Config`

## Publishing

Publish config:

```bash
php artisan vendor:publish --tag=laravel-modules-kit-config
```

Publish stubs:

```bash
php artisan vendor:publish --tag=laravel-modules-kit-stubs
```

## Config highlights

The package config lets you customize:

- base modules path
- module namespace
- API prefix and middleware
- web middleware
- runtime loading toggles
- published stubs path

## Notes

- The default module namespace is `App\\Modules`.
- The default API prefix is `api/v1`.
- Blade view overrides are read from `resources/views/modules/<ModuleName>`.
