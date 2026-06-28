<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $column = config('permission.user.exclusive_column', 'mca_permission_exclusive');

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', $column)) {
            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->boolean($column)->default(false);
            });
        }
    }

    public function down(): void
    {
        $column = config('permission.user.exclusive_column', 'mca_permission_exclusive');

        if (Schema::hasTable('users') && Schema::hasColumn('users', $column)) {
            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
