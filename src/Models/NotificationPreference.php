<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A user's explicit subscribe/unsubscribe decision for one (type × channel).
 *
 * Rows are sparse: one exists only when the user diverges from the type's default. Absence
 * means "use the default". Optionally scoped via the configured scope column.
 *
 * @property string $channel
 * @property bool $subscribed
 */
class NotificationPreference extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('notification-preferences.table_names.preferences', 'notification_preferences');
    }

    protected function casts(): array
    {
        return [
            'subscribed' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
