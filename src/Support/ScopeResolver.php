<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Support;

/**
 * Resolves the current optional tenant/team scope for preference isolation.
 *
 * When no resolver is configured the scope is null and preferences are global per notifiable.
 */
class ScopeResolver
{
    public function current(): int|string|null
    {
        $resolver = config('notification-preferences.scope.resolver');

        if (! is_callable($resolver)) {
            return null;
        }

        $value = $resolver();

        return is_int($value) || is_string($value) ? $value : null;
    }

    public function column(): string
    {
        return (string) config('notification-preferences.scope.column', 'scope_id');
    }
}
