<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Facades\NotificationPreferences;
use Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications\MarketingNotification;

it('resolves channel defaults and stores preferences sparsely', function () {
    $type = marketingType(['default_channels' => ['mail']]);
    $user = makeUser();

    expect($user->isSubscribedTo($type, 'mail'))->toBeTrue()
        ->and($user->isSubscribedTo($type, 'database'))->toBeFalse()
        ->and($user->notificationPreferences()->count())->toBe(0);

    $user->subscribeToNotification($type, 'database');

    expect($user->isSubscribedTo($type, 'database'))->toBeTrue()
        ->and($user->notificationPreferences()->count())->toBe(1);
});

it('resolves a notification by its class through the facade', function () {
    $type = marketingType();
    $user = makeUser();

    NotificationPreferences::for($user)->unsubscribe(MarketingNotification::class, 'mail');

    expect($user->isSubscribedTo($type, 'mail'))->toBeFalse();
});

it('unsubscribes from every type in a category at once', function () {
    marketingType();
    marketingType(['key' => 'promos', 'name' => 'Promotions']);
    $user = makeUser();

    NotificationPreferences::for($user)->unsubscribeAll(NotificationCategory::Marketing);

    expect($user->isSubscribedTo('marketing-news', 'mail'))->toBeFalse()
        ->and($user->isSubscribedTo('promos', 'mail'))->toBeFalse();
});

it('cannot unsubscribe from a locked type', function () {
    $type = transactionalType();
    $user = makeUser();

    $user->unsubscribeFromNotification($type, 'mail');

    expect($user->isSubscribedTo($type, 'mail'))->toBeTrue()
        ->and($user->notificationPreferences()->count())->toBe(0);
});

it('builds a resolved matrix for the UI', function () {
    marketingType(['default_channels' => ['mail']]);
    $user = makeUser();

    $matrix = NotificationPreferences::for($user)->matrix();

    expect($matrix)->toHaveCount(1)
        ->and($matrix[0]['channels']['mail'])->toBeTrue()
        ->and($matrix[0]['channels']['database'])->toBeFalse();
});
