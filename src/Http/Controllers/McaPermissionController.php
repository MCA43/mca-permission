<?php

namespace Mca\Permission\Http\Controllers;

use Mca\Permission\Support\McaPermissionView;

abstract class McaPermissionController
{
    protected function view(string $name, array $data = [])
    {
        return McaPermissionView::render($name, $data);
    }
}
