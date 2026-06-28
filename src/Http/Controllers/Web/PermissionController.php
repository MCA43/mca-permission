<?php

namespace Mca\Permission\Http\Controllers\Web;

use Illuminate\View\View;
use Mca\Permission\Http\Controllers\McaPermissionController;
use Mca\Permission\Models\Permission;

class PermissionController extends McaPermissionController
{
    public function index(): View
    {
        return $this->view('permissions.index', [
            'permissions' => Permission::query()
                ->orderBy('module')
                ->orderBy('method')
                ->get(),
        ]);
    }
}
