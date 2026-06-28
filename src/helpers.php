<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Mca\Permission\Services\PermissionService;

if (! function_exists('mca_can')) {
    function mca_can(?Authenticatable $user, string $folder, string $controller, ?string $method = null): bool
    {
        return app(PermissionService::class)->canAccess($user, $folder, $controller, $method);
    }
}

if (! function_exists('mca_is_root')) {
    function mca_is_root(?Authenticatable $user): bool
    {
        return app(PermissionService::class)->isRoot($user);
    }
}

if (! function_exists('mca_perm')) {
    /** @param  array<string, string|int>  $replace */
    function mca_perm(string $key, array $replace = []): string
    {
        return (string) __('mca-permission::permission.'.$key, $replace);
    }
}
