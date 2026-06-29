<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Filament\Pages;

use BackedEnum;
use Denizaygundev\NotificationPreferences\Concerns\HasNotificationPreferences;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Facades\NotificationPreferences;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;

/**
 * User-facing preference centre: the rows-×-channels subscription matrix, per-category bulk
 * unsubscribe, and pause-all controls. The panel user must use the
 * {@see HasNotificationPreferences} trait.
 */
class ManageNotificationPreferences extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notification preferences';

    protected static ?string $title = 'Notification preferences';

    protected string $view = 'notification-preferences::pages.manage-preferences';

    public function toggleChannel(int $typeId, string $channel): void
    {
        $notifiable = $this->notifiable();
        $type = NotificationType::query()->whereKey($typeId)->first();

        if ($notifiable === null || $type === null) {
            return;
        }

        $preferences = NotificationPreferences::for($notifiable);

        if ($preferences->isSubscribedTo($type, $channel)) {
            $preferences->unsubscribe($type, $channel);
        } else {
            $preferences->subscribe($type, $channel);
        }

        $this->notifySaved();
    }

    public function unsubscribeCategory(string $category): void
    {
        $notifiable = $this->notifiable();
        $enum = NotificationCategory::tryFrom($category);

        if ($notifiable === null || $enum === null) {
            return;
        }

        NotificationPreferences::for($notifiable)->unsubscribeAll($enum);

        $this->notifySaved();
    }

    public function pauseFor(?int $days = null): void
    {
        $notifiable = $this->notifiable();

        if ($notifiable === null) {
            return;
        }

        NotificationPreferences::for($notifiable)->pause($days !== null ? now()->addDays($days) : null);

        $this->notifySaved();
    }

    public function resumePaused(): void
    {
        $notifiable = $this->notifiable();

        if ($notifiable === null) {
            return;
        }

        NotificationPreferences::for($notifiable)->resume();

        $this->notifySaved();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $notifiable = $this->notifiable();
        $manager = $notifiable !== null ? NotificationPreferences::for($notifiable) : null;

        return [
            'matrix' => $manager?->matrix() ?? [],
            'channels' => (array) config('notification-preferences.channels', []),
            'pausePresets' => (array) config('notification-preferences.pause_presets', []),
            'activePause' => $manager?->activePause(),
            'timezone' => $this->displayTimezone(),
        ];
    }

    /**
     * Timezone used to render dates in the UI. Configurable via the `timezone` config key
     * (string or closure); falls back to the application timezone.
     */
    protected function displayTimezone(): string
    {
        $timezone = config('notification-preferences.timezone');

        if (is_callable($timezone)) {
            $timezone = $timezone();
        }

        return is_string($timezone) && $timezone !== ''
            ? $timezone
            : (string) config('app.timezone', 'UTC');
    }

    protected function notifiable(): ?Model
    {
        $user = Filament::auth()->user();

        return $user instanceof Model ? $user : null;
    }

    protected function notifySaved(): void
    {
        Notification::make()
            ->title(__('notification-preferences::notification-preferences.saved'))
            ->success()
            ->send();
    }
}
