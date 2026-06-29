<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * A "pause everything" (optionally per-category) request for a notifiable.
 *
 * @property Carbon|null $paused_until null = paused indefinitely
 * @property string|null $category null = pause all subscribable types
 */
class NotificationPause extends Model
{
    // Written only by package internals (the manager), never from request input, and the scope
    // column name is configurable — so the table is intentionally left fully fillable.
    protected $guarded = [];

    public function getTable(): string
    {
        return config('notification-preferences.table_names.pauses', 'notification_pauses');
    }

    protected function casts(): array
    {
        return [
            'paused_until' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->paused_until === null || $this->paused_until->isFuture();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereNull('paused_until')->orWhere('paused_until', '>', now());
        });
    }
}
