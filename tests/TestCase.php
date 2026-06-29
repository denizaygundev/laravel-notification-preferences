<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Tests;

use Denizaygundev\NotificationPreferences\NotificationPreferencesServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NotificationPreferencesServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Capture mail in-memory so we can assert on the rendered Symfony message (headers).
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);
    }

    protected function defineDatabaseMigrations(): void
    {
        // A throwaway notifiable table so we can attach the HasNotificationPreferences trait in tests.
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // Laravel's database-notifications table, for exercising the "database" channel end-to-end.
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        // Package migrations.
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
