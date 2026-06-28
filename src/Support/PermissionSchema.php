<?php

namespace Mca\Permission\Support;

use Illuminate\Support\Facades\Schema;

final class PermissionSchema
{
    public static function hasUserPermissionTable(): bool
    {
        return Schema::hasTable('user_permission');
    }

    public static function hasDepartmentPermissionTable(): bool
    {
        return Schema::hasTable('department_permission');
    }

    public static function departmentTableExists(): bool
    {
        $table = (string) config('permission.department.table', 'departments');

        return Schema::hasTable($table);
    }

    /** @return list<string> */
    public static function requiredTablesForMode(string $mode): array
    {
        $tables = ['permissions', 'roles', 'role_permission'];

        if (in_array($mode, [PermissionMode::USER, PermissionMode::FULL], true)) {
            $tables[] = 'user_permission';
        }

        if ($mode === PermissionMode::FULL) {
            $tables[] = 'department_permission';
        }

        return $tables;
    }

    /** @return list<string> */
    public static function missingTablesForMode(string $mode): array
    {
        return array_values(array_filter(
            self::requiredTablesForMode($mode),
            fn (string $table) => ! Schema::hasTable($table),
        ));
    }

    /** @return list<string> */
    public static function missingExclusiveSetupForMode(string $mode): array
    {
        $issues = [];

        if (in_array($mode, [PermissionMode::USER, PermissionMode::FULL], true)) {
            if (! PermissionGrantContext::userHasExclusiveColumn()) {
                $issues[] = 'users.'.PermissionGrantContext::userExclusiveColumn();
            }
        }

        if ($mode === PermissionMode::FULL && self::departmentTableExists()) {
            if (! PermissionGrantContext::departmentHasExclusiveColumn()) {
                $issues[] = 'departments.'.PermissionGrantContext::departmentExclusiveColumn();
            }
        }

        return $issues;
    }
}
