<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Se lo stato dell'utente cambia (bloccato/sospeso da un admin) mentre ha
     * gia' una sessione hub attiva, lo disconnette al prossimo giro invece di
     * lasciarlo navigare finche' non rifa' login.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $user->status === 'blocked'
                    ? 'Il tuo account è stato bloccato.'
                    : 'Il tuo account è in attesa di approvazione da parte di un amministratore.',
            ]);
        }

        return $next($request);
    }
}
