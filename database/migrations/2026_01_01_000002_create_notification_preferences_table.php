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
        $typesTable = config('notification-preferences.table_names.types', 'notification_types');

        Schema::create($this->table(), function (Blueprint $table) use ($scope, $typesTable): void {
            $table->id();

            // String morph columns support int, ULID and UUID notifiable keys. Hosts using
            // bigint keys on PostgreSQL may publish this migration and switch to morphs().
            $table->string('notifiable_type');
            $table->string('notifiable_id');

            $table->foreignId('notification_type_id')->constrained($typesTable)->cascadeOnDelete();
            $table->string('channel');
            $table->boolean('subscribed')->default(true);

            // Optional tenant/team scope. Null = global per notifiable.
            $table->string($scope)->nullable();

            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id'], 'np_pref_notifiable_idx');
            $table->unique(
                ['notifiable_type', 'notifiable_id', $scope, 'notification_type_id', 'channel'],
                'np_pref_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return config('notification-preferences.table_names.preferences', 'notification_preferences');
    }

    private function scopeColumn(): string
    {
        return config('notification-preferences.scope.column', 'scope_id');
    }
};
