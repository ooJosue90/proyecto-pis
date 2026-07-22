<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Authorization;
use App\Core\Request;

final class PermissionMiddleware
{
    public function __construct(
        private readonly Authorization $authorization,
        private readonly string $permission
    ) {
    }

    public function __invoke(Request $request, callable $next): mixed
    {
        $this->authorization->authorize($this->permission);
        return $next($request);
    }
}
