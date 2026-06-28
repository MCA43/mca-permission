<?php

namespace Mca\Permission\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;

final class PermissionGrantContext
{
    public static function userExclusiveColumn(): string
    {
        return (string) config('permission.user.exclusive_column', 'mca_permission_exclusive');
    }

    public static function departmentExclusiveColumn(): string
    {
        return (string) config('permission.department.exclusive_column', 'mca_permission_exclusive');
    }

    public static function userHasExclusiveColumn(): bool
    {
        $userModel = config('permission.user_model');
        if (! is_string($userModel) || ! class_exists($userModel)) {
            return false;
        }

        $table = (new $userModel)->getTable();

        return Schema::hasColumn($table, self::userExclusiveColumn());
    }

    public static function departmentHasExclusiveColumn(): bool
    {
        $table = (string) config('permission.department.table', 'departments');

        return Schema::hasTable($table) && Schema::hasColumn($table, self::departmentExclusiveColumn());
    }

    public static function isUserExclusive(Authenticatable $user): bool
    {
        if (! self::userHasExclusiveColumn()) {
            return false;
        }

        $column = self::userExclusiveColumn();

        return (bool) ($user->{$column} ?? false);
    }

    public static function isDepartmentExclusive(?object $department): bool
    {
        if ($department === null || ! self::departmentHasExclusiveColumn()) {
            return false;
        }

        $column = self::departmentExclusiveColumn();

        return (bool) ($department->{$column} ?? false);
    }

    public static function userDepartmentId(Authenticatable $user): int|string|null
    {
        if (! PermissionMode::supportsDepartmentGrants()) {
            return null;
        }

        $column = (string) config('permission.department.user_column', 'department_id');
        if (! isset($user->{$column}) || $user->{$column} === null) {
            return null;
        }

        return $user->{$column};
    }
}
