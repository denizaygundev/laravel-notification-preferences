<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $scope = $this->scopeColumn();

        Schema::create($this->table(), function (Blueprint $table) use ($scope): void {
            $table->id();

            // Who made the change (admin, the user themselves, or null for system/one-click).
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();

            $table->string('notifiable_type');
            $table->string('notifiable_id');

            // Snapshot of the type/channel touched (no FK — logs outlive type deletion).
            $table->unsignedBigInteger('notification_type_id')->nullable();
            $table->string('notification_type_key')->nullable();
            $table->string('channel')->nullable();

            // subscribe | unsubscribe | pause | resume
            $table->string('action');
            // Structured before/after payload.
            $table->json('properties')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->string($scope)->nullable();

            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id'], 'np_log_notifiable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return config('notification-preferences.table_names.logs', 'notification_preference_logs');
    }

    private function scopeColumn(): string
    {
        return config('notification-preferences.scope.column', 'scope_id');
    }
};
