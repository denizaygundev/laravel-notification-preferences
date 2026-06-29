<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Filament\NotificationPreferencesPlugin;
use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource;
use Denizaygundev\NotificationPreferences\Models\NotificationType;

it('points the resource at the notification-type model with the expected pages', function () {
    expect(NotificationTypeResource::getModel())->toBe(NotificationType::class)
        ->and(array_keys(NotificationTypeResource::getPages()))->toEqual(['index', 'create', 'edit']);
});

it('exposes a stable plugin id and toggleable sections', function () {
    $plugin = NotificationPreferencesPlugin::make();

    expect($plugin->getId())->toBe('notification-preferences')
        ->and($plugin->adminResource(false))->toBe($plugin)
        ->and($plugin->userPage(false))->toBe($plugin);
});
