<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences;

use Denizaygundev\NotificationPreferences\Support\NotificationTypeRegistry;
use Denizaygundev\NotificationPreferences\Support\PreferenceAuditor;
use Denizaygundev\NotificationPreferences\Support\ScopeResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * Entry point / facade root for the preference engine.
 */
class NotificationPreferencesManager
{
    public function __construct(
        private NotificationTypeRegistry $registry,
        private ScopeResolver $scope,
        private PreferenceAuditor $auditor,
    ) {}

    /** Begin a fluent preference interaction for a notifiable in the current scope. */
    public function for(Model $notifiable): PendingNotifiablePreferences
    {
        return new PendingNotifiablePreferences($notifiable, $this->registry, $this->scope, $this->auditor);
    }

    public function registry(): NotificationTypeRegistry
    {
        return $this->registry;
    }

    public function scope(): ScopeResolver
    {
        return $this->scope;
    }
}
