<?php

declare(strict_types=1);

namespace App\Modules\Produccion\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Modules\Produccion\Services\ProduccionService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Support\ActionGuidance;

final class ProduccionController extends Controller
{
    public function __construct(
        private readonly ProduccionService $service,
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly Session $session
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        return $this->render(__DIR__ . '/../Views/index.php', [
            'producciones' => $this->service->listVisibleTo($user['id_usuario'], $user['rol']),
            'user' => $user,
            'success' => $this->session->flash('success'),
            'error' => $this->session->flash('error'),
            'nextStep' => ActionGuidance::decode($this->session->flash('next_step')),
        ]);
    }

    public function finalize(Request $request): Response
    {
        $user = $this->auth->user();
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $production = $this->service->finalizeHarvest($user['id_usuario'], $request->all());
            $this->session->flash('success', "Cosecha registrada: {$production->quantity} kg. Siguiente paso: revise el consolidado de producción.");
            $this->session->flash('next_step', ActionGuidance::encode(
                'Ciclo productivo completado',
                'Compruebe el consolidado de producción y valide el rendimiento final del lote.',
                'Ver producción',
                Url::route('/produccion'),
                'success',
                'fa-chart-column'
            ));
        } catch (ValidationException $exception) {
            $messages = array_merge(...array_values($exception->errors()));
            $this->session->flash('error', implode(' ', $messages));
        }
        return $this->redirect((string) $request->input('legacy', '') === '1'
            ? Url::route('/dashboard/agricultor', ['tab' => 'lote'])
            : Url::route('/produccion'));
    }
}
