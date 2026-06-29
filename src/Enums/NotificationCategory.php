<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Enums;

/**
 * Built-in classification for a notification type.
 *
 * The category drives the *default* of whether users may unsubscribe from a type:
 * "locked" categories (transactional, account) are delivered no matter what, while the
 * rest default to user-controllable. An admin can still override this per type via the
 * `is_subscribable` flag on the type itself.
 */
enum NotificationCategory: string
{
    case Transactional = 'transactional';
    case Account = 'account';
    case Marketing = 'marketing';
    case Announcement = 'announcement';
    case Activity = 'activity';

    public function label(): string
    {
        return match ($this) {
            self::Transactional => 'Transactional',
            self::Account => 'Account & security',
            self::Marketing => 'Marketing',
            self::Announcement => 'Announcements',
            self::Activity => 'Activity',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Transactional => 'Receipts, confirmations and other messages tied to an action you took. Always delivered.',
            self::Account => 'Security, password and account-related messages. Always delivered.',
            self::Marketing => 'Promotions, offers and other marketing messages.',
            self::Announcement => 'Product news and announcements.',
            self::Activity => 'Updates about activity relevant to you.',
        };
    }

    /**
     * Whether notifications in this category may be unsubscribed from by default.
     *
     * Locked categories (transactional, account) cannot be turned off — they are the
     * messages a user must receive to operate the account safely.
     */
    public function isSubscribableByDefault(): bool
    {
        return match ($this) {
            self::Transactional, self::Account => false,
            self::Marketing, self::Announcement, self::Activity => true,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Transactional => 'gray',
            self::Account => 'danger',
            self::Marketing => 'warning',
            self::Announcement => 'info',
            self::Activity => 'success',
        };
    }

    /** @return array<string, string> value => label, for Filament selects. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            static function (array $carry, self $case): array {
                $carry[$case->value] = $case->label();

                return $carry;
            },
            [],
        );
    }
}
