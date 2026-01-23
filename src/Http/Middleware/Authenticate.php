<?php

namespace StuMason\Kick\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use StuMason\Kick\Exceptions\UnauthorizedException;
use StuMason\Kick\Kick;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            throw UnauthorizedException::missingToken();
        }

        $scopes = Kick::scopesForToken($token);

        if ($scopes === null) {
            throw UnauthorizedException::invalidToken();
        }

        if ($scope !== null && ! Kick::tokenHasScope($token, $scope)) {
            throw UnauthorizedException::insufficientScope($scope);
        }

        // Store token info on request for later use
        $request->attributes->set('kick_token', $token);
        $request->attributes->set('kick_scopes', $scopes);

        return $next($request);
    }

    /**
     * Extract the bearer token from the request.
     */
    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }
}
