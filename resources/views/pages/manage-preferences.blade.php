@php
    use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;

    /** @var array<int, array{type: \Denizaygundev\NotificationPreferences\Models\NotificationType, available: array<int,string>, channels: array<string,bool>}> $matrix */
    /** @var array<string, array{label?: string, icon?: string}> $channels */
    /** @var array<string, int> $pausePresets */
    /** @var \Denizaygundev\NotificationPreferences\Models\NotificationPause|null $activePause */
    /** @var string $timezone */

    $groups = collect($matrix)->groupBy(fn (array $item): string => $item['type']->category->value);
@endphp

<x-filament-panels::page>
    {{-- Pause controls --}}
    <x-filament::section>
        @if ($activePause)
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @if ($activePause->paused_until)
                        {{ __('notification-preferences::notification-preferences.paused_until', ['date' => $activePause->paused_until->copy()->timezone($timezone)->isoFormat('LLL')]) }}
                    @else
                        {{ __('notification-preferences::notification-preferences.paused_indefinitely') }}
                    @endif
                </p>
                <x-filament::button wire:click="resumePaused" color="warning" icon="heroicon-m-play">
                    {{ __('notification-preferences::notification-preferences.resume') }}
                </x-filament::button>
            </div>
        @else
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('notification-preferences::notification-preferences.pause') }}
                </p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($pausePresets as $label => $days)
                        <x-filament::button wire:click="pauseFor({{ (int) $days }})" color="gray" size="sm">
                            {{ $label }}
                        </x-filament::button>
                    @endforeach
                    <x-filament::button wire:click="pauseFor()" color="gray" size="sm" outlined>
                        &infin;
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::section>

    {{-- Subscription matrix, grouped by category --}}
    @forelse ($groups as $categoryValue => $items)
        @php $category = NotificationCategory::from($categoryValue); @endphp

        <x-filament::section :heading="$category->label()" :description="$category->description()">
            <div class="mb-3 flex justify-end">
                <x-filament::button
                    size="xs"
                    color="gray"
                    wire:click="unsubscribeCategory(@js($categoryValue))"
                    icon="heroicon-m-bell-slash"
                >
                    {{ __('Unsubscribe from all') }}
                </x-filament::button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 pr-4 text-left font-medium text-gray-500 dark:text-gray-400">
                                {{ __('Notification') }}
                            </th>
                            @foreach ($channels as $key => $channel)
                                <th class="px-3 py-2 text-center font-medium text-gray-500 dark:text-gray-400">
                                    {{ $channel['label'] ?? $key }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($items as $item)
                            @php $type = $item['type']; @endphp
                            <tr>
                                <td class="py-3 pr-4">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ $type->name }}</div>
                                    @if ($type->description)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $type->description }}</div>
                                    @endif
                                </td>
                                @foreach ($channels as $key => $channel)
                                    <td class="px-3 py-3 text-center">
                                        @if (in_array($key, $item['available'], true))
                                            <input
                                                type="checkbox"
                                                wire:click="toggleChannel({{ (int) $type->id }}, @js($key))"
                                                wire:loading.attr="disabled"
                                                @checked($item['channels'][$key] ?? false)
                                                class="size-5 cursor-pointer rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-white/20 dark:bg-white/5"
                                            />
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">&mdash;</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @empty
        <x-filament::section>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('There are no notification types you can manage yet.') }}
            </p>
        </x-filament::section>
    @endforelse
</x-filament-panels::page>
