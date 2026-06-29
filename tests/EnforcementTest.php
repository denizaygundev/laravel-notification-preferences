<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications\MarketingNotification;
use Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications\TransactionalNotification;
use Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications\UnknownNotification;
use Illuminate\Notifications\AnonymousNotifiable;

it('delivers by default when the user has expressed no preference', function () {
    marketingType();

    expect(enforce(makeUser(), new MarketingNotification, 'mail'))->toBeTrue();
});

it('suppresses only the unsubscribed channel', function () {
    marketingType();
    $user = makeUser();

    $user->unsubscribeFromNotification(MarketingNotification::class, 'mail');

    expect(enforce($user, new MarketingNotification, 'mail'))->toBeFalse()
        ->and(enforce($user, new MarketingNotification, 'database'))->toBeTrue();
});

it('always delivers locked transactional types, ignoring unsubscribe attempts', function () {
    transactionalType();
    $user = makeUser();

    $user->unsubscribeFromNotification(TransactionalNotification::class, 'mail');

    expect(enforce($user, new TransactionalNotification, 'mail'))->toBeTrue();
});

it('passes through notifications that are not in the registry', function () {
    expect(enforce(makeUser(), new UnknownNotification, 'mail'))->toBeTrue();
});

it('passes through anonymous (on-demand) notifiables', function () {
    marketingType();

    expect(enforce(new AnonymousNotifiable, new MarketingNotification, 'mail'))->toBeTrue();
});

it('suppresses subscribable types while paused but never locked ones', function () {
    marketingType();
    transactionalType();
    $user = makeUser();

    $user->pauseNotifications(now()->addWeek());

    expect(enforce($user, new MarketingNotification, 'mail'))->toBeFalse()
        ->and(enforce($user, new TransactionalNotification, 'mail'))->toBeTrue();
});

it('resumes delivery after a pause is lifted', function () {
    marketingType();
    $user = makeUser();

    $user->pauseNotifications();
    expect(enforce($user, new MarketingNotification, 'mail'))->toBeFalse();

    $user->resumeNotifications();
    expect(enforce($user, new MarketingNotification, 'mail'))->toBeTrue();
});

it('is wired into the real notification pipeline for the database channel', function () {
    marketingType();
    $user = makeUser();

    $user->notify(new MarketingNotification);
    expect($user->notifications()->count())->toBe(1);

    $user->unsubscribeFromNotification(MarketingNotification::class, 'database');
    $user->notify(new MarketingNotification);

    // Second send is suppressed on the database channel, so the count does not grow.
    expect($user->notifications()->count())->toBe(1);
});

it('honours the enforcement master switch', function () {
    config(['notification-preferences.enforcement.enabled' => false]);
    marketingType();
    $user = makeUser();

    $user->unsubscribeFromNotification(MarketingNotification::class, 'mail');

    expect(enforce($user, new MarketingNotification, 'mail'))->toBeTrue();
});
