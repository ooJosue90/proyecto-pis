<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testItDispatchesDynamicParameters(): void
    {
        $router = new Router();
        $capturedId = null;
        $router->get('/cultivos/{id}', static function (Request $request) use (&$capturedId): Response {
            $capturedId = $request->route('id');
            return Response::json(['ok' => true]);
        });

        $response = $router->dispatch(new Request('GET', '/cultivos/42', [], [], [], []));

        self::assertInstanceOf(Response::class, $response);
        self::assertSame('42', $capturedId);
    }

    public function testItRunsMiddlewareBeforeTheHandler(): void
    {
        $router = new Router();
        $calls = [];
        $middleware = static function (Request $request, callable $next) use (&$calls): mixed {
            $calls[] = 'middleware';
            return $next($request);
        };
        $router->post('/cultivos', static function (Request $request) use (&$calls): Response {
            $calls[] = 'handler';
            return Response::json(['ok' => true], 201);
        }, [$middleware]);

        $router->dispatch(new Request('POST', '/cultivos', [], [], [], []));

        self::assertSame(['middleware', 'handler'], $calls);
    }
}
