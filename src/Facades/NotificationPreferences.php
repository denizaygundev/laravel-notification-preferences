<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Facades;

use Denizaygundev\NotificationPreferences\NotificationPreferencesManager;
use Denizaygundev\NotificationPreferences\PendingNotifiablePreferences;
use Denizaygundev\NotificationPreferences\Support\NotificationTypeRegistry;
use Denizaygundev\NotificationPreferences\Support\ScopeResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PendingNotifiablePreferences for(Model $notifiable)
 * @method static NotificationTypeRegistry registry()
 * @method static ScopeResolver scope()
 *
 * @see NotificationPreferencesManager
 */
class NotificationPreferences extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationPreferencesManager::class;
    }
}
