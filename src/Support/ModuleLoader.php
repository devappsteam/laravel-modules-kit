<?php

namespace DevApps\LaravelModulesKit\Support;

use DevApps\LaravelModulesKit\LaravelModulesKitServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class ModuleLoader
{
    public function __construct(
        protected Application $app,
        protected ConfigRepository $config
    ) {}

    public function registerModules(): void
    {
        foreach ($this->moduleDirectories() as $modulePath) {
            $moduleName = basename($modulePath);

            $this->loadModuleConfig($moduleName);
            $this->registerModuleProvider($moduleName);
        }
    }

    public function bootModules(LaravelModulesKitServiceProvider $provider): void
    {
        foreach ($this->moduleDirectories() as $modulePath) {
            $moduleName = basename($modulePath);

            $this->loadModuleRoutes($moduleName);
            $this->loadModuleMigrations($provider, $moduleName);
            $this->loadModuleViews($provider, $moduleName);
            $this->publishModuleConfig($provider, $moduleName);
        }
    }

    protected function moduleDirectories(): array
    {
        $modulesPath = base_path(config('laravel-modules-kit.paths.modules', 'app/Modules'));

        if (!File::exists($modulesPath)) {
            return [];
        }

        return File::directories($modulesPath);
    }

    protected function registerModuleProvider(string $moduleName): void
    {
        if (!config('laravel-modules-kit.runtime.register_module_providers', true)) {
            return;
        }

        $providerClass = sprintf(
            '%s\\%s\\Providers\\%sServiceProvider',
            trim(config('laravel-modules-kit.namespace', 'App\\Modules'), '\\'),
            $moduleName,
            $moduleName,
        );

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    protected function loadModuleConfig(string $moduleName): void
    {
        if (!config('laravel-modules-kit.runtime.load_configs', true)) {
            return;
        }

        $configPath = $this->modulePath($moduleName, 'Config');

        if (!File::exists($configPath)) {
            return;
        }

        foreach (File::files($configPath) as $configFile) {
            $fileName = $configFile->getFilenameWithoutExtension();
            $moduleKey = Str::kebab($moduleName);
            $configKey = $fileName === 'module'
                ? "modules.{$moduleKey}"
                : "modules.{$moduleKey}.{$fileName}";
            $current = $this->config->get($configKey, []);
            $loaded = require $configFile->getPathname();

            $this->config->set(
                $configKey,
                array_replace_recursive($loaded, is_array($current) ? $current : [])
            );
        }
    }

    protected function loadModuleRoutes(string $moduleName): void
    {
        if (!config('laravel-modules-kit.runtime.load_routes', true)) {
            return;
        }

        $routesPath = $this->modulePath($moduleName, 'Routes');

        if (!File::exists($routesPath)) {
            return;
        }

        $webRoutesFile = $routesPath . '/web.php';
        if (File::exists($webRoutesFile)) {
            Route::middleware(config('laravel-modules-kit.web.middleware', ['web']))
                ->group(function () use ($webRoutesFile): void {
                    require $webRoutesFile;
                });
        }

        $apiRoutesFile = $routesPath . '/api.php';
        if (File::exists($apiRoutesFile)) {
            $prefix = trim((string) config('laravel-modules-kit.api.prefix', 'api/v1'), '/');
            $route = Route::middleware(config('laravel-modules-kit.api.middleware', ['api']));

            if ($prefix !== '') {
                $route = $route->prefix($prefix);
            }

            $route->group(function () use ($apiRoutesFile): void {
                require $apiRoutesFile;
            });
        }
    }

    protected function loadModuleMigrations(LaravelModulesKitServiceProvider $provider, string $moduleName): void
    {
        if (!config('laravel-modules-kit.runtime.load_migrations', true)) {
            return;
        }

        $migrationsPath = $this->modulePath($moduleName, 'Database/Migrations');

        if (File::exists($migrationsPath)) {
            $provider->registerModuleMigrations($migrationsPath);
        }
    }

    protected function loadModuleViews(LaravelModulesKitServiceProvider $provider, string $moduleName): void
    {
        if (!config('laravel-modules-kit.runtime.load_views', true)) {
            return;
        }

        $viewsPath = $this->modulePath($moduleName, 'Resources/views');

        if (!File::exists($viewsPath)) {
            return;
        }

        $provider->registerModuleViews([
            resource_path(trim(config('laravel-modules-kit.paths.views_overrides', 'resources/views/modules'), '/') . '/' . $moduleName),
            $viewsPath,
        ], Str::kebab($moduleName));
    }

    protected function publishModuleConfig(LaravelModulesKitServiceProvider $provider, string $moduleName): void
    {
        if (!config('laravel-modules-kit.runtime.publish_module_configs', true)) {
            return;
        }

        $configPath = $this->modulePath($moduleName, 'Config');

        if (!File::exists($configPath)) {
            return;
        }

        foreach (File::files($configPath) as $configFile) {
            $provider->publishModuleResources(
                [
                    $configFile->getPathname() => config_path('modules/' . Str::kebab($moduleName) . '/' . $configFile->getFilename()),
                ],
                Str::kebab($moduleName) . '-module-config'
            );
        }
    }

    protected function modulePath(string $moduleName, string $suffix = ''): string
    {
        $path = base_path(trim(config('laravel-modules-kit.paths.modules', 'app/Modules'), '/')) . '/' . $moduleName;

        if ($suffix === '') {
            return $path;
        }

        return $path . '/' . trim($suffix, '/');
    }
}
