<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Concerns;

use DateTimeInterface;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Models\NotificationPause;
use Denizaygundev\NotificationPreferences\Models\NotificationPreference;
use Denizaygundev\NotificationPreferences\Models\NotificationPreferenceLog;
use Denizaygundev\NotificationPreferences\NotificationPreferencesManager;
use Denizaygundev\NotificationPreferences\PendingNotifiablePreferences;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Add to any Notifiable model to give it managed notification preferences.
 *
 * The presence of this trait is also how the enforcement listener recognises a notifiable as
 * "manageable"; notifiables without it always receive every notification.
 *
 * Method names are deliberately specific (e.g. subscribeToNotification) to avoid collisions on
 * host models. For the terse form use the facade: NotificationPreferences::for($model)->subscribe(...).
 */
trait HasNotificationPreferences
{
    /** @return MorphMany<NotificationPreference, $this> */
    public function notificationPreferences(): MorphMany
    {
        return $this->morphMany(NotificationPreference::class, 'notifiable');
    }

    /** @return MorphMany<NotificationPause, $this> */
    public function notificationPauses(): MorphMany
    {
        return $this->morphMany(NotificationPause::class, 'notifiable');
    }

    /** @return MorphMany<NotificationPreferenceLog, $this> */
    public function notificationPreferenceLogs(): MorphMany
    {
        return $this->morphMany(NotificationPreferenceLog::class, 'notifiable');
    }

    public function isSubscribedTo(object|string $type, string $channel): bool
    {
        return $this->notificationPreferenceManager()->isSubscribedTo($type, $channel);
    }

    public function hasActivePause(NotificationCategory|string|null $category = null): bool
    {
        return $this->notificationPreferenceManager()->hasActivePause($category);
    }

    public function subscribeToNotification(object|string $type, ?string $channel = null): PendingNotifiablePreferences
    {
        return $this->notificationPreferenceManager()->subscribe($type, $channel);
    }

    public function unsubscribeFromNotification(object|string $type, ?string $channel = null): PendingNotifiablePreferences
    {
        return $this->notificationPreferenceManager()->unsubscribe($type, $channel);
    }

    public function pauseNotifications(
        ?DateTimeInterface $until = null,
        NotificationCategory|string|null $category = null,
    ): PendingNotifiablePreferences {
        return $this->notificationPreferenceManager()->pause($until, $category);
    }

    public function resumeNotifications(NotificationCategory|string|null $category = null): PendingNotifiablePreferences
    {
        return $this->notificationPreferenceManager()->resume($category);
    }

    protected function notificationPreferenceManager(): PendingNotifiablePreferences
    {
        return app(NotificationPreferencesManager::class)->for($this);
    }
}
