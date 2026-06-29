<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Builds and parses the opaque, tamper-proof tokens embedded in one-click unsubscribe links.
 *
 * The token is an encrypted payload — it carries the notifiable, type key, channel and the
 * scope captured at send time, so the link works out-of-band (a public route with no tenant
 * context) and resolves the correct scope without trusting the ambient request.
 */
class UnsubscribeLinkGenerator
{
    public function __construct(private ScopeResolver $scope) {}

    public function url(Model $notifiable, string $typeKey, string $channel): string
    {
        return route('notification-preferences.unsubscribe', [
            'token' => $this->token($notifiable, $typeKey, $channel),
        ]);
    }

    public function token(Model $notifiable, string $typeKey, string $channel): string
    {
        return Crypt::encrypt([
            'nt' => $notifiable->getMorphClass(),
            'ni' => $notifiable->getKey(),
            'k' => $typeKey,
            'c' => $channel,
            's' => $this->scope->current(),
            'exp' => $this->expiry(),
        ]);
    }

    /**
     * @return array{nt: string, ni: int|string, k: string, c: string, s: int|string|null, exp: int|null}|null
     */
    public function parse(string $token): ?array
    {
        try {
            $payload = Crypt::decrypt($token);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload) || ! isset($payload['nt'], $payload['ni'], $payload['k'], $payload['c'])) {
            return null;
        }

        if (($payload['exp'] ?? null) !== null && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private function expiry(): ?int
    {
        $days = config('notification-preferences.unsubscribe.signed_ttl_days');

        return $days === null ? null : now()->addDays((int) $days)->getTimestamp();
    }
}
