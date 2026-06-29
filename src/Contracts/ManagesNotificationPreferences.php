<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Contracts;

use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;

/**
 * Opt-in contract a Notification may implement to declare how it is managed.
 *
 * Implementing this is optional — types can also be mapped in config, or fall back to their
 * fully-qualified class name as the key with the configured default category.
 *
 * IMPORTANT: both methods must be deterministic and independent of constructor state. The
 * package may resolve them from an instance created without invoking the constructor (e.g.
 * during `notification-preferences:sync`), so they must not rely on injected payload.
 */
interface ManagesNotificationPreferences
{
    /** Stable, unique identifier for this notification type (e.g. "order-shipped"). */
    public function notificationTypeKey(): string;

    /** Classification that drives the default of whether users may unsubscribe. */
    public function notificationCategory(): NotificationCategory;
}
