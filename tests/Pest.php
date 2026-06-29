<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Listeners\EnforceNotificationPreferences;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Denizaygundev\NotificationPreferences\Tests\Fixtures\User;
use Denizaygundev\NotificationPreferences\Tests\TestCase;
use Illuminate\Notifications\Events\NotificationSending;

uses(TestCase::class)->in(__DIR__);

function makeUser(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ], $attributes));
}

function marketingType(array $overrides = []): NotificationType
{
    return NotificationType::query()->create(array_merge([
        'key' => 'marketing-news',
        'name' => 'Marketing news',
        'category' => NotificationCategory::Marketing,
        'is_subscribable' => true,
        'available_channels' => ['mail', 'database'],
        'default_channels' => ['mail', 'database'],
        'is_active' => true,
    ], $overrides));
}

function transactionalType(array $overrides = []): NotificationType
{
    return NotificationType::query()->create(array_merge([
        'key' => 'order-receipt',
        'name' => 'Order receipt',
        'category' => NotificationCategory::Transactional,
        'is_subscribable' => false,
        'available_channels' => ['mail'],
        'default_channels' => ['mail'],
        'is_active' => true,
    ], $overrides));
}

/** Run the enforcement listener for a (notifiable, notification, channel) and return its verdict. */
function enforce(mixed $notifiable, object $notification, string $channel): bool
{
    return app(EnforceNotificationPreferences::class)
        ->handle(new NotificationSending($notifiable, $notification, $channel));
}
