<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Models;

use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The admin-managed registry of notification types.
 *
 * One row per notification a host app can send. Types are app-global (not scoped) — the
 * same Notification class means the same thing in every tenant. Whether a user may opt out
 * is governed by {@see self::$attributes['is_subscribable']}; locked types are always sent.
 *
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property NotificationCategory $category
 * @property bool $is_subscribable
 * @property array<int, string>|null $available_channels
 * @property array<int, string>|null $default_channels
 * @property int $sort_order
 * @property bool $is_active
 */
class NotificationType extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'is_subscribable',
        'available_channels',
        'default_channels',
        'sort_order',
        'is_active',
    ];

    public function getTable(): string
    {
        return config('notification-preferences.table_names.types', 'notification_types');
    }

    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'available_channels' => 'array',
            'default_channels' => 'array',
            'is_subscribable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'notification_type_id');
    }

    /**
     * Channels this type may be delivered on. Falls back to every configured channel.
     *
     * @return array<int, string>
     */
    public function availableChannels(): array
    {
        return $this->available_channels ?: array_keys((array) config('notification-preferences.channels', []));
    }

    /**
     * Channels that are ON by default for a user who has expressed no preference.
     * Falls back to all available channels.
     *
     * @return array<int, string>
     */
    public function defaultChannels(): array
    {
        return $this->default_channels ?? $this->availableChannels();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeSubscribable(Builder $query): void
    {
        $query->where('is_subscribable', true);
    }
}
