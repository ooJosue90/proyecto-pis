<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Auth;
use App\Core\Authorization;
use App\Core\Csrf;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\PermissionMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\AuthorizationException;
use App\Shared\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        $this->session = new Session(dirname(__DIR__, 2) . '/storage/sessions');
        $this->session->start();
        $_SESSION = [];
    }

    public function testAuthorizationUsesCentralRolePermissions(): void
    {
        $_SESSION['id_usuario'] = 'AGR001';
        $_SESSION['rol'] = 'Agricultor';
        $authorization = new Authorization(new Auth($this->session), [
            'Agricultor' => ['cultivos.ver', 'cultivos.crear'],
        ]);

        self::assertTrue($authorization->can('cultivos.crear'));
        self::assertFalse($authorization->can('inventario.actualizar'));
        $this->expectException(AuthorizationException::class);
        $authorization->authorize('inventario.actualizar');
    }

    public function testCsrfRejectsAnInvalidToken(): void
    {
        $csrf = new Csrf($this->session);
        $csrf->token();

        $this->expectException(ValidationException::class);
        $csrf->validate('token-invalido');
    }

    public function testCsrfAcceptsTheSessionToken(): void
    {
        $csrf = new Csrf($this->session);
        $token = $csrf->token();
        $csrf->validate($token);
        self::assertSame(64, strlen($token));
    }

    public function testAuthMiddlewareRejectsAccessWithoutSession(): void
    {
        $middleware = new AuthMiddleware(new Auth($this->session));

        $this->expectException(AuthenticationException::class);
        $middleware(
            new Request('GET', '/cultivos', [], [], [], []),
            static fn (Request $request): Response => Response::json(['ok' => true])
        );
    }

    public function testPermissionMiddlewareRejectsAnIncorrectRole(): void
    {
        $_SESSION['id_usuario'] = 'AGR001';
        $_SESSION['rol'] = 'Agricultor';
        $auth = new Auth($this->session);
        $authorization = new Authorization($auth, ['Agricultor' => ['cultivos.ver']]);
        $middleware = new PermissionMiddleware($authorization, 'inventario.actualizar');

        $this->expectException(AuthorizationException::class);
        $middleware(
            new Request('POST', '/inventario', [], [], [], []),
            static fn (Request $request): Response => Response::json(['ok' => true])
        );
    }
}
