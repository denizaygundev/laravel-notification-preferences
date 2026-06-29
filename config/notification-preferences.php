<?php

declare(strict_types=1);

use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;

return [

    /*
    |--------------------------------------------------------------------------
    | Notifiable model
    |--------------------------------------------------------------------------
    | The model that receives notifications and owns preferences. Used by the
    | Filament user page and for documentation. Apply the
    | Denizaygundev\NotificationPreferences\Concerns\HasNotificationPreferences
    | trait to this model.
    */
    'notifiable_model' => null, // e.g. App\Models\User::class

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    | Delivery channels shown as the columns of the preference matrix. Keys are
    | Laravel notification channel names; "database" is the in-app / Filament
    | bell channel. Add SMS/Slack/broadcast by uncommenting once you wire the
    | underlying driver in the host app.
    */
    'channels' => [
        'mail' => [
            'label' => 'Email',
            'icon' => 'heroicon-o-envelope',
        ],
        'database' => [
            'label' => 'In-app',
            'icon' => 'heroicon-o-bell',
        ],
        // 'broadcast' => ['label' => 'Realtime', 'icon' => 'heroicon-o-signal'],
        // 'vonage'    => ['label' => 'SMS', 'icon' => 'heroicon-o-device-phone-mobile'],
        // 'slack'     => ['label' => 'Slack', 'icon' => 'heroicon-o-hashtag'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Explicit type map
    |--------------------------------------------------------------------------
    | Optionally map a stable type key => Notification class. Notifications may
    | instead implement ManagesNotificationPreferences, or fall back to their
    | fully-qualified class name as the key.
    */
    'types' => [
        // 'order-shipped' => App\Notifications\OrderShipped::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-discovery
    |--------------------------------------------------------------------------
    | Used by `php artisan notification-preferences:sync` to seed the type
    | registry from your Notification classes.
    */
    'discovery' => [
        'paths' => [
            // app_path('Notifications'),
        ],
        // Category assigned to discovered/unknown types. Transactional is locked,
        // which is the safe default: we never silently drop a message we don't understand.
        'default_category' => NotificationCategory::Transactional->value,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pause presets
    |--------------------------------------------------------------------------
    | Offered in the UI as "pause everything for…". label => number of days.
    | Indefinite pause is offered separately.
    */
    'pause_presets' => [
        '1 week' => 7,
        '1 month' => 30,
        '3 months' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope (optional multi-tenant / team isolation)
    |--------------------------------------------------------------------------
    | Resolver returns the current scope id (a scalar) or null. When null,
    | preferences are global per notifiable. Multi-tenant hosts can return e.g.
    | the current tenant/team id so the same user holds independent preferences
    | per tenant.
    */
    'scope' => [
        'resolver' => null, // e.g. fn () => tenant()?->getTenantKey()
        'column' => 'scope_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit / consent log
    |--------------------------------------------------------------------------
    | driver: 'table' | 'activitylog' | 'both' | 'none'. The 'activitylog' and
    | 'both' drivers require spatie/laravel-activitylog.
    */
    'audit' => [
        'driver' => 'table',
        'log_name' => 'notification-preferences',
    ],

    /*
    |--------------------------------------------------------------------------
    | One-click unsubscribe (RFC 8058)
    |--------------------------------------------------------------------------
    | Injects List-Unsubscribe / List-Unsubscribe-Post headers into outgoing
    | mail for subscribable types and exposes signed unsubscribe routes.
    */
    'unsubscribe' => [
        'enabled' => true,
        'route_prefix' => 'notification-preferences',
        'middleware' => ['web'],
        'signed_ttl_days' => 30, // null = links never expire
    ],

    /*
    |--------------------------------------------------------------------------
    | Enforcement
    |--------------------------------------------------------------------------
    | Master switch for the NotificationSending listener that suppresses
    | unsubscribed / paused channels at send time.
    */
    'enforcement' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table names
    |--------------------------------------------------------------------------
    */
    'table_names' => [
        'types' => 'notification_types',
        'preferences' => 'notification_preferences',
        'pauses' => 'notification_pauses',
        'logs' => 'notification_preference_logs',
    ],

];
