<?php

namespace App\Listeners;

use App\Notifications\NewUserRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;

class SendNewUserRegistrationNotification
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        Notification::route('mail', config('mail.admin_notification_address'))
            ->notify(new NewUserRegistered($event->user));
    }
}
