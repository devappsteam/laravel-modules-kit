# Laravel Modules Kit

Reusable Laravel package for generating and loading application modules with API, Blade, or hybrid scaffolding.

## Features

* Generates modules with `make:module`
* Supports `api` (default), `blade`, and `hybrid` module types
* Supports **submodules** — organize modules hierarchically (e.g. `ERP/Companies`)
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

### Basic usage

The default type is **`api`** — no flag needed:

```bash
php artisan make:module Billing
```

### Choosing the module type

Use one of the available flags to select a different type:

```bash
php artisan make:module Billing           # api (default)
php artisan make:module Billing --api     # api (explicit)
php artisan make:module Catalog --blade   # blade
php artisan make:module Orders --hybrid   # api + blade
```

### Submodules

Organize modules hierarchically by separating segments with `/`:

```bash
php artisan make:module ERP/Companies
php artisan make:module ERP/HR/Employees --blade
php artisan make:module Finance/Invoices  --hybrid
```

This generates the module under `app/Modules/ERP/Companies/` with the
PHP namespace `App\Modules\ERP\Companies`.

### Force overwrite

```bash
php artisan make:module Billing --force
```

## Generated structure

The package generates modules under `app/Modules/<ModuleName>` by default (supports subpaths).

```
app/Modules/
└── ERP/
    └── Companies/
        ├── Config/
        │   └── module.php
        ├── Database/
        │   ├── Factories/
        │   ├── Migrations/
        │   └── Seeders/
        ├── Http/
        │   ├── Controllers/
        │   ├── Requests/
        │   └── Resources/       # api and hybrid only
        ├── Models/
        ├── Policies/
        ├── Providers/
        ├── Repositories/
        │   └── Contracts/
        ├── Resources/
        │   └── views/           # blade and hybrid only
        │       └── partials/
        ├── Routes/
        │   ├── api.php          # api and hybrid
        │   └── web.php          # blade and hybrid
        └── Services/
```

Each generated file includes:

* **Model** — with UUID trait, SoftDeletes, and fillable stub
* **Repository** — interface + implementation
* **Service** — thin service layer wrapping the repository
* **Form Requests** — `Store` and `Update` requests
* **Policy** — registered automatically via the ServiceProvider
* **Controller** — tailored to the selected type (api / blade / hybrid)
* **Resource** — JSON resource (api and hybrid)
* **Views** — index, create, edit, show, partials/form (blade and hybrid)
* **Factory & Seeder**
* **Migration**
* **ServiceProvider** — binds repository interface and registers the policy

## Runtime loading

When installed in a Laravel application, the package scans the configured modules directory (recursively) and automatically:

* registers each module's ServiceProvider
* loads module routes from `Routes/web.php` and `Routes/api.php`
* loads module migrations from `Database/Migrations`
* loads module views from `Resources/views`
* loads module config files from `Config`

## Config highlights

The package config lets you customize:

* base modules path (`app/Modules` by default)
* module namespace (`App\Modules` by default)
* API prefix and middleware
* web middleware
* runtime loading toggles
* published stubs path
* default module type (`api` by default)

## Notes

* The default module type is `api` — omitting the flag always produces an API module
* Submodule paths accept `/` or `\` as separators
* The default API prefix is `api/v1`
* Blade view overrides are read from `resources/views/modules/<ModuleName>`
* The default module namespace is `App\Modules`
