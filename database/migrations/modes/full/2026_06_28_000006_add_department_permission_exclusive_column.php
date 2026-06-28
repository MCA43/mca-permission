<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = config('permission.department.table', 'departments');
        $column = config('permission.department.exclusive_column', 'mca_permission_exclusive');

        if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, $column)) {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->boolean($column)->default(false);
            });
        }
    }

    public function down(): void
    {
        $tableName = config('permission.department.table', 'departments');
        $column = config('permission.department.exclusive_column', 'mca_permission_exclusive');

        if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, $column)) {
            Schema::table($tableName, function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
