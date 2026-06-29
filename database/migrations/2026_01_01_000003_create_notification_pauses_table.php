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

            $table->string('notifiable_type');
            $table->string('notifiable_id');

            // Null paused_until = paused indefinitely. Null category = pause all subscribable types.
            $table->timestamp('paused_until')->nullable();
            $table->string('category')->nullable();

            $table->string($scope)->nullable();

            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id'], 'np_pause_notifiable_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return config('notification-preferences.table_names.pauses', 'notification_pauses');
    }

    private function scopeColumn(): string
    {
        return config('notification-preferences.scope.column', 'scope_id');
    }
};
