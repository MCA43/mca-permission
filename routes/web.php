<?php

use Illuminate\Support\Facades\Route;
use Mca\Permission\Support\PermissionMode;

$web = config('permission.routes.web', []);
$prefix = $web['prefix'] ?? 'mca/permission';
$middleware = $web['middleware'] ?? ['web', 'auth', 'mca.permission.root'];

if (! in_array('mca.permission.locale', $middleware, true)) {
    $afterWeb = array_search('web', $middleware, true);
    if ($afterWeb !== false) {
        array_splice($middleware, (int) $afterWeb + 1, 0, 'mca.permission.locale');
    } else {
        array_unshift($middleware, 'mca.permission.locale');
    }
}

$namePrefix = config('permission.routes.name_prefix', 'mca.permission.');
$controllers = config('permission.controllers.web', []);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name($namePrefix)
    ->group(function () use ($controllers) {
        Route::get('/', [$controllers['permission'], 'index'])->name('index');
        Route::get('/scanner', [$controllers['scanner'], 'index'])->name('scanner');

        Route::get('/roles', [$controllers['role'], 'index'])->name('roles.index');
        Route::post('/roles', [$controllers['role'], 'store'])->name('roles.store');
        Route::put('/roles/{role:slug}', [$controllers['role'], 'update'])->name('roles.update');
        Route::delete('/roles/{role:slug}', [$controllers['role'], 'destroy'])->name('roles.destroy');
        Route::get('/roles/{role:slug}/permissions', [$controllers['role_permission'], 'edit'])->name('roles.permissions.edit');
        Route::post('/roles/{role:slug}/permissions', [$controllers['role_permission'], 'update'])->name('roles.permissions.update');

        if (PermissionMode::supportsUserGrants()) {
            Route::get('/users', [$controllers['user'], 'index'])->name('users.index');
            Route::post('/users', [$controllers['user'], 'store'])->name('users.store');
            Route::put('/users/{user}', [$controllers['user'], 'update'])->name('users.update');
            Route::delete('/users/{user}', [$controllers['user'], 'destroy'])->name('users.destroy');
            Route::get('/users/{user}/permissions', [$controllers['user_permission'], 'edit'])->name('users.permissions.edit');
            Route::post('/users/{user}/permissions', [$controllers['user_permission'], 'update'])->name('users.permissions.update');
        }

        if (PermissionMode::supportsDepartmentGrants()) {
            Route::get('/departments', [$controllers['department'], 'index'])->name('departments.index');
            Route::post('/departments', [$controllers['department'], 'store'])->name('departments.store');
            Route::put('/departments/{department}', [$controllers['department'], 'update'])->name('departments.update');
            Route::delete('/departments/{department}', [$controllers['department'], 'destroy'])->name('departments.destroy');
            Route::get('/departments/{department}/permissions', [$controllers['department_permission'], 'edit'])->name('departments.permissions.edit');
            Route::post('/departments/{department}/permissions', [$controllers['department_permission'], 'update'])->name('departments.permissions.update');
        }
    });
