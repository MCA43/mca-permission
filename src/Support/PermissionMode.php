<?php

namespace Mca\Permission\Support;

final class PermissionMode
{
    public const BASIC = 'basic';

    public const USER = 'user';

    public const FULL = 'full';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::BASIC, self::USER, self::FULL];
    }

    public static function current(): string
    {
        $mode = (string) config('permission.mode', self::BASIC);

        return in_array($mode, self::all(), true) ? $mode : self::BASIC;
    }

    public static function supportsUserGrants(): bool
    {
        return in_array(self::current(), [self::USER, self::FULL], true);
    }

    public static function supportsDepartmentGrants(): bool
    {
        return self::current() === self::FULL;
    }

    public static function label(string $mode): string
    {
        return match ($mode) {
            self::USER => mca_perm('modes.user'),
            self::FULL => mca_perm('modes.full'),
            default => mca_perm('modes.basic'),
        };
    }

    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::all(), true);
    }
}
