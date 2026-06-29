<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource\Pages;

use Denizaygundev\NotificationPreferences\Filament\Resources\NotificationTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationType extends CreateRecord
{
    protected static string $resource = NotificationTypeResource::class;
}
