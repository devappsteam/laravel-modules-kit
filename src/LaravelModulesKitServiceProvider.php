<?php

namespace DevApps\LaravelModulesKit;

use DevApps\LaravelModulesKit\Console\Commands\MakeModuleCommand;
use DevApps\LaravelModulesKit\Support\ModuleLoader;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;

class LaravelModulesKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/laravel-modules-kit.php',
            'laravel-modules-kit'
        );

        $this->app->singleton(ModuleLoader::class, fn($app) => new ModuleLoader(
            $app,
            $app->make(ConfigRepository::class)
        ));

        if ($this->modulesEnabled()) {
            $this->app->make(ModuleLoader::class)->registerModules();
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModuleCommand::class,
            ]);

            $this->publishes([
                dirname(__DIR__) . '/config/laravel-modules-kit.php' => config_path('laravel-modules-kit.php'),
            ], 'laravel-modules-kit-config');

            $this->publishes([
                dirname(__DIR__) . '/stubs' => base_path(config('laravel-modules-kit.generator.stubs_path')),
            ], 'laravel-modules-kit-stubs');
        }

        if ($this->modulesEnabled()) {
            $this->app->make(ModuleLoader::class)->bootModules($this);
        }
    }

    protected function modulesEnabled(): bool
    {
        return (bool) config('laravel-modules-kit.enabled', true);
    }

    public function registerModuleMigrations(string $path): void
    {
        $this->loadMigrationsFrom($path);
    }

    public function registerModuleViews(array $paths, string $namespace): void
    {
        $this->loadViewsFrom($paths, $namespace);
    }

    public function publishModuleResources(array $paths, string $group): void
    {
        $this->publishes($paths, $group);
    }
}
