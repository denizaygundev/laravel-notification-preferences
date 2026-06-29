<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications;

use Denizaygundev\NotificationPreferences\Contracts\ManagesNotificationPreferences;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MarketingNotification extends Notification implements ManagesNotificationPreferences
{
    public function notificationTypeKey(): string
    {
        return 'marketing-news';
    }

    public function notificationCategory(): NotificationCategory
    {
        return NotificationCategory::Marketing;
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('News')->line('Latest news.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => 'marketing'];
    }
}
