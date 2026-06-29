<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications;

use Denizaygundev\NotificationPreferences\Contracts\ManagesNotificationPreferences;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionalNotification extends Notification implements ManagesNotificationPreferences
{
    public function notificationTypeKey(): string
    {
        return 'order-receipt';
    }

    public function notificationCategory(): NotificationCategory
    {
        return NotificationCategory::Transactional;
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Receipt')->line('Your receipt.');
    }
}
