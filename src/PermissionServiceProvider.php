<?php

namespace Mca\Permission;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Mca\Permission\Console\DoctorPermissionCommand;
use Mca\Permission\Console\InstallPermissionCommand;
use Mca\Permission\Console\SyncMcaPackagePermissionsCommand;
use Mca\Permission\Http\Middleware\CheckPermission;
use Mca\Permission\Http\Middleware\EnsureMcaPackageAccess;
use Mca\Permission\Http\Middleware\EnsureMcaRoot;
use Mca\Permission\Http\Middleware\SetMcaPermissionLocale;
use Mca\Permission\Services\DepartmentService;
use Mca\Permission\Services\GrantResolverRegistry;
use Mca\Permission\Services\PackageAccessService;
use Mca\Permission\Services\PermissionScannerService;
use Mca\Permission\Services\PermissionService;
use Mca\Permission\Services\RoleService;
use Mca\Permission\Services\ScanSegmentService;
use Mca\Permission\Services\UserService;
use Mca\Permission\Support\PermissionMode;

class PermissionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/permission.php', 'permission');

        $this->app->singleton(GrantResolverRegistry::class);
        $this->app->singleton(PermissionService::class);
        $this->app->singleton(PackageAccessService::class);
        $this->app->singleton(PermissionScannerService::class);
        $this->app->singleton(RoleService::class);
        $this->app->singleton(UserService::class);
        $this->app->singleton(DepartmentService::class);
        $this->app->singleton(ScanSegmentService::class);
    }

    public function boot(): void
    {
        if (! config('permission.enabled', true)) {
            return;
        }

        $this->syncScanSegmentsFromConfig();
        $this->syncPackagePermissionsFromConfig();

        $this->registerPublishing();
        $this->registerMiddleware();
        $this->loadMigrations();
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mca-permission');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mca-permission');
        $this->registerRoutes();
        $this->registerHub();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPermissionCommand::class,
                DoctorPermissionCommand::class,
                SyncMcaPackagePermissionsCommand::class,
            ]);
        }
    }

    protected function registerHub(): void
    {
        if (! function_exists('mca_hub_register')) {
            return;
        }

        mca_hub_register('permission', [
            'enabled' => fn () => (bool) config('permission.enabled', true)
                && (bool) config('permission.ui.enabled', true),
        ]);
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (PermissionMode::supportsUserGrants()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations/modes/user');
        }

        if (PermissionMode::supportsDepartmentGrants()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations/modes/full');
        }
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/permission.php' => config_path('permission.php'),
        ], 'mca-permission-config');

        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/mca-permission'),
        ], 'mca-permission-lang');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/mca-permission'),
        ], 'mca-permission-views');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/mca-permission'),
        ], 'mca-permission-assets');

        $this->publishes([
            __DIR__.'/../stubs/routes/mca-permission.php' => base_path('routes/mca-permission.php'),
        ], 'mca-permission-routes');

        $this->publishes([
            __DIR__.'/../stubs/controllers' => app_path('Http/Controllers/Vendor/McaPermission'),
        ], 'mca-permission-controllers');

        $this->publishes([
            __DIR__.'/../stubs/requests' => app_path('Http/Requests/McaPermission'),
        ], 'mca-permission-requests');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mca-permission-migrations');

        $this->publishes([
            __DIR__.'/../stubs/migrations/create_departments_stub_table.php' => database_path('migrations/'.date('Y_m_d_His').'_create_departments_stub_table.php'),
        ], 'mca-permission-departments-stub');

        $this->publishes([
            __DIR__.'/../stubs/migrations/add_role_id_to_users_table.php' => database_path('migrations/'.date('Y_m_d_His').'_add_role_id_to_users_table.php'),
        ], 'mca-permission-users-migration');

        $this->publishes([
            __DIR__.'/../stubs/models/Department.php' => app_path('Models/Department.php'),
        ], 'mca-permission-departments-stub');
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];
        $router->aliasMiddleware(
            config('permission.middleware_alias', 'mca.permission'),
            CheckPermission::class,
        );
        $router->aliasMiddleware(
            config('permission.root_middleware_alias', 'mca.permission.root'),
            EnsureMcaRoot::class,
        );
        $router->aliasMiddleware('mca.permission.locale', SetMcaPermissionLocale::class);
        $router->aliasMiddleware(
            config('permission.package_middleware_alias', 'mca.package'),
            EnsureMcaPackageAccess::class,
        );
    }

    protected function registerRoutes(): void
    {
        if (! config('permission.routes.load_package_routes', true)) {
            return;
        }

        if (config('permission.ui.enabled', true) && config('permission.routes.web.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (config('permission.routes.api.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        }
    }

    protected function syncScanSegmentsFromConfig(): void
    {
        if (! config('permission.scan.sync_segments_on_boot', true)) {
            return;
        }

        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        try {
            $this->app->make(ScanSegmentService::class)->ensureConfigSegments();
        } catch (\Throwable) {
            // Migration henüz çalışmadıysa sessizce geç.
        }
    }

    protected function syncPackagePermissionsFromConfig(): void
    {
        if (! config('permission.packages_sync_on_boot', true)) {
            return;
        }

        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        try {
            $this->app->make(PackageAccessService::class)->syncDefinitions();
        } catch (\Throwable) {
            // Migration / tablo yoksa sessizce geç.
        }
    }
}
