<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences;

use Denizaygundev\NotificationPreferences\Channels\PreferenceAwareMailChannel;
use Denizaygundev\NotificationPreferences\Commands\SyncNotificationTypesCommand;
use Denizaygundev\NotificationPreferences\Listeners\EnforceNotificationPreferences;
use Illuminate\Foundation\Console\AboutCommand;
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
        if ($this->app->runningInConsole()) {
            // Publish-only: the host publishes the migrations (Laravel re-stamps them with the
            // current timestamp) and runs `php artisan migrate`. This keeps the schema
            // customisable — e.g. switching the string morph keys to bigint — with no risk of a
            // double-run from also auto-loading them.
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'notification-preferences-migrations');
        }

        $this->registerEnforcementListener();
        $this->registerMailChannel();
        $this->registerAboutCommand();
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

    private function registerAboutCommand(): void
    {
        AboutCommand::add('Notification Preferences', fn (): array => [
            'Enforcement' => config('notification-preferences.enforcement.enabled') ? 'enabled' : 'disabled',
            'One-click unsubscribe' => config('notification-preferences.unsubscribe.enabled') ? 'enabled' : 'disabled',
            'Audit driver' => (string) config('notification-preferences.audit.driver'),
        ]);
    }
}
