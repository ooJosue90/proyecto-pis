<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Shared\Exceptions\AuthenticationException;

final class AuthMiddleware
{
    public function __construct(private readonly Auth $auth)
    {
    }

    public function __invoke(Request $request, callable $next): mixed
    {
        if (!$this->auth->check()) {
            throw new AuthenticationException();
        }

        return $next($request);
    }
}
