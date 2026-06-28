<?php

namespace Mca\Permission\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mca\Permission\PermissionServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [PermissionServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('permission.mode', 'full');
        $app['config']->set('permission.locale', 'en');
        $app['config']->set('permission.user_model', Models\User::class);
        $app['config']->set('permission.department.model', null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        \Mca\Permission\Support\McaPermissionLocale::apply();
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('role', 64)->default('editor');
            $table->boolean('mca_permission_exclusive')->default(false);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->timestamps();
        });

        $base = __DIR__.'/../database/migrations';
        $this->loadMigrationsFrom($base);
        $this->loadMigrationsFrom($base.'/modes/user');
        $this->loadMigrationsFrom($base.'/modes/full');
    }
}
