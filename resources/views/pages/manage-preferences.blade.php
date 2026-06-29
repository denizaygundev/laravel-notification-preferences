@php
    use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;

    /** @var array<int, array{type: \Denizaygundev\NotificationPreferences\Models\NotificationType, available: array<int,string>, channels: array<string,bool>}> $matrix */
    /** @var array<string, array{label?: string, icon?: string}> $channels */
    /** @var array<string, int> $pausePresets */
    /** @var \Denizaygundev\NotificationPreferences\Models\NotificationPause|null $activePause */
    /** @var string $timezone */

    // Layout is done with inline styles (and currentColor/opacity for theming) so the page renders
    // identically in any host panel without depending on the host's Tailwind build scanning this view.
    $groups = collect($matrix)->groupBy(fn (array $item): string => $item['type']->category->value);
    $rowFlex = 'display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center; justify-content:space-between;';
    $gridCols = 'minmax(0, 1fr) repeat('.count($channels).', minmax(4rem, 6rem))';
    $divider = 'border-top:1px solid color-mix(in srgb, currentColor 10%, transparent);';
@endphp

<x-filament-panels::page>
    {{-- Pause controls --}}
    <x-filament::section>
        @if ($activePause)
            <div style="{{ $rowFlex }}">
                <p style="margin:0;">
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
            <div style="{{ $rowFlex }}">
                <p style="margin:0; font-weight:600;">
                    {{ __('notification-preferences::notification-preferences.pause') }}
                </p>
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
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
            <div style="display:flex; justify-content:flex-end; margin-bottom:0.75rem;">
                <x-filament::button
                    size="xs"
                    color="gray"
                    icon="heroicon-m-bell-slash"
                    wire:click="unsubscribeCategory(@js($categoryValue))"
                >
                    {{ __('Unsubscribe from all') }}
                </x-filament::button>
            </div>

            <div style="overflow-x:auto;">
                <div style="display:grid; grid-template-columns:{{ $gridCols }}; gap:0.75rem 1rem; align-items:center; min-width:24rem;">
                    {{-- Header row --}}
                    <div></div>
                    @foreach ($channels as $key => $channel)
                        <div style="text-align:center; font-weight:600; font-size:0.875rem;">
                            {{ $channel['label'] ?? $key }}
                        </div>
                    @endforeach

                    {{-- One grid row per notification type --}}
                    @foreach ($items as $item)
                        @php $type = $item['type']; @endphp
                        <div style="{{ $divider }} padding-top:0.75rem;">
                            <div style="font-weight:500;">{{ $type->name }}</div>
                            @if ($type->description)
                                <div style="font-size:0.8rem; opacity:0.6;">{{ $type->description }}</div>
                            @endif
                        </div>
                        @foreach ($channels as $key => $channel)
                            <div style="{{ $divider }} padding-top:0.75rem; text-align:center;">
                                @if (in_array($key, $item['available'], true))
                                    <x-filament::input.checkbox
                                        wire:click="toggleChannel({{ (int) $type->id }}, @js($key))"
                                        wire:loading.attr="disabled"
                                        @checked($item['channels'][$key] ?? false)
                                    />
                                @else
                                    <span style="opacity:0.4;">&mdash;</span>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </x-filament::section>
    @empty
        <x-filament::section>
            <p style="margin:0; opacity:0.7;">
                {{ __('There are no notification types you can manage yet.') }}
            </p>
        </x-filament::section>
    @endforelse
</x-filament-panels::page>
