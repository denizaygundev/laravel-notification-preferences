<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Denizaygundev\NotificationPreferences\Tests\Fixtures\Notifications\UnknownNotification;

beforeEach(function () {
    config(['notification-preferences.discovery.paths' => [__DIR__.'/Fixtures/Notifications']]);
});

it('discovers notifications and seeds the registry', function () {
    $this->artisan('notification-preferences:sync')->assertSuccessful();

    $marketing = NotificationType::query()->where('key', 'marketing-news')->first();
    expect($marketing)->not->toBeNull()
        ->and($marketing->category)->toBe(NotificationCategory::Marketing)
        ->and($marketing->is_subscribable)->toBeTrue()
        ->and($marketing->default_channels)->toEqual(['mail', 'database']);

    $receipt = NotificationType::query()->where('key', 'order-receipt')->first();
    expect($receipt->category)->toBe(NotificationCategory::Transactional)
        ->and($receipt->is_subscribable)->toBeFalse();

    // A notification with no contract is keyed by FQCN and defaults to the safe (locked) category.
    $unknown = NotificationType::query()->where('key', UnknownNotification::class)->first();
    expect($unknown)->not->toBeNull()
        ->and($unknown->is_subscribable)->toBeFalse();
});

it('is idempotent and preserves admin edits on re-run', function () {
    $this->artisan('notification-preferences:sync')->assertSuccessful();

    NotificationType::query()->where('key', 'marketing-news')->update([
        'name' => 'Custom name',
        'is_subscribable' => false,
    ]);

    $this->artisan('notification-preferences:sync')->assertSuccessful();

    $marketing = NotificationType::query()->where('key', 'marketing-news')->first();
    expect($marketing->name)->toBe('Custom name')
        ->and($marketing->is_subscribable)->toBeFalse()
        ->and(NotificationType::query()->where('key', 'marketing-news')->count())->toBe(1);
});

it('deactivates orphaned types when pruning', function () {
    NotificationType::query()->create([
        'key' => 'gone-away',
        'name' => 'Gone away',
        'category' => NotificationCategory::Marketing,
        'is_subscribable' => true,
        'is_active' => true,
    ]);

    $this->artisan('notification-preferences:sync --prune')->assertSuccessful();

    expect(NotificationType::query()->where('key', 'gone-away')->value('is_active'))->toBeFalse();
});
