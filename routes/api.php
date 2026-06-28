<?php

use Illuminate\Support\Facades\Route;

$api = config('permission.routes.api', []);
$prefix = $api['prefix'] ?? 'mca/permission/api';
$middleware = $api['middleware'] ?? ['web', 'auth', 'mca.permission.root'];
$namePrefix = config('permission.routes.name_prefix', 'mca.permission.').'api.';
$controllers = config('permission.controllers.api', []);

Route::prefix($prefix)
    ->middleware($middleware)
    ->name($namePrefix)
    ->group(function () use ($controllers) {
        $ctrl = $controllers['permission'];

        Route::get('/scanner', [$ctrl, 'scan'])->name('scanner');
        Route::post('/permissions/bulk', [$ctrl, 'storeBulk'])->name('permissions.bulk');
        Route::post('/permissions/sync-labels', [$ctrl, 'syncLabels'])->name('permissions.sync-labels');
        Route::post('/permissions/sync-all', [$ctrl, 'syncAll'])->name('permissions.sync-all');
        Route::get('/permissions', [$ctrl, 'indexPermissions'])->name('permissions.index');
        Route::get('/roles', [$ctrl, 'indexRoles'])->name('roles.index');
        Route::put('/roles/{role}/permissions', [$ctrl, 'updateRolePermissions'])->name('roles.permissions.update');

        Route::get('/scan-segments', [$ctrl, 'indexScanSegments'])->name('scan-segments.index');
        Route::post('/scan-segments', [$ctrl, 'storeScanSegment'])->name('scan-segments.store');
        Route::put('/scan-segments/{segment}', [$ctrl, 'updateScanSegment'])->name('scan-segments.update');
        Route::delete('/scan-segments/{segment}', [$ctrl, 'destroyScanSegment'])->name('scan-segments.destroy');
        Route::post('/scan-segments/sync-config', [$ctrl, 'syncScanSegmentsFromConfig'])->name('scan-segments.sync-config');
    });
