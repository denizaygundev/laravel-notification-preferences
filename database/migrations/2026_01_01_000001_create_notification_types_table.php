<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->index();
            // Whether users may unsubscribe from this type. false = locked / always delivered.
            $table->boolean('is_subscribable')->default(true);
            // Channels this type may use, and which are on by default for a new user.
            $table->json('available_channels')->nullable();
            $table->json('default_channels')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        return config('notification-preferences.table_names.types', 'notification_types');
    }
};
