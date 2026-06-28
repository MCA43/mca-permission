<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;

class PermissionScannerController extends McaPermissionController
{
    public function index(): View
    {
        return $this->view('permissions.scanner', [
            'apiScanUrl' => route(config('permission.routes.name_prefix').'api.scanner'),
            'apiBulkUrl' => route(config('permission.routes.name_prefix').'api.permissions.bulk'),
            'apiSyncLabelsUrl' => route(config('permission.routes.name_prefix').'api.permissions.sync-labels'),
            'apiSyncAllUrl' => route(config('permission.routes.name_prefix').'api.permissions.sync-all'),
            'apiSegmentsUrl' => route(config('permission.routes.name_prefix').'api.scan-segments.index'),
            'apiSegmentsStoreUrl' => route(config('permission.routes.name_prefix').'api.scan-segments.store'),
            'apiSegmentsSyncUrl' => route(config('permission.routes.name_prefix').'api.scan-segments.sync-config'),
        ]);
    }
}
