<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Support;

use Denizaygundev\NotificationPreferences\Models\NotificationPreferenceLog;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes consent/audit records for preference changes, to the package table and/or
 * spatie/laravel-activitylog depending on the configured driver.
 */
class PreferenceAuditor
{
    public function __construct(private ScopeResolver $scope) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(
        Model $notifiable,
        string $action,
        array $properties = [],
        ?NotificationType $type = null,
        ?string $channel = null,
    ): void {
        $driver = (string) config('notification-preferences.audit.driver', 'table');

        if ($driver === 'none') {
            return;
        }

        if (in_array($driver, ['table', 'both'], true)) {
            $this->toTable($notifiable, $action, $properties, $type, $channel);
        }

        if (in_array($driver, ['activitylog', 'both'], true)) {
            $this->toActivityLog($notifiable, $action, $properties, $type, $channel);
        }
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function toTable(
        Model $notifiable,
        string $action,
        array $properties,
        ?NotificationType $type,
        ?string $channel,
    ): void {
        $actor = $this->actor();

        NotificationPreferenceLog::query()->create([
            'actor_type' => $actor?->getMorphClass(),
            'actor_id' => $actor?->getKey(),
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'notification_type_id' => $type?->getKey(),
            'notification_type_key' => $type?->key,
            'channel' => $channel,
            'action' => $action,
            'properties' => $properties ?: null,
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            $this->scope->column() => $this->scope->current(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function toActivityLog(
        Model $notifiable,
        string $action,
        array $properties,
        ?NotificationType $type,
        ?string $channel,
    ): void {
        if (! function_exists('activity')) {
            return;
        }

        activity((string) config('notification-preferences.audit.log_name', 'notification-preferences'))
            ->performedOn($notifiable)
            ->causedBy($this->actor())
            ->withProperties($properties + array_filter([
                'channel' => $channel,
                'type' => $type?->key,
                'scope' => $this->scope->current(),
            ], static fn ($value): bool => $value !== null))
            ->event($action)
            ->log($action);
    }

    private function actor(): ?Model
    {
        $user = auth()->user();

        return $user instanceof Model ? $user : null;
    }

    private function ip(): ?string
    {
        return app()->bound('request') ? request()->ip() : null;
    }

    private function userAgent(): ?string
    {
        return app()->bound('request') ? request()->userAgent() : null;
    }
}
