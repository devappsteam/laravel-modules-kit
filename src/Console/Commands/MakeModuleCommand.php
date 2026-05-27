<?php

namespace DevApps\LaravelModulesKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {name : Nome do modulo}
        {--type= : Tipo do modulo: api, blade ou hybrid}
        {--force : Sobrescrever arquivos do modulo existente}';

    protected $description = 'Cria um novo modulo Laravel com suporte a API, Blade ou modo hibrido';

    protected string $moduleName;

    protected string $modulePath;

    protected string $moduleType;

    protected string $stubsPath;

    public function handle(): int
    {
        $this->moduleName = Str::studly((string) $this->argument('name'));
        $this->moduleType = $this->resolveType();
        $this->modulePath = base_path(trim(config('laravel-modules-kit.paths.modules', 'app/Modules'), '/')) . '/' . $this->moduleName;
        $this->stubsPath = $this->resolveStubsPath();

        if (!$this->isValidType($this->moduleType)) {
            $this->error('Tipo invalido. Use api, blade ou hybrid.');

            return self::FAILURE;
        }

        if (File::exists($this->modulePath) && !$this->option('force')) {
            $this->error("O modulo {$this->moduleName} ja existe.");
            $this->info('Use --force para sobrescrever os arquivos existentes.');

            return self::FAILURE;
        }

        if (!File::exists($this->stubsPath)) {
            $this->error("Diretorio de stubs nao encontrado em: {$this->stubsPath}");

            return self::FAILURE;
        }

        $this->info("Criando modulo {$this->moduleName} ({$this->moduleType})...");

        $this->createModuleStructure();
        $this->generateFiles();

        $this->newLine();
        $this->info("Modulo {$this->moduleName} criado com sucesso.");
        $this->line("Local: {$this->modulePath}");

        return self::SUCCESS;
    }

    protected function createModuleStructure(): void
    {
        $directories = [
            'Models',
            'Repositories',
            'Repositories/Contracts',
            'Services',
            'Http',
            'Http/Controllers',
            'Http/Requests',
            'Policies',
            'Database/Migrations',
            'Database/Seeders',
            'Database/Factories',
            'Routes',
            'Config',
            'Providers',
        ];

        if ($this->supportsApi()) {
            $directories[] = 'Http/Resources';
        }

        if ($this->supportsBlade()) {
            $directories[] = 'Resources/views';
            $directories[] = 'Resources/views/partials';
        }

        foreach ($directories as $directory) {
            $path = $this->modulePath . '/' . $directory;

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                $this->line("  - criado: {$directory}");
            }
        }
    }

    protected function generateFiles(): void
    {
        $files = [
            'model.stub' => 'Models/' . $this->moduleName . '.php',
            'repository-interface.stub' => 'Repositories/Contracts/' . $this->moduleName . 'RepositoryInterface.php',
            'repository.stub' => 'Repositories/' . $this->moduleName . 'Repository.php',
            'service.stub' => 'Services/' . $this->moduleName . 'Service.php',
            'policy.stub' => 'Policies/' . $this->moduleName . 'Policy.php',
            'factory.stub' => 'Database/Factories/' . $this->moduleName . 'Factory.php',
            'seeder.stub' => 'Database/Seeders/' . $this->moduleName . 'Seeder.php',
            'config.stub' => 'Config/module.php',
            'service-provider.stub' => 'Providers/' . $this->moduleName . 'ServiceProvider.php',
        ];

        $controllerStub = match ($this->moduleType) {
            'api' => 'controller-api.stub',
            'blade' => 'controller-blade.stub',
            default => 'controller-hybrid.stub',
        };

        $files[$controllerStub] = 'Http/Controllers/' . $this->moduleName . 'Controller.php';

        if ($this->supportsApi()) {
            $files['resource.stub'] = 'Http/Resources/' . $this->moduleName . 'Resource.php';
            $files['routes-api.stub'] = 'Routes/api.php';
        }

        if ($this->supportsBlade()) {
            $files['routes-web.stub'] = 'Routes/web.php';
            $files['view-index.stub'] = 'Resources/views/index.blade.php';
            $files['view-create.stub'] = 'Resources/views/create.blade.php';
            $files['view-edit.stub'] = 'Resources/views/edit.blade.php';
            $files['view-show.stub'] = 'Resources/views/show.blade.php';
            $files['view-form.stub'] = 'Resources/views/partials/form.blade.php';
        }

        foreach ($files as $stub => $destination) {
            $this->generateFile($stub, $destination);
        }

        $this->generateRequestFile('Store' . $this->moduleName . 'Request');
        $this->generateRequestFile('Update' . $this->moduleName . 'Request');

        $this->generateMigration();
    }

    protected function generateFile(string $stub, string $destination, array $extraReplacements = []): void
    {
        $stubPath = $this->stubsPath . '/' . $stub;
        $destinationPath = $this->modulePath . '/' . $destination;

        if (!File::exists($stubPath)) {
            $this->warn("Stub nao encontrado: {$stub}");

            return;
        }

        $content = $this->replaceStubVariables(File::get($stubPath), $extraReplacements);

        File::put($destinationPath, $content);

        $this->line("  - gerado: {$destination}");
    }

    protected function generateRequestFile(string $className): void
    {
        $this->generateFile(
            'request.stub',
            'Http/Requests/' . $className . '.php',
            ['{{CLASS}}' => $className]
        );
    }

    protected function generateMigration(): void
    {
        $timestamp = now()->format('Y_m_d_His');
        $fileName = $timestamp . '_create_' . $this->tableName() . '_table.php';
        $stubPath = $this->stubsPath . '/migration.stub';
        $destinationPath = $this->modulePath . '/Database/Migrations/' . $fileName;

        if (!File::exists($stubPath)) {
            $this->warn('Stub de migration nao encontrado.');

            return;
        }

        File::put(
            $destinationPath,
            $this->replaceStubVariables(File::get($stubPath))
        );

        $this->line("  - gerado: Database/Migrations/{$fileName}");
    }

    protected function replaceStubVariables(string $content, array $extraReplacements = []): string
    {
        $moduleLower = Str::camel($this->moduleName);
        $modulePlural = Str::pluralStudly($this->moduleName);
        $moduleLowerPlural = Str::camel($modulePlural);
        $moduleKebab = Str::kebab($this->moduleName);
        $moduleKebabPlural = Str::kebab($modulePlural);

        $replacements = [
            '{{MODULE}}' => $this->moduleName,
            '{{MODULE_LOWER}}' => $moduleLower,
            '{{MODULE_LOWER_PLURAL}}' => $moduleLowerPlural,
            '{{MODULE_PLURAL}}' => $modulePlural,
            '{{MODULE_KEBAB}}' => $moduleKebab,
            '{{MODULE_KEBAB_PLURAL}}' => $moduleKebabPlural,
            '{{MODULE_UPPER}}' => Str::upper(Str::snake($this->moduleName)),
            '{{TABLE_NAME}}' => $this->tableName(),
        ];

        $replacements = $replacements + $extraReplacements;

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    protected function resolveType(): string
    {
        return Str::lower((string) ($this->option('type') ?: config('laravel-modules-kit.generator.default_type', 'hybrid')));
    }

    protected function resolveStubsPath(): string
    {
        $published = base_path(trim(config('laravel-modules-kit.generator.stubs_path', 'stubs/vendor/laravel-modules-kit'), '/'));

        if (File::exists($published)) {
            return $published;
        }

        return dirname(__DIR__, 3) . '/stubs';
    }

    protected function supportsApi(): bool
    {
        return in_array($this->moduleType, ['api', 'hybrid'], true);
    }

    protected function supportsBlade(): bool
    {
        return in_array($this->moduleType, ['blade', 'hybrid'], true);
    }

    protected function isValidType(string $type): bool
    {
        return in_array($type, ['api', 'blade', 'hybrid'], true);
    }

    protected function tableName(): string
    {
        return Str::snake(Str::pluralStudly($this->moduleName));
    }
}
