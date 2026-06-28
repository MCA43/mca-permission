<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_scan_segments', function (Blueprint $table) {
            $table->id();
            $table->string('folder', 128);
            $table->string('path', 255);
            $table->string('namespace', 255);
            $table->boolean('is_active')->default(true);
            $table->boolean('from_config')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['path', 'namespace']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_scan_segments');
    }
};
