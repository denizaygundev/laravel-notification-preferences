<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource\Pages;

use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationTypes extends ListRecords
{
    protected static string $resource = NotificationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
