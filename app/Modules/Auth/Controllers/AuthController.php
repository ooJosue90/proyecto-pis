<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\PasswordResetService;
use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\ValidationException;

final class AuthController extends Controller
{
    private const VIEW_PATH = __DIR__ . '/../Views/';

    public function __construct(
        private readonly AuthService $authService,
        private readonly PasswordResetService $passwordResetService,
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly Session $session
    ) {
    }

    public function showLogin(Request $request): Response
    {
        if ($this->auth->check()) {
            return $this->redirect($this->dashboardForRole($this->auth->role()));
        }

        return $this->render(self::VIEW_PATH . 'login.php', [
            'csrfToken' => $this->csrf->token(),
            'error' => $this->session->flash('error'),
            'email' => (string) $this->session->get('_login_email', ''),
        ]);
    }

    public function login(Request $request): Response
    {
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $user = $this->authService->authenticate($request->all());
            $this->auth->login($user->sessionData());
            $this->session->forget('_login_email');
            return $this->redirect($this->dashboardForRole($user->role));
        } catch (ValidationException|AuthenticationException $exception) {
            $this->session->put('_login_email', trim((string) $request->input('email', '')));
            $this->session->flash('error', $exception instanceof AuthenticationException
                ? $exception->getMessage()
                : 'Revise los datos ingresados y vuelva a intentarlo.');
            return $this->redirect(Url::route('/login'));
        }
    }

    public function logout(Request $request): Response
    {
        $this->csrf->validate((string) $request->input('_token', ''));
        $this->auth->logout();
        return $this->redirect(Url::route('/login'));
    }

    public function showForgotPassword(Request $request): Response
    {
        return $this->render(self::VIEW_PATH . 'forgot-password.php', [
            'csrfToken' => $this->csrf->token(),
            'success' => $this->session->flash('success'),
            'error' => $this->session->flash('error'),
        ]);
    }

    public function requestPasswordReset(Request $request): Response
    {
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $this->passwordResetService->request($request->all());
            $this->session->flash(
                'success',
                'Si el correo está registrado, el administrador recibirá la solicitud de recuperación.'
            );
        } catch (ValidationException) {
            $this->session->flash('error', 'Ingrese un correo electrónico válido.');
        }

        return $this->redirect(Url::route('/password/forgot'));
    }

    private function dashboardForRole(?string $role): string
    {
        return Url::route(match ($role) {
            'Administrador' => '/dashboard/admin',
            'Agricultor' => '/dashboard/agricultor',
            'Bodeguero' => '/dashboard/bodega',
            default => '/login',
        });
    }
}
