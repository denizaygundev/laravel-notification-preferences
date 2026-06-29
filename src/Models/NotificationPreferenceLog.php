<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Append-only consent/audit record of a preference change.
 *
 * @property string $action subscribe|unsubscribe|pause|resume
 * @property array<string, mixed>|null $properties before/after payload
 */
class NotificationPreferenceLog extends Model
{
    // Written only by package internals (the auditor), never from request input, and the scope
    // column name is configurable — so the table is intentionally left fully fillable.
    protected $guarded = [];

    public function getTable(): string
    {
        return config('notification-preferences.table_names.logs', 'notification_preference_logs');
    }

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
