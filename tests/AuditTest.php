<?php

declare(strict_types=1);

it('logs preference changes to the audit table', function () {
    $type = marketingType();
    $user = makeUser();

    $user->unsubscribeFromNotification($type, 'mail');

    $log = $user->notificationPreferenceLogs()->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('unsubscribe')
        ->and($log->channel)->toBe('mail')
        ->and($log->notification_type_key)->toBe('marketing-news');
});

it('logs pause and resume actions', function () {
    marketingType();
    $user = makeUser();

    $user->pauseNotifications(now()->addMonth());
    $user->resumeNotifications();

    expect($user->notificationPreferenceLogs()->where('action', 'pause')->count())->toBe(1)
        ->and($user->notificationPreferenceLogs()->where('action', 'resume')->count())->toBe(1);
});

it('writes nothing when the audit driver is none', function () {
    config(['notification-preferences.audit.driver' => 'none']);
    $type = marketingType();
    $user = makeUser();

    $user->unsubscribeFromNotification($type, 'mail');

    expect($user->notificationPreferenceLogs()->count())->toBe(0);
});

it('does not log a no-op change', function () {
    $type = marketingType(['default_channels' => ['mail']]);
    $user = makeUser();

    // Already subscribed by default; subscribing again should not create a log row.
    $user->subscribeToNotification($type, 'mail');

    expect($user->notificationPreferenceLogs()->count())->toBe(0);
});
