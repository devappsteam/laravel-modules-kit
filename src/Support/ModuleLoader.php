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
        foreach ($this->moduleDirectories() as $moduleRelPath => $modulePath) {
            $this->loadModuleConfig($moduleRelPath, $modulePath);
            $this->registerModuleProvider($moduleRelPath, $modulePath);
        }
    }

    public function bootModules(LaravelModulesKitServiceProvider $provider): void
    {
        foreach ($this->moduleDirectories() as $moduleRelPath => $modulePath) {
            $this->loadModuleRoutes($modulePath);
            $this->loadModuleMigrations($provider, $modulePath);
            $this->loadModuleViews($provider, $moduleRelPath, $modulePath);
            $this->publishModuleConfig($provider, $moduleRelPath, $modulePath);
        }
    }

    /**
     * Returns all leaf module directories, recursively.
     *
     * A directory is considered a "leaf module" when it contains at least one
     * of the conventional module sub-directories (Providers, Models, Http, …).
     * Parent directories that only contain other directories (e.g. ERP/) are
     * skipped — they are namespace containers, not modules themselves.
     *
     * @return array<string, string>  key = relative path (e.g. "ERP/Companies"), value = absolute path
     */
    protected function moduleDirectories(): array
    {
        $basePath = base_path(trim(
            $this->config->get('laravel-modules-kit.paths.modules', 'app/Modules'),
            '/'
        ));

        if (! File::exists($basePath)) {
            return [];
        }

        $modules = [];

        $this->scanForModules($basePath, $basePath, $modules);

        return $modules;
    }

    /**
     * Recursively walk $currentPath looking for leaf module directories.
     *
     * @param string               $basePath     Root modules directory (absolute)
     * @param string               $currentPath  Current directory being scanned (absolute)
     * @param array<string,string> &$modules     Collected modules
     */
    protected function scanForModules(string $basePath, string $currentPath, array &$modules): void
    {
        $markerDirs = ['Providers', 'Models', 'Http', 'Services', 'Routes', 'Database'];

        foreach (File::directories($currentPath) as $dir) {
            $isLeaf = false;

            foreach (File::directories($dir) as $sub) {
                if (in_array(basename($sub), $markerDirs, true)) {
                    $isLeaf = true;
                    break;
                }
            }

            if ($isLeaf) {
                // Relative path from basePath, using forward slashes
                $relPath = ltrim(str_replace($basePath, '', $dir), DIRECTORY_SEPARATOR . '/');
                $relPath = str_replace(DIRECTORY_SEPARATOR, '/', $relPath);
                $modules[$relPath] = $dir;
            } else {
                // Not a leaf — could be a namespace container (e.g. ERP/); go deeper
                $this->scanForModules($basePath, $dir, $modules);
            }
        }
    }

    /**
     * Build the PHP namespace string from a relative module path.
     * "ERP/Companies" → "ERP\Companies"
     */
    protected function namespaceFromRelPath(string $relPath): string
    {
        return str_replace('/', '\\', $relPath);
    }

    /**
     * Build a config key-safe slug from a relative module path.
     * "ERP/Companies" → "erp.companies"
     */
    protected function configKeyFromRelPath(string $relPath): string
    {
        return implode('.', array_map(
            fn ($s) => Str::kebab($s),
            explode('/', $relPath)
        ));
    }

    protected function registerModuleProvider(string $moduleRelPath, string $modulePath): void
    {
        if (! $this->config->get('laravel-modules-kit.runtime.register_module_providers', true)) {
            return;
        }

        $moduleName    = basename($modulePath);
        $nsRelPath     = $this->namespaceFromRelPath($moduleRelPath);
        $baseNamespace = trim($this->config->get('laravel-modules-kit.namespace', 'App\\Modules'), '\\');

        $providerClass = sprintf(
            '%s\\%s\\Providers\\%sServiceProvider',
            $baseNamespace,
            $nsRelPath,
            $moduleName,
        );

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    protected function loadModuleConfig(string $moduleRelPath, string $modulePath): void
    {
        if (! $this->config->get('laravel-modules-kit.runtime.load_configs', true)) {
            return;
        }

        $configPath = $modulePath . '/Config';

        if (! File::exists($configPath)) {
            return;
        }

        $moduleKey = $this->configKeyFromRelPath($moduleRelPath);

        foreach (File::files($configPath) as $configFile) {
            $fileName  = $configFile->getFilenameWithoutExtension();
            $configKey = $fileName === 'module'
                ? "modules.{$moduleKey}"
                : "modules.{$moduleKey}.{$fileName}";

            $current = $this->config->get($configKey, []);
            $loaded  = require $configFile->getPathname();

            $this->config->set(
                $configKey,
                array_replace_recursive($loaded, is_array($current) ? $current : [])
            );
        }
    }

    protected function loadModuleRoutes(string $modulePath): void
    {
        if (! $this->config->get('laravel-modules-kit.runtime.load_routes', true)) {
            return;
        }

        $routesPath = $modulePath . '/Routes';

        if (! File::exists($routesPath)) {
            return;
        }

        $webRoutesFile = $routesPath . '/web.php';
        if (File::exists($webRoutesFile)) {
            Route::middleware($this->config->get('laravel-modules-kit.web.middleware', ['web']))
                ->group(function () use ($webRoutesFile): void {
                    require $webRoutesFile;
                });
        }

        $apiRoutesFile = $routesPath . '/api.php';
        if (File::exists($apiRoutesFile)) {
            $prefix = trim((string) $this->config->get('laravel-modules-kit.api.prefix', 'api/v1'), '/');
            $route  = Route::middleware($this->config->get('laravel-modules-kit.api.middleware', ['api']));

            if ($prefix !== '') {
                $route = $route->prefix($prefix);
            }

            $route->group(function () use ($apiRoutesFile): void {
                require $apiRoutesFile;
            });
        }
    }

    protected function loadModuleMigrations(LaravelModulesKitServiceProvider $provider, string $modulePath): void
    {
        if (! $this->config->get('laravel-modules-kit.runtime.load_migrations', true)) {
            return;
        }

        $migrationsPath = $modulePath . '/Database/Migrations';

        if (File::exists($migrationsPath)) {
            $provider->registerModuleMigrations($migrationsPath);
        }
    }

    protected function loadModuleViews(
        LaravelModulesKitServiceProvider $provider,
        string $moduleRelPath,
        string $modulePath
    ): void {
        if (! $this->config->get('laravel-modules-kit.runtime.load_views', true)) {
            return;
        }

        $viewsPath = $modulePath . '/Resources/views';

        if (! File::exists($viewsPath)) {
            return;
        }

        $moduleName    = basename($modulePath);
        $overridesBase = trim(
            $this->config->get('laravel-modules-kit.paths.views_overrides', 'resources/views/modules'),
            '/'
        );

        // Namespace uses kebab-cased relative path: "erp/companies" → "erp-companies"
        $viewNamespace = implode('-', array_map(
            fn ($s) => Str::kebab($s),
            explode('/', $moduleRelPath)
        ));

        $overridePath = resource_path($overridesBase . '/' . $moduleName);

        $provider->registerModuleViews([$overridePath, $viewsPath], $viewNamespace);
    }

    protected function publishModuleConfig(
        LaravelModulesKitServiceProvider $provider,
        string $moduleRelPath,
        string $modulePath
    ): void {
        if (! $this->config->get('laravel-modules-kit.runtime.publish_module_configs', true)) {
            return;
        }

        $configPath = $modulePath . '/Config';

        if (! File::exists($configPath)) {
            return;
        }

        $moduleKey = $this->configKeyFromRelPath($moduleRelPath);

        foreach (File::files($configPath) as $configFile) {
            $provider->publishModuleResources(
                [
                    $configFile->getPathname() => config_path('modules/' . str_replace('.', '/', $moduleKey) . '/' . $configFile->getFilename()),
                ],
                $moduleKey . '-module-config'
            );
        }
    }
}
