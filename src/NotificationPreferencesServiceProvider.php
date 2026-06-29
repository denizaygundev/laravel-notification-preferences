<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences;

use Denizaygundev\NotificationPreferences\Channels\PreferenceAwareMailChannel;
use Denizaygundev\NotificationPreferences\Commands\SyncNotificationTypesCommand;
use Denizaygundev\NotificationPreferences\Listeners\EnforceNotificationPreferences;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NotificationPreferencesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('notification-preferences')
            ->hasConfigFile()
            ->hasViews('notification-preferences')
            ->hasTranslations()
            ->hasRoute('web')
            ->hasCommand(SyncNotificationTypesCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(NotificationPreferencesManager::class);
    }

    public function packageBooted(): void
    {
        // Migrations are auto-loaded (so the host doesn't need to publish) but also publishable
        // for customisation (e.g. switching string morph keys to bigint).
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'notification-preferences-migrations');
        }

        $this->registerEnforcementListener();
        $this->registerMailChannel();
    }

    private function registerEnforcementListener(): void
    {
        Event::listen(NotificationSending::class, [EnforceNotificationPreferences::class, 'handle']);
    }

    /**
     * Decorate the `mail` notification channel so subscribable notifications carry a
     * one-click List-Unsubscribe header. The subclass behaves identically otherwise.
     */
    private function registerMailChannel(): void
    {
        if (! config('notification-preferences.unsubscribe.enabled', true)) {
            return;
        }

        $this->app->resolving(ChannelManager::class, function (ChannelManager $manager): void {
            $manager->extend('mail', fn ($app) => $app->make(PreferenceAwareMailChannel::class));
        });
    }
}
