<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Filament;

use Denizaygundev\NotificationPreferences\Filament\Pages\ManageNotificationPreferences;
use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Registers the notification-preferences UI into a Filament panel.
 *
 * Register the admin type-registry resource and the user preference page independently — e.g.
 * the resource in an admin panel and the page in the tenant/customer panels:
 *
 *   ->plugin(NotificationPreferencesPlugin::make()->userPage(false))   // admin: resource only
 *   ->plugin(NotificationPreferencesPlugin::make()->adminResource(false)) // tenant: page only
 */
class NotificationPreferencesPlugin implements Plugin
{
    protected bool $hasAdminResource = true;

    protected bool $hasUserPage = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function getId(): string
    {
        return 'notification-preferences';
    }

    public function register(Panel $panel): void
    {
        if ($this->hasAdminResource) {
            $panel->resources([NotificationTypeResource::class]);
        }

        if ($this->hasUserPage) {
            $panel->pages([ManageNotificationPreferences::class]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function adminResource(bool $condition = true): static
    {
        $this->hasAdminResource = $condition;

        return $this;
    }

    public function userPage(bool $condition = true): static
    {
        $this->hasUserPage = $condition;

        return $this;
    }
}
