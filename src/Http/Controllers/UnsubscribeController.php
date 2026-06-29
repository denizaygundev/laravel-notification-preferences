<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Http\Controllers;

use Denizaygundev\NotificationPreferences\Facades\NotificationPreferences;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Denizaygundev\NotificationPreferences\Support\UnsubscribeLinkGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Response;

/**
 * Public, login-free one-click unsubscribe endpoints. The encrypted token is the credential —
 * GET renders a confirmation page, POST performs the unsubscribe (RFC 8058 one-click).
 */
class UnsubscribeController
{
    public function __construct(private UnsubscribeLinkGenerator $links) {}

    public function show(string $token): View|Response
    {
        $payload = $this->links->parse($token);

        if ($payload === null) {
            return $this->invalid();
        }

        return response()->view('notification-preferences::unsubscribe.confirm', [
            'token' => $token,
            'type' => $this->typeFor($payload),
            'channel' => $payload['c'],
        ]);
    }

    public function update(string $token): View|Response
    {
        $payload = $this->links->parse($token);

        if ($payload === null) {
            return $this->invalid();
        }

        $notifiable = $this->resolveNotifiable($payload);

        if ($notifiable !== null) {
            NotificationPreferences::for($notifiable)
                ->withScope($payload['s'] ?? null)
                ->unsubscribe($payload['k'], $payload['c']);
        }

        return response()->view('notification-preferences::unsubscribe.done', [
            'type' => $this->typeFor($payload),
            'channel' => $payload['c'],
        ]);
    }

    private function invalid(): Response
    {
        return response()->view('notification-preferences::unsubscribe.invalid', [], Response::HTTP_GONE);
    }

    /**
     * @param  array{k: string}  $payload
     */
    private function typeFor(array $payload): ?NotificationType
    {
        return NotificationType::query()->where('key', $payload['k'])->first();
    }

    /**
     * @param  array{nt: string, ni: int|string}  $payload
     */
    private function resolveNotifiable(array $payload): ?Model
    {
        $class = Relation::getMorphedModel($payload['nt']) ?? $payload['nt'];

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        $model = $class::query()->find($payload['ni']);

        return $model instanceof Model ? $model : null;
    }
}
