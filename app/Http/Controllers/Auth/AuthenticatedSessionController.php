<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\PwaClient;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view, or silently complete the handoff if the hub
     * session is already authenticated (cross-PWA SSO).
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->isActive()) {
                $this->logoutInactive($request);

                return redirect()->route('login')->withErrors(['email' => $this->statusMessage($user)]);
            }

            return $this->handoff($request, $user);
        }

        return view('auth.login', [
            'client' => $request->query('client'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->isActive()) {
            $this->logoutInactive($request);

            throw ValidationException::withMessages(['email' => $this->statusMessage($user)]);
        }

        return $this->handoff($request, $user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Redirect the user back to the requesting PWA with a fresh API token,
     * or to the homepage launcher if no (valid) client was specified.
     */
    protected function handoff(Request $request, $user): RedirectResponse
    {
        $clientName = $request->input('client', $request->query('client'));

        $client = $clientName ? PwaClient::where('name', $clientName)->first() : null;

        if (! $client) {
            return redirect()->away(config('app.homepage_url'));
        }

        $token = $user->createToken(
            $client->name,
            ['*'],
            now()->addMinutes((int) config('sanctum.pwa_token_ttl_minutes'))
        )->plainTextToken;

        return redirect()->away($client->callbackUrl().'#token='.$token);
    }

    protected function logoutInactive(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    protected function statusMessage(User $user): string
    {
        return $user->status === 'blocked'
            ? 'Il tuo account è stato bloccato. Contatta l\'amministratore.'
            : 'Il tuo account è in attesa di approvazione da parte di un amministratore.';
    }
}
