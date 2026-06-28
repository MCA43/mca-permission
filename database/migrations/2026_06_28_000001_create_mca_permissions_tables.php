<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_root_only')->default(false);
            $table->string('folder', 64);
            $table->string('controller', 128);
            $table->string('module', 128);
            $table->string('module_description')->nullable();
            $table->string('method', 64);
            $table->string('method_description')->nullable();
            $table->timestamps();

            $table->index(['folder', 'controller', 'method']);
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->string('role', 64);
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
    }
};
