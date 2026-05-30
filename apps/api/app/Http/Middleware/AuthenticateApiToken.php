<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = ApiToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $bearerToken))
            ->first();

        if (! $token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['message' => 'Invalid or expired API token.'], Response::HTTP_UNAUTHORIZED);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($token->user);
        $request->attributes->set('apiToken', $token);

        return $next($request);
    }
}
