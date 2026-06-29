<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Support;

use Denizaygundev\NotificationPreferences\Contracts\ManagesNotificationPreferences;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use ReflectionClass;
use Throwable;

/**
 * Maps a Notification (instance or class-string) to its stable type key, category and the
 * persisted {@see NotificationType} registry row.
 */
class NotificationTypeRegistry
{
    /**
     * Resolve the stable type key for a notification.
     *
     * Order: ManagesNotificationPreferences contract → config type map → fully-qualified
     * class name.
     */
    public function keyFor(object|string $notification): string
    {
        $instance = $this->toInstance($notification);

        if ($instance instanceof ManagesNotificationPreferences) {
            return $instance->notificationTypeKey();
        }

        $class = is_object($notification) ? $notification::class : $notification;

        $map = (array) config('notification-preferences.types', []);
        $key = array_search($class, $map, true);

        return $key !== false ? (string) $key : $class;
    }

    public function categoryFor(object|string $notification): NotificationCategory
    {
        $instance = $this->toInstance($notification);

        if ($instance instanceof ManagesNotificationPreferences) {
            return $instance->notificationCategory();
        }

        $default = (string) config(
            'notification-preferences.discovery.default_category',
            NotificationCategory::Transactional->value,
        );

        return NotificationCategory::tryFrom($default) ?? NotificationCategory::Transactional;
    }

    /**
     * The persisted registry row for a notification, or null if it has not been registered
     * (in which case enforcement allows the send — unknown types are never silently dropped).
     */
    public function resolve(object|string $notification): ?NotificationType
    {
        return NotificationType::query()
            ->where('key', $this->keyFor($notification))
            ->first();
    }

    /**
     * Return an instance to inspect the contract on, creating one without the constructor for
     * class-strings. Returns null when the class does not implement the contract.
     */
    private function toInstance(object|string $notification): ?object
    {
        if (is_object($notification)) {
            return $notification;
        }

        if (! class_exists($notification) || ! is_subclass_of($notification, ManagesNotificationPreferences::class)) {
            return null;
        }

        try {
            return (new ReflectionClass($notification))->newInstanceWithoutConstructor();
        } catch (Throwable) {
            return null;
        }
    }
}
