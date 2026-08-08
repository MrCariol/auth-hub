<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class TouchSanctumToken
{
    /**
     * Extends the current Sanctum token's expiration on each authenticated use,
     * so tokens stay valid as long as the PWA keeps being used within the window.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->forceFill([
                'expires_at' => now()->addMinutes((int) config('sanctum.pwa_token_ttl_minutes')),
            ])->save();
        }

        return $next($request);
    }
}
