<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Modules\Cultivos\Services\CultivoService;
use App\Shared\Exceptions\ValidationException;

final class CultivoController extends Controller
{
    private const VIEW_PATH = __DIR__ . '/../Views/';

    public function __construct(
        private readonly CultivoService $service,
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly Session $session
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        $cultivos = $this->service->listVisibleTo($user['id_usuario'], $user['rol']);

        return $this->render(self::VIEW_PATH . 'index.php', [
            'cultivos' => $cultivos,
            'user' => $user,
            'csrfToken' => $this->csrf->token(),
            'success' => $this->session->flash('success'),
            'error' => $this->session->flash('error'),
            'old' => $this->session->get('_cultivo_old', []),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->auth->user();
        $legacy = (string) $request->input('legacy', '') === '1';

        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $cultivo = $this->service->create($user['id_usuario'], $request->all());
            $this->session->forget('_cultivo_old');
            $this->session->flash('success', "Cultivo {$cultivo->tipo} registrado correctamente.");
        } catch (ValidationException $exception) {
            $this->session->put('_cultivo_old', [
                'tipo' => trim((string) $request->input('tipo', '')),
                'fecha_siembra' => (string) $request->input('fecha_siembra', ''),
            ]);
            $messages = array_merge(...array_values($exception->errors()));
            $this->session->flash('error', implode(' ', $messages));
        }

        return $this->redirect($legacy
            ? Url::route('/dashboard/agricultor', ['tab' => 'lote'])
            : Url::route('/cultivos'));
    }

    public function show(Request $request): Response
    {
        $user = $this->auth->user();
        $id = $this->validatedId($request);

        $cultivo = $this->service->getVisible($id, $user['id_usuario'], $user['rol']);
        if ($request->expectsJson()) {
            return $this->json([
                'ok' => true,
                'data' => [
                    'id' => $cultivo->id,
                    'tipo' => $cultivo->tipo,
                    'fecha_siembra' => $cultivo->fechaSiembra,
                    'agricultor' => $cultivo->agricultor,
                ],
            ]);
        }

        return $this->render(self::VIEW_PATH . 'show.php', [
            'cultivo' => $cultivo,
            'user' => $user,
        ]);
    }

    public function destroy(Request $request): Response
    {
        $id = $this->validatedId($request);

        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $this->service->delete($id);
            $this->session->flash('success', 'Cultivo eliminado correctamente.');
        } catch (ValidationException $exception) {
            $messages = array_merge(...array_values($exception->errors()));
            $this->session->flash('error', implode(' ', $messages));
        }

        return $this->redirect(Url::route('/cultivos'));
    }

    private function validatedId(Request $request): int
    {
        $id = filter_var($request->route('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new ValidationException(['id' => ['El identificador del cultivo no es válido.']]);
        }

        return (int) $id;
    }
}
