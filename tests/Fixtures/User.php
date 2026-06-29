<?php

declare(strict_types=1);

namespace Denizaygundev\NotificationPreferences\Tests\Fixtures;

use Denizaygundev\NotificationPreferences\Concerns\HasNotificationPreferences;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class User extends Model
{
    use HasNotificationPreferences;
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}
