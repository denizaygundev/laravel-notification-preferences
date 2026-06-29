<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource\Pages;

use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNotificationType extends EditRecord
{
    protected static string $resource = NotificationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
