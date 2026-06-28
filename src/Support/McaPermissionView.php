<?php

namespace Mca\Permission\Support;

use Illuminate\Contracts\View\View;

final class McaPermissionView
{
    public static function layout(): string
    {
        return (string) config('permission.views.layout', 'mca-permission::layouts.app');
    }

    public static function render(string $view, array $data = []): View
    {
        McaPermissionLocale::apply();

        $namespace = config('permission.views.namespace', 'mca-permission');

        return view($namespace.'::'.$view, array_merge([
            'mcaPermPrefix' => config('permission.ui.class_prefix', 'mca-perm'),
            'mcaPermTitle' => config('permission.ui.title') ?: mca_perm('app.title'),
        ], $data));
    }

    public static function uiCssUrl(): string
    {
        $path = config('permission.ui.assets.ui', 'vendor/mca-permission/mca-ui.css');

        return asset($path);
    }

    public static function uiJsUrl(): string
    {
        $path = config('permission.ui.assets.ui_js', 'vendor/mca-permission/mca-ui.js');

        return asset($path);
    }

    public static function cssUrl(): string
    {
        $path = config('permission.ui.assets.css', 'vendor/mca-permission/mca-permission.css');

        return asset($path);
    }

    public static function jsUrl(): string
    {
        $path = config('permission.ui.assets.js', 'vendor/mca-permission/mca-permission.js');

        return asset($path);
    }
}
