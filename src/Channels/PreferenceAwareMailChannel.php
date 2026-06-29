<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Channels;

use Denizaygundev\NotificationPreferences\Concerns\HasNotificationPreferences;
use Denizaygundev\NotificationPreferences\Support\NotificationTypeRegistry;
use Denizaygundev\NotificationPreferences\Support\UnsubscribeLinkGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Message;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Drop-in replacement for Laravel's mail notification channel that adds a one-click
 * List-Unsubscribe header (RFC 8058) to subscribable notifications. Behaves identically to the
 * parent for everything else.
 */
class PreferenceAwareMailChannel extends MailChannel
{
    /**
     * @param  Message  $mailMessage
     * @param  mixed  $notifiable
     * @param  Notification  $notification
     * @param  MailMessage  $message
     */
    protected function buildMessage($mailMessage, $notifiable, $notification, $message)
    {
        parent::buildMessage($mailMessage, $notifiable, $notification, $message);

        $this->addListUnsubscribeHeaders($mailMessage, $notifiable, $notification);
    }

    private function addListUnsubscribeHeaders(Message $mailMessage, mixed $notifiable, Notification $notification): void
    {
        if (! $notifiable instanceof Model) {
            return;
        }

        if (! in_array(HasNotificationPreferences::class, class_uses_recursive($notifiable), true)) {
            return;
        }

        $type = app(NotificationTypeRegistry::class)->resolve($notification);

        if ($type === null || ! $type->is_subscribable) {
            return;
        }

        $url = app(UnsubscribeLinkGenerator::class)->url($notifiable, $type->key, 'mail');

        $headers = $mailMessage->getSymfonyMessage()->getHeaders();
        $headers->addTextHeader('List-Unsubscribe', '<'.$url.'>');
        $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
    }
}
