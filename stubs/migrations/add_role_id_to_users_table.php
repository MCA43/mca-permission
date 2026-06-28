<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'role_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('password');
        });

        if (Schema::hasColumn('users', 'role')) {
            foreach (DB::table('users')->whereNotNull('role')->get(['id', 'role']) as $user) {
                $roleId = DB::table('roles')->where('slug', $user->role)->value('id');
                if ($roleId) {
                    DB::table('users')->where('id', $user->id)->update(['role_id' => $roleId]);
                }
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'role_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 64)->nullable()->after('password');
        });

        foreach (DB::table('users')->whereNotNull('role_id')->get(['id', 'role_id']) as $user) {
            $slug = DB::table('roles')->where('id', $user->role_id)->value('slug');
            if ($slug) {
                DB::table('users')->where('id', $user->id)->update(['role' => $slug]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
