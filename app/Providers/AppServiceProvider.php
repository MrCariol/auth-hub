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

        // Pannello di amministrazione: accessibile a chiunque abbia il flag
        // is_admin, impostabile da un admin esistente nella lista utenti.
        Gate::define('access-admin', function (User $user) {
            return $user->is_admin;
        });
    }
}
