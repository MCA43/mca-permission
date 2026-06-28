<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_permission') || ! Schema::hasTable('roles')) {
            return;
        }

        if (Schema::hasColumn('role_permission', 'role_id')) {
            return;
        }

        if (! Schema::hasColumn('role_permission', 'role')) {
            return;
        }

        Schema::table('role_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('id');
        });

        foreach (DB::table('role_permission')->get() as $row) {
            $roleId = DB::table('roles')->where('slug', $row->role)->value('id');
            if ($roleId) {
                DB::table('role_permission')->where('id', $row->id)->update(['role_id' => $roleId]);
            }
        }

        Schema::table('role_permission', function (Blueprint $table) {
            $table->dropUnique(['role', 'permission_id']);
            $table->dropColumn('role');
        });

        Schema::table('role_permission', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_permission') || ! Schema::hasColumn('role_permission', 'role_id')) {
            return;
        }

        Schema::table('role_permission', function (Blueprint $table) {
            $table->string('role', 64)->nullable()->after('id');
        });

        foreach (DB::table('role_permission')->get() as $row) {
            $slug = DB::table('roles')->where('id', $row->role_id)->value('slug');
            if ($slug) {
                DB::table('role_permission')->where('id', $row->id)->update(['role' => $slug]);
            }
        }

        Schema::table('role_permission', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropUnique(['role_id', 'permission_id']);
            $table->dropColumn('role_id');
            $table->unique(['role', 'permission_id']);
        });
    }
};
