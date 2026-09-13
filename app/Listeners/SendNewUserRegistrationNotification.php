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
        $address = config('mail.admin_notification_address');

        if (! $address) {
            return;
        }

        Notification::route('mail', $address)
            ->notify(new NewUserRegistered($event->user));
    }
}
