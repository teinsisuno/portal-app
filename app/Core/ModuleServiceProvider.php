<?php

namespace App\Core;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Daftar modul yang aktif di portal central.
     */
    protected array $modules = [
        'Auth',
        'Tenant',
        'Apps',
        'Subscription',
        'Payment',
        'Admin',
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->modules as $module) {
            $modulePath = app_path("Core/Modules/{$module}");

            $this->loadModuleRoutes($modulePath);
            $this->loadModuleMigrations($modulePath);
        }
    }

    /**
     * Auto-load web.php & api.php dari tiap modul.
     */
    protected function loadModuleRoutes(string $modulePath): void
    {
        foreach (['web.php', 'api.php'] as $routeFile) {
            $routePath = "{$modulePath}/Routes/{$routeFile}";

            if (is_file($routePath)) {
                Route::middleware($routeFile === 'api.php' ? 'api' : 'web')
                    ->group($routePath);
            }
        }
    }

    /**
     * Auto-load migrations dari tiap modul.
     */
    protected function loadModuleMigrations(string $modulePath): void
    {
        $migrationsPath = "{$modulePath}/Database/Migrations";

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
