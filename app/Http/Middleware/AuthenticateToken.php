<?php

namespace App\Http\Middleware;

use App\Exceptions\BusinessException;
use App\Models\Token;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AuthenticateToken
{
    public function handle(Request $request, Closure $next)
    {
        $tokenValue = $request->bearerToken();

        if (! $tokenValue) {
            throw new BusinessException('Unauthorized. Missing bearer token.', [], 401);
        }

        $token = Token::query()
            ->where('token', $tokenValue)
            ->whereNull('revoked_at')
            ->first();

        if (! $token) {
            throw new BusinessException('Invalid or expired token.', [], 401);
        }

        $user = User::find($token->user_id);

        if (! $user) {
            throw new BusinessException('Authenticated user not found.', [], 401);
        }

        $request->setUserResolver(fn () => $user);
        $request->attributes->set('auth_user', $user);

        return $next($request);
    }
}
