<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Commands;

use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Denizaygundev\NotificationPreferences\Support\NotificationTypeRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

/**
 * Seed/refresh the notification-type registry from the host app's Notification classes.
 *
 * Idempotent: existing rows are never clobbered (admin edits to name/category/channels/locked
 * state are preserved). Newly discovered notifications are inserted with sensible defaults
 * inferred from their category and via() channels.
 */
class SyncNotificationTypesCommand extends Command
{
    protected $signature = 'notification-preferences:sync
        {--prune : Deactivate registry types whose notification class is no longer discovered}';

    protected $description = 'Discover Notification classes and sync them into the notification-type registry';

    public function handle(NotificationTypeRegistry $registry): int
    {
        $paths = array_filter((array) config('notification-preferences.discovery.paths', []), 'is_string');
        $paths = array_values(array_filter($paths, 'is_dir'));

        if ($paths === []) {
            $this->components->warn('No discovery paths configured. Set notification-preferences.discovery.paths.');

            return self::SUCCESS;
        }

        $created = 0;
        $existing = 0;
        $seenKeys = [];

        foreach ($this->discoverNotifications($paths) as $class) {
            $key = $registry->keyFor($class);
            $seenKeys[] = $key;

            $type = NotificationType::query()->firstOrNew(['key' => $key]);

            if ($type->exists) {
                $existing++;

                continue;
            }

            $category = $registry->categoryFor($class);
            $channels = $this->channelsFor($class);

            $type->fill([
                'name' => Str::headline(class_basename($class)),
                'description' => null,
                'category' => $category,
                'is_subscribable' => $category->isSubscribableByDefault(),
                'available_channels' => $channels,
                'default_channels' => $channels,
                'is_active' => true,
            ])->save();

            $created++;
        }

        $pruned = $this->option('prune') ? $this->prune($seenKeys) : 0;

        $this->components->info(sprintf(
            '%d created, %d already present%s.',
            $created,
            $existing,
            $this->option('prune') ? sprintf(', %d deactivated', $pruned) : '',
        ));

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $paths
     * @return iterable<int, class-string<Notification>>
     */
    private function discoverNotifications(array $paths): iterable
    {
        foreach (Finder::create()->files()->name('*.php')->in($paths) as $file) {
            $class = $this->classFromFile($file);

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, Notification::class) || (new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            yield $class;
        }
    }

    private function classFromFile(SplFileInfo $file): ?string
    {
        $contents = (string) file_get_contents($file->getRealPath());

        if (! preg_match('/namespace\s+([^;]+);/', $contents, $namespace)) {
            return null;
        }

        if (! preg_match('/\b(?:final\s+|abstract\s+)*class\s+(\w+)/', $contents, $class)) {
            return null;
        }

        return trim($namespace[1]).'\\'.$class[1];
    }

    /**
     * Infer default channels from a notification's via(), intersected with the configured,
     * manageable channels. Returns null when nothing can be determined.
     *
     * @param  class-string<Notification>  $class
     * @return array<int, string>|null
     */
    private function channelsFor(string $class): ?array
    {
        try {
            $instance = (new ReflectionClass($class))->newInstanceWithoutConstructor();

            // via() is a convention, not declared on the base Notification class.
            if (! method_exists($instance, 'via')) {
                return null;
            }

            $notifiable = new class extends Model {};

            $via = $instance->via($notifiable);
            $via = is_string($via) ? [$via] : (array) $via;

            $channels = array_values(array_intersect(
                array_filter($via, 'is_string'),
                array_keys((array) config('notification-preferences.channels', [])),
            ));

            return $channels ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $seenKeys
     */
    private function prune(array $seenKeys): int
    {
        return NotificationType::query()
            ->whereNotIn('key', $seenKeys)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
