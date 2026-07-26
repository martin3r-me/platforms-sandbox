<?php

namespace Platform\Sandbox;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SandboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Morph-Map
        Relation::morphMap([
            'sandbox_project'     => \Platform\Sandbox\Models\SandboxProject::class,
            'sandbox_phase'       => \Platform\Sandbox\Models\SandboxPhase::class,
            'sandbox_action'      => \Platform\Sandbox\Models\SandboxAction::class,
            'sandbox_stakeholder' => \Platform\Sandbox\Models\SandboxStakeholder::class,
            'sandbox_log'         => \Platform\Sandbox\Models\SandboxLog::class,
        ]);

        // Config laden
        $this->mergeConfigFrom(__DIR__.'/../config/sandbox.php', 'sandbox');

        // Modul registrieren
        if (
            config()->has('sandbox.routing') &&
            config()->has('sandbox.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'sandbox',
                'title'      => 'Sandbox',
                'group'      => 'admin',
                'routing'    => config('sandbox.routing'),
                'guard'      => config('sandbox.guard'),
                'navigation' => config('sandbox.navigation'),
                'sidebar'    => config('sandbox.sidebar'),
            ]);
        }

        // Routes laden
        if (PlatformCore::getModule('sandbox')) {
            ModuleRouter::group('sandbox', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Migrationen laden
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Config veröffentlichen
        $this->publishes([
            __DIR__.'/../config/sandbox.php' => config_path('sandbox.php'),
        ], 'config');

        // Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'sandbox');
        $this->registerLivewireComponents();

        // Tools registrieren
        $this->registerTools();

        // EntityLinkProvider registrieren (loose Kopplung mit Organization-Modul)
        try {
            resolve(\Platform\Organization\Services\EntityLinkRegistry::class)
                ->register(new \Platform\Sandbox\Organization\SandboxEntityLinkProvider());
        } catch (\Throwable $e) {
            // Organization-Modul nicht geladen
        }

        // Error Reporter
        try {
            resolve(\Platform\Core\Services\ErrorReporterRegistry::class)
                ->register('sandbox', 'Platform\\Sandbox');
        } catch (\Throwable $e) {}
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Sandbox\\Livewire';
        $prefix = 'sandbox';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // SandboxProject CRUD
            $registry->register(new \Platform\Sandbox\Tools\ListSandboxProjectsTool());
            $registry->register(new \Platform\Sandbox\Tools\CreateSandboxProjectTool());
            $registry->register(new \Platform\Sandbox\Tools\UpdateSandboxProjectTool());
            $registry->register(new \Platform\Sandbox\Tools\DeleteSandboxProjectTool());

            // SandboxPhase
            $registry->register(new \Platform\Sandbox\Tools\ListSandboxPhasesTool());
            $registry->register(new \Platform\Sandbox\Tools\UpdateSandboxPhaseTool());

            // SandboxStakeholder CRUD
            $registry->register(new \Platform\Sandbox\Tools\ListSandboxStakeholdersTool());
            $registry->register(new \Platform\Sandbox\Tools\CreateSandboxStakeholderTool());
            $registry->register(new \Platform\Sandbox\Tools\UpdateSandboxStakeholderTool());
            $registry->register(new \Platform\Sandbox\Tools\DeleteSandboxStakeholderTool());

            // SandboxAction CRUD
            $registry->register(new \Platform\Sandbox\Tools\ListSandboxActionsTool());
            $registry->register(new \Platform\Sandbox\Tools\CreateSandboxActionTool());
            $registry->register(new \Platform\Sandbox\Tools\UpdateSandboxActionTool());
            $registry->register(new \Platform\Sandbox\Tools\DeleteSandboxActionTool());

            // SandboxLog CRUD
            $registry->register(new \Platform\Sandbox\Tools\ListSandboxLogsTool());
            $registry->register(new \Platform\Sandbox\Tools\CreateSandboxLogTool());
            $registry->register(new \Platform\Sandbox\Tools\UpdateSandboxLogTool());
            $registry->register(new \Platform\Sandbox\Tools\DeleteSandboxLogTool());

            // Analytics
            $registry->register(new \Platform\Sandbox\Tools\GetSandboxProgressTool());
            $registry->register(new \Platform\Sandbox\Tools\GetSandboxBoardTool());

        } catch (\Throwable $e) {
            \Log::warning('Sandbox: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }
}
