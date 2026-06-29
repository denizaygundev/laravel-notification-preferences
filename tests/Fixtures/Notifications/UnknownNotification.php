<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A notification that is NOT registered in the type registry and does not implement the
 * contract — used to assert the safe-by-default pass-through behaviour.
 */
class UnknownNotification extends Notification
{
    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Hello')->line('Hello.');
    }
}
