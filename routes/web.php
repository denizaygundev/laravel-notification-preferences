<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Http\Controllers\UnsubscribeController;
use Illuminate\Support\Facades\Route;

$prefix = (string) config('notification-preferences.unsubscribe.route_prefix', 'notification-preferences');
$middleware = (array) config('notification-preferences.unsubscribe.middleware', ['web']);

Route::prefix($prefix)->group(function () use ($middleware): void {
    // Browser landing page — needs session/web middleware for a styled confirmation.
    Route::middleware($middleware)
        ->get('unsubscribe/{token}', [UnsubscribeController::class, 'show'])
        ->name('notification-preferences.unsubscribe');

    // RFC 8058 one-click POST. CSRF-exempt by design: the encrypted token is the credential,
    // and mail clients POST without a session.
    Route::middleware(['throttle:60,1'])
        ->post('unsubscribe/{token}', [UnsubscribeController::class, 'update'])
        ->name('notification-preferences.unsubscribe.process');
});
