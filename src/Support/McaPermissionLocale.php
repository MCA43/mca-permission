<?php

namespace Mca\Permission\Support;

final class McaPermissionLocale
{
    public static function resolve(): string
    {
        $locale = config('permission.locale');

        if (is_string($locale) && $locale !== '') {
            return $locale;
        }

        return (string) app()->getLocale();
    }

    public static function apply(): void
    {
        app()->setLocale(self::resolve());
    }
}
