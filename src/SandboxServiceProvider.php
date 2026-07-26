<?php

namespace Platform\Sandbox;

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
        // Config laden (Laravel Best Practice: in register())
        $this->mergeConfigFrom(__DIR__.'/../config/sandbox.php', 'sandbox');
    }

    public function boot(): void
    {
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
}
