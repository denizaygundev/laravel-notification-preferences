<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Listeners;

use Denizaygundev\NotificationPreferences\Concerns\HasNotificationPreferences;
use Denizaygundev\NotificationPreferences\NotificationPreferencesManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSending;

/**
 * Suppresses a single (notifiable, type, channel) delivery at send time when the user has
 * unsubscribed, or while a pause is active. Returning false from this listener stops the send
 * on that channel only.
 *
 * Fires for both queued and synchronous notifications (it runs as the message is dispatched on
 * the worker), and is safe-by-default: anonymous notifiables, notifiables without the
 * {@see HasNotificationPreferences} trait, unknown types and locked types all pass through.
 */
class EnforceNotificationPreferences
{
    public function __construct(private NotificationPreferencesManager $manager) {}

    public function handle(NotificationSending $event): bool
    {
        if (! config('notification-preferences.enforcement.enabled', true)) {
            return true;
        }

        $notifiable = $event->notifiable;

        // Only manageable notifiables are subject to preferences; everything else is delivered.
        if (! $notifiable instanceof Model) {
            return true;
        }

        if (! in_array(HasNotificationPreferences::class, class_uses_recursive($notifiable), true)) {
            return true;
        }

        $type = $this->manager->registry()->resolve($event->notification);

        // Unknown (unregistered) or locked types are always delivered.
        if ($type === null || ! $type->is_subscribable) {
            return true;
        }

        $preferences = $this->manager->for($notifiable);

        if ($preferences->hasActivePause($type->category)) {
            return false;
        }

        return $preferences->isSubscribedTo($type, $event->channel);
    }
}
