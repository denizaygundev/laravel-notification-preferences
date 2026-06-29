<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences;

use DateTimeInterface;
use Denizaygundev\NotificationPreferences\Concerns\HasNotificationPreferences;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Listeners\EnforceNotificationPreferences;
use Denizaygundev\NotificationPreferences\Models\NotificationPause;
use Denizaygundev\NotificationPreferences\Models\NotificationPreference;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Denizaygundev\NotificationPreferences\Support\NotificationTypeRegistry;
use Denizaygundev\NotificationPreferences\Support\PreferenceAuditor;
use Denizaygundev\NotificationPreferences\Support\ScopeResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Fluent preference API bound to a single notifiable, in the current scope.
 *
 * Every public read/write here is the single source of truth used by both the
 * {@see HasNotificationPreferences} trait and the
 * {@see EnforceNotificationPreferences} listener.
 *
 * The `$type` argument throughout may be a {@see NotificationType}, a Notification instance, a
 * Notification class-string, or a stable type key string.
 */
class PendingNotifiablePreferences
{
    private bool $scopeOverridden = false;

    private int|string|null $scopeOverride = null;

    public function __construct(
        private Model $notifiable,
        private NotificationTypeRegistry $registry,
        private ScopeResolver $scope,
        private PreferenceAuditor $auditor,
    ) {}

    /**
     * Operate within an explicit scope instead of the ambient resolver. Used by the one-click
     * unsubscribe flow, which must act in the scope captured in the link, not the request.
     */
    public function withScope(int|string|null $scope): static
    {
        $clone = clone $this;
        $clone->scopeOverridden = true;
        $clone->scopeOverride = $scope;

        return $clone;
    }

    public function subscribe(object|string $type, ?string $channel = null): static
    {
        $model = $this->resolveType($type);

        if ($model === null) {
            return $this;
        }

        foreach ($channel !== null ? [$channel] : $model->availableChannels() as $each) {
            $this->setChannel($model, $each, true);
        }

        return $this;
    }

    public function unsubscribe(object|string $type, ?string $channel = null): static
    {
        $model = $this->resolveType($type);

        // Locked types cannot be unsubscribed from.
        if ($model === null || ! $model->is_subscribable) {
            return $this;
        }

        foreach ($channel !== null ? [$channel] : $model->availableChannels() as $each) {
            $this->setChannel($model, $each, false);
        }

        return $this;
    }

    public function subscribeAll(): static
    {
        NotificationType::query()->active()->subscribable()->get()
            ->each(fn (NotificationType $type) => $this->subscribe($type));

        return $this;
    }

    public function unsubscribeAll(?NotificationCategory $category = null): static
    {
        $query = NotificationType::query()->active()->subscribable();

        if ($category !== null) {
            $query->where('category', $category->value);
        }

        $query->get()->each(fn (NotificationType $type) => $this->unsubscribe($type));

        return $this;
    }

    public function isSubscribedTo(object|string $type, string $channel): bool
    {
        $model = $this->resolveType($type);

        if ($model === null || ! $model->is_subscribable) {
            return true; // unknown or locked → always delivered
        }

        $preference = $this->preferencesQuery()
            ->where('notification_type_id', $model->getKey())
            ->where('channel', $channel)
            ->first();

        if ($preference !== null) {
            return (bool) $preference->subscribed;
        }

        return in_array($channel, $model->defaultChannels(), true);
    }

    public function pause(?DateTimeInterface $until = null, NotificationCategory|string|null $category = null): static
    {
        $category = $this->normalizeCategory($category);

        NotificationPause::query()->updateOrCreate(
            [
                'notifiable_type' => $this->notifiable->getMorphClass(),
                'notifiable_id' => $this->notifiable->getKey(),
                $this->scope->column() => $this->scopeValue(),
                'category' => $category,
            ],
            ['paused_until' => $until],
        );

        $this->auditor->record($this->notifiable, 'pause', array_filter([
            'until' => $until?->format(DATE_ATOM),
            'category' => $category,
        ]));

        return $this;
    }

    public function resume(NotificationCategory|string|null $category = null): static
    {
        $category = $this->normalizeCategory($category);

        $query = $this->pausesQuery();

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($query->delete() > 0) {
            $this->auditor->record($this->notifiable, 'resume', array_filter(['category' => $category]));
        }

        return $this;
    }

    public function hasActivePause(NotificationCategory|string|null $category = null): bool
    {
        $category = $this->normalizeCategory($category);

        return $this->pausesQuery()
            ->active()
            ->where(function (Builder $query) use ($category): void {
                $query->whereNull('category');

                if ($category !== null) {
                    $query->orWhere('category', $category);
                }
            })
            ->exists();
    }

    /**
     * The active pause covering the given category (or the global pause when null), if any.
     * Useful for rendering a "paused until…" banner.
     */
    public function activePause(NotificationCategory|string|null $category = null): ?NotificationPause
    {
        $category = $this->normalizeCategory($category);

        return $this->pausesQuery()
            ->active()
            ->where(function (Builder $query) use ($category): void {
                $query->whereNull('category');

                if ($category !== null) {
                    $query->orWhere('category', $category);
                }
            })
            ->orderByRaw('paused_until is null desc')
            ->orderByDesc('paused_until')
            ->first();
    }

    /** @return Collection<int, NotificationPreference> */
    public function preferences(): Collection
    {
        return $this->preferencesQuery()->get();
    }

    /** @return Collection<int, NotificationPause> */
    public function pauses(): Collection
    {
        return $this->pausesQuery()->get();
    }

    /**
     * Resolved grid for the user UI: each subscribable type with a per-channel boolean.
     *
     * @return array<int, array{type: NotificationType, available: array<int, string>, channels: array<string, bool>}>
     */
    public function matrix(): array
    {
        $channels = array_keys((array) config('notification-preferences.channels', []));

        return NotificationType::query()->active()->subscribable()
            ->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (NotificationType $type): array => [
                'type' => $type,
                'available' => $type->availableChannels(),
                'channels' => collect($channels)->mapWithKeys(fn (string $channel): array => [
                    $channel => $this->isSubscribedTo($type, $channel),
                ])->all(),
            ])->all();
    }

    private function setChannel(NotificationType $type, string $channel, bool $subscribed): void
    {
        $existing = $this->preferencesQuery()
            ->where('notification_type_id', $type->getKey())
            ->where('channel', $channel)
            ->first();

        // No-op if the desired state already holds — explicitly, or via the type default
        // (keeping preference rows sparse and avoiding spurious audit entries).
        if ($existing !== null) {
            if ((bool) $existing->subscribed === $subscribed) {
                return;
            }
        } elseif (in_array($channel, $type->defaultChannels(), true) === $subscribed) {
            return;
        }

        NotificationPreference::query()->updateOrCreate(
            [
                'notifiable_type' => $this->notifiable->getMorphClass(),
                'notifiable_id' => $this->notifiable->getKey(),
                $this->scope->column() => $this->scopeValue(),
                'notification_type_id' => $type->getKey(),
                'channel' => $channel,
            ],
            ['subscribed' => $subscribed],
        );

        $this->auditor->record(
            $this->notifiable,
            $subscribed ? 'subscribe' : 'unsubscribe',
            ['subscribed' => $subscribed],
            $type,
            $channel,
        );
    }

    private function resolveType(object|string $type): ?NotificationType
    {
        if ($type instanceof NotificationType) {
            return $type;
        }

        return NotificationType::query()
            ->where('key', $this->registry->keyFor($type))
            ->first();
    }

    private function normalizeCategory(NotificationCategory|string|null $category): ?string
    {
        if ($category === null) {
            return null;
        }

        return $category instanceof NotificationCategory ? $category->value : $category;
    }

    private function scopeValue(): int|string|null
    {
        return $this->scopeOverridden ? $this->scopeOverride : $this->scope->current();
    }

    /** @return Builder<NotificationPreference> */
    private function preferencesQuery(): Builder
    {
        return NotificationPreference::query()
            ->where('notifiable_type', $this->notifiable->getMorphClass())
            ->where('notifiable_id', $this->notifiable->getKey())
            ->where($this->scope->column(), $this->scopeValue());
    }

    /** @return Builder<NotificationPause> */
    private function pausesQuery(): Builder
    {
        return NotificationPause::query()
            ->where('notifiable_type', $this->notifiable->getMorphClass())
            ->where('notifiable_id', $this->notifiable->getKey())
            ->where($this->scope->column(), $this->scopeValue());
    }
}
