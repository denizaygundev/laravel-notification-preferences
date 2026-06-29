<?php

declare(strict_types=1);

it('isolates preferences per scope', function () {
    $scope = null;
    config(['notification-preferences.scope.resolver' => function () use (&$scope) {
        return $scope;
    }]);

    $type = marketingType(['default_channels' => ['mail']]);
    $user = makeUser();

    $scope = 'tenant-a';
    $user->unsubscribeFromNotification($type, 'mail');
    expect($user->isSubscribedTo($type, 'mail'))->toBeFalse();

    // A different scope is unaffected and still resolves the default.
    $scope = 'tenant-b';
    expect($user->isSubscribedTo($type, 'mail'))->toBeTrue();

    // The stored row carries the scope it was written in.
    expect($user->notificationPreferences()->where('scope_id', 'tenant-a')->count())->toBe(1)
        ->and($user->notificationPreferences()->where('scope_id', 'tenant-b')->count())->toBe(0);
});

it('treats a null resolver as a single global scope', function () {
    $type = marketingType(['default_channels' => ['mail']]);
    $user = makeUser();

    $user->unsubscribeFromNotification($type, 'mail');

    expect($user->isSubscribedTo($type, 'mail'))->toBeFalse()
        ->and($user->notificationPreferences()->whereNull('scope_id')->count())->toBe(1);
});
