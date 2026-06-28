<?php

namespace Mca\Permission\Support;

final class PermissionLabels
{
    /** @return array<string, string> */
    public static function controllerMap(): array
    {
        return config('permission.labels.controllers', []);
    }

    /** @return array<string, string> */
    public static function methodMap(): array
    {
        return array_merge(self::defaultMethods(), config('permission.labels.methods', []));
    }

    public static function folderLabel(string $folder): string
    {
        $map = config('permission.labels.folders', []);

        return $map[$folder] ?? $folder;
    }

    public static function controllerDescription(string $folder, string $controller): string
    {
        $controller = self::normalizeController($controller);
        $map = self::controllerMap();

        if (isset($map[$controller])) {
            return $map[$controller];
        }

        $short = str_replace('Controller', '', $controller);
        $short = preg_replace('/Api$/', '', $short) ?? $short;

        return trim($short) !== '' ? $short : $controller;
    }

    public static function moduleLabel(string $folder, string $controller): string
    {
        $desc = self::controllerDescription($folder, $controller);
        $folderLabel = self::folderLabel($folder);

        if ($folder === 'Panel') {
            return $desc;
        }

        return $desc.' ('.$folderLabel.')';
    }

    public static function methodLabel(string $method): string
    {
        $map = self::methodMap();

        return $map[$method] ?? $method;
    }

    public static function normalizeController(string $controller): string
    {
        return str_ends_with($controller, 'Controller') ? $controller : $controller.'Controller';
    }

    /** @return array<string, string> */
    private static function defaultMethods(): array
    {
        return [
            'index' => 'Liste görüntüleme',
            'create' => 'Oluşturma sayfası',
            'store' => 'Kaydetme',
            'show' => 'Detay',
            'edit' => 'Düzenleme sayfası',
            'update' => 'Güncelleme',
            'destroy' => 'Silme',
            'scan' => 'İzin tarama',
            'bulk' => 'Toplu ekleme',
            'syncLabels' => 'Etiket senkronu',
            '*' => 'Tüm metodlar',
        ];
    }
}
