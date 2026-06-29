<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Support\UnsubscribeLinkGenerator;
use Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications\MarketingNotification;
use Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications\TransactionalNotification;

it('unsubscribes via a one-click POST and records consent', function () {
    $type = marketingType();
    $user = makeUser();

    $token = app(UnsubscribeLinkGenerator::class)->token($user, 'marketing-news', 'mail');

    $this->post(route('notification-preferences.unsubscribe.process', ['token' => $token]))
        ->assertOk();

    expect($user->isSubscribedTo($type, 'mail'))->toBeFalse()
        ->and($user->notificationPreferenceLogs()->where('action', 'unsubscribe')->count())->toBe(1);
});

it('shows a confirmation page for a valid token', function () {
    marketingType();
    $user = makeUser();
    $token = app(UnsubscribeLinkGenerator::class)->token($user, 'marketing-news', 'mail');

    $this->get(route('notification-preferences.unsubscribe', ['token' => $token]))
        ->assertOk()
        ->assertSee('Unsubscribe');
});

it('rejects a tampered or unparseable token', function () {
    $this->get(route('notification-preferences.unsubscribe', ['token' => 'not-a-real-token']))
        ->assertStatus(410);
});

it('respects the scope captured in the token', function () {
    $scope = 'tenant-a';
    config(['notification-preferences.scope.resolver' => function () use (&$scope) {
        return $scope;
    }]);

    $type = marketingType();
    $user = makeUser();
    $token = app(UnsubscribeLinkGenerator::class)->token($user, 'marketing-news', 'mail');

    // Process the link while the ambient scope is something else entirely.
    $scope = 'tenant-z';
    $this->post(route('notification-preferences.unsubscribe.process', ['token' => $token]))->assertOk();

    $scope = 'tenant-a';
    expect($user->isSubscribedTo($type, 'mail'))->toBeFalse();
    $scope = 'tenant-z';
    expect($user->isSubscribedTo($type, 'mail'))->toBeTrue();
});

it('adds a List-Unsubscribe header to subscribable mail and omits it for locked types', function () {
    marketingType();
    transactionalType();
    $user = makeUser(['email' => 'user@example.com']);

    $user->notify(new MarketingNotification);
    $user->notify(new TransactionalNotification);

    $messages = app('mailer')->getSymfonyTransport()->messages();

    $marketing = $messages->map->getOriginalMessage()->first(fn ($m) => $m->getSubject() === 'News');
    $receipt = $messages->map->getOriginalMessage()->first(fn ($m) => $m->getSubject() === 'Receipt');

    expect($marketing->getHeaders()->has('List-Unsubscribe'))->toBeTrue()
        ->and($marketing->getHeaders()->has('List-Unsubscribe-Post'))->toBeTrue()
        ->and($receipt->getHeaders()->has('List-Unsubscribe'))->toBeFalse();
});
