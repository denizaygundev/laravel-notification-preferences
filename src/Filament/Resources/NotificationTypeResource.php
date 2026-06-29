<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Filament\Resources;

use BackedEnum;
use Denizaygundev\NotificationPreferences\Enums\NotificationCategory;
use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource\Pages\CreateNotificationType;
use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource\Pages\EditNotificationType;
use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource\Pages\ListNotificationTypes;
use Denizaygundev\NotificationPreferences\Models\NotificationType;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Admin registry of notification types — classify each notification and decide which ones users
 * may unsubscribe from (the `is_subscribable` toggle; locked types are always delivered).
 */
class NotificationTypeResource extends Resource
{
    protected static ?string $model = NotificationType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationLabel = 'Notification types';

    protected static ?string $modelLabel = 'notification type';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),

            TextInput::make('key')
                ->helperText('Stable identifier used to match the notification. Usually set by sync.')
                ->required()
                ->maxLength(255)
                ->disabledOn('edit'),

            Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),

            Select::make('category')
                ->options(NotificationCategory::options())
                ->required()
                ->native(false),

            Toggle::make('is_subscribable')
                ->label('Users may unsubscribe')
                ->helperText('Off = locked: always delivered regardless of preferences (e.g. transactional).')
                ->default(true),

            Select::make('available_channels')
                ->label('Available channels')
                ->multiple()
                ->options(static::channelOptions())
                ->native(false),

            Select::make('default_channels')
                ->label('On by default')
                ->multiple()
                ->options(static::channelOptions())
                ->native(false),

            TextInput::make('sort_order')
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('key')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (NotificationCategory $state): string => $state->label())
                    ->color(fn (NotificationCategory $state): string => $state->color()),
                IconColumn::make('is_subscribable')->label('Unsub.')->boolean(),
                IconColumn::make('is_active')->label('Active')->boolean(),
                TextColumn::make('sort_order')->label('Order')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationTypes::route('/'),
            'create' => CreateNotificationType::route('/create'),
            'edit' => EditNotificationType::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function channelOptions(): array
    {
        $options = [];

        foreach ((array) config('notification-preferences.channels', []) as $key => $channel) {
            $options[$key] = $channel['label'] ?? $key;
        }

        return $options;
    }
}
