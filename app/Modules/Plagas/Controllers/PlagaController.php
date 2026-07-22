<?php

declare(strict_types=1);

namespace App\Modules\Plagas\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Modules\Lotes\Services\LoteService;
use App\Modules\Plagas\Services\PlagaService;
use App\Shared\Exceptions\ValidationException;

final class PlagaController extends Controller
{
    private const VIEW = __DIR__ . '/../Views/index.php';

    public function __construct(
        private readonly PlagaService $service,
        private readonly LoteService $lotes,
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly Session $session
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        return $this->render(self::VIEW, [
            'plagas' => $this->service->listVisibleTo($user['id_usuario'], $user['rol']),
            'lotes' => $this->lotes->listVisibleTo($user['id_usuario'], $user['rol']),
            'user' => $user,
            'csrfToken' => $this->csrf->token(),
            'success' => $this->session->flash('success'),
            'error' => $this->session->flash('error'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->auth->user();
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $plaga = $this->service->create($user['id_usuario'], $request->all());
            $this->session->flash('success', "Plaga {$plaga->nombre} registrada correctamente.");
        } catch (ValidationException $exception) {
            $messages = array_merge(...array_values($exception->errors()));
            $this->session->flash('error', implode(' ', $messages));
        }
        return $this->redirect(Url::route('/plagas'));
    }
}
