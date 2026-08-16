<?php

namespace App\Providers;

use App\Listeners\SendNewUserRegistrationNotification;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Registered::class, SendNewUserRegistrationNotification::class);

        // Pannello di amministrazione (gestione pwa_clients): accessibile solo
        // all'utente il cui indirizzo coincide con quello gia' usato per le
        // notifiche di nuova registrazione, niente ruolo/colonna dedicati.
        Gate::define('access-admin', function (User $user) {
            $adminEmail = config('mail.admin_notification_address');

            return $adminEmail && strcasecmp($user->email, $adminEmail) === 0;
        });
    }
}
