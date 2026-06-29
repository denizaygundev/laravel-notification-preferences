# Laravel Notification Preferences

Give your users a real notification preference centre — per-notification, per-channel
subscribe/unsubscribe, pause-everything-for-a-while, one-click email unsubscribe, and a consent
log — with an optional [Filament](https://filamentphp.com) admin + user UI.

It is a framework-agnostic **engine** (a trait, a registry, and a `NotificationSending` enforcer)
with a **Filament layer** bolted on top. Use the engine with any UI, or drop in the Filament
screens for free.

## Features

- **Per-(type × channel) preferences** — a user can keep email on but turn off the in-app bell for
  the same notification.
- **Locked vs subscribable types** — admins classify each notification (transactional, marketing,
  …); transactional/locked types are always delivered.
- **Pause everything** for 1 week / 1 month / 3 months / a custom date / indefinitely, with
  auto-resume.
- **Enforcement at send time** via Laravel's `NotificationSending` event — works for queued and
  sync sends, and the database/bell channel, without touching your notification classes.
- **One-click unsubscribe** — RFC 8058 `List-Unsubscribe` headers + a public, login-free landing
  page (great for deliverability and GDPR/PECR/CAN-SPAM).
- **Consent / audit log** of every change, optionally mirrored to
  [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog).
- **Pluggable multi-tenant / team scoping** — off by default.
- **Auto-discovery** — `notification-preferences:sync` seeds the registry from your `Notification`
  classes.
- Safe by default — unknown notifications, anonymous (on-demand) notifiables, and locked types are
  never silently dropped.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Filament v5 (only if you use the UI layer)

## Installation

```bash
composer require denizaygundev/laravel-notification-preferences

php artisan vendor:publish --tag="notification-preferences-config"
php artisan vendor:publish --tag="notification-preferences-migrations"   # optional: only to customise
php artisan migrate
```

Migrations are auto-loaded, so publishing them is only needed if you want to customise the schema
(see [Notifiable key types](#notifiable-key-types)).

## Quick start

### 1. Make your notifiable manageable

```php
use Denizaygundev\NotificationPreferences\Concerns\HasNotificationPreferences;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    use HasNotificationPreferences;
}
```

Only notifiables using this trait are subject to preferences — everything else is always delivered.

### 2. Tell the package about your notifications

Three ways, in resolution order:

**a) Implement the contract** (most explicit):

```php
use Denizaygundev\NotificationPreferences\Contracts\ManagesNotificationPreferences;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;

class OrderShipped extends Notification implements ManagesNotificationPreferences
{
    public function notificationTypeKey(): string { return 'order-shipped'; }
    public function notificationCategory(): NotificationCategory { return NotificationCategory::Transactional; }
}
```

> Both methods must be deterministic and constructor-independent — the package may inspect an
> instance created without the constructor.

**b) Map them in config** (`config/notification-preferences.php`):

```php
'types' => [
    'order-shipped' => App\Notifications\OrderShipped::class,
],
```

**c) Auto-discover** — point `discovery.paths` at your notifications and run:

```bash
php artisan notification-preferences:sync          # idempotent; never clobbers admin edits
php artisan notification-preferences:sync --prune  # also deactivate types whose class is gone
```

Sync infers the category (and default channels from `via()`), and you refine the rest in the admin UI.

### 3. Register the Filament UI (optional)

```php
use Denizaygundev\NotificationPreferences\Filament\NotificationPreferencesPlugin;

// Admin panel — the type registry only:
->plugin(NotificationPreferencesPlugin::make()->userPage(false))

// Tenant / customer panel — the user preference matrix only:
->plugin(NotificationPreferencesPlugin::make()->adminResource(false))
```

## The API

Via the trait, or the `NotificationPreferences` facade (same engine):

```php
use Denizaygundev\NotificationPreferences\Facades\NotificationPreferences;

NotificationPreferences::for($user)->subscribe(OrderShipped::class, channel: 'mail');
NotificationPreferences::for($user)->unsubscribe(OrderShipped::class);          // all channels
NotificationPreferences::for($user)->unsubscribeAll(NotificationCategory::Marketing);
NotificationPreferences::for($user)->pause(now()->addMonth());                  // pause everything
NotificationPreferences::for($user)->resume();

$user->isSubscribedTo(OrderShipped::class, 'mail');   // bool
$user->hasActivePause();                              // bool
```

Preference rows are **sparse**: one exists only when a user diverges from the type's default. The
resolution order is *explicit preference → type default channels → allowed*.

## How enforcement works

A listener on `Illuminate\Notifications\Events\NotificationSending` returns `false` to suppress a
single channel when the user has unsubscribed or a pause is active. It runs on the queue worker, so
it covers queued notifications, and it never drops locked or unknown types. Disable globally with
`enforcement.enabled => false`.

## One-click unsubscribe

When `unsubscribe.enabled` is true the package decorates the `mail` notification channel to add, for
subscribable types only:

```
List-Unsubscribe: <https://your-app/notification-preferences/unsubscribe/{token}>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

The token is an encrypted, optionally-expiring payload (it carries the scope captured at send time),
so the public `GET` (confirmation page) and `POST` (one-click) endpoints work with no login.

## Multi-tenant / team scoping

Off by default. To isolate preferences per tenant/team, set a resolver:

```php
'scope' => [
    'resolver' => fn () => app('currentTeam')?->id, // or tenant()?->getTenantKey()
    'column' => 'scope_id',
],
```

Notification *types* stay global; only preferences, pauses and logs are scoped.

## Audit log

Every change is recorded. Choose the driver in config:

```php
'audit' => ['driver' => 'table'], // 'table' | 'activitylog' | 'both' | 'none'
```

`activitylog`/`both` require `spatie/laravel-activitylog`.

## Notifiable key types

Morph columns are stored as **strings**, which supports integer, ULID and UUID notifiable keys. If
your notifiable uses a **bigint** key on **PostgreSQL**, publish the migrations and change the
`notifiable_id` columns to `morphs()` to avoid a type-mismatch.

## Testing

```bash
composer test      # Pest
composer analyse   # PHPStan / Larastan
composer format    # Pint
```

## License

MIT. See [LICENSE.md](LICENSE.md).
