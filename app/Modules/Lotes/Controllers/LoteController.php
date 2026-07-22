<?php

declare(strict_types=1);

namespace App\Modules\Lotes\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Modules\Lotes\Services\LoteService;
use App\Modules\Plagas\Services\PlagaService;
use App\Shared\Exceptions\ValidationException;
use Throwable;

final class LoteController extends Controller
{
    private const VIEW_PATH = __DIR__ . '/../Views/';

    public function __construct(
        private readonly LoteService $service,
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly Session $session,
        private readonly PlagaService $plagas,
        private readonly Database $database
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user();
        return $this->render(self::VIEW_PATH . 'index.php', [
            'lotes' => $this->service->listVisibleTo($user['id_usuario'], $user['rol']),
            'user' => $user,
            'success' => $this->session->flash('success'),
            'error' => $this->session->flash('error'),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->auth->user();
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $lote = $this->service->create($user['id_usuario'], $request->all());
            $this->session->flash('success', "Lote #{$lote->id} registrado correctamente.");
        } catch (ValidationException $exception) {
            $messages = array_merge(...array_values($exception->errors()));
            $this->session->flash('error', implode(' ', $messages));
        }

        return $this->redirect((string) $request->input('legacy', '') === '1'
            ? Url::route('/dashboard/agricultor', ['tab' => 'lote'])
            : Url::route('/lotes'));
    }

    public function show(Request $request): Response
    {
        $user = $this->auth->user();
        $id = filter_var($request->route('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new ValidationException(['id' => ['El identificador del lote no es válido.']]);
        }

        $lote = $this->service->getVisible((int) $id, $user['id_usuario'], $user['rol']);
        if ($request->expectsJson()) {
            return $this->json(['ok' => true, 'data' => [
                'id' => $lote->id,
                'id_cultivo' => $lote->cultivoId,
                'ubicacion' => $lote->ubicacion,
                'area' => $lote->area,
                'etapa' => $lote->etapaLabel(),
                'estado' => $lote->estadoLabel(),
                'cultivo' => $lote->cultivo,
                'agricultor' => $lote->agricultor,
                'fechas' => $lote->dates,
            ]]);
        }

        return $this->render(self::VIEW_PATH . 'show.php', ['lote' => $lote, 'user' => $user]);
    }

    public function storeWithPests(Request $request): Response
    {
        $user = $this->auth->user();
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $data = $request->all();
            $stage = (int) ($data['etapa_actual'] ?? 0);
            if (!in_array($stage, [1, 2, 3], true)) {
                $legacyStages = is_array($data['etapas'] ?? null) ? $data['etapas'] : [];
                $stage = in_array('Cosecha', $legacyStages, true)
                    ? 3
                    : (in_array('Desarrollo', $legacyStages, true) || in_array('Riego', $legacyStages, true) ? 2 : 1);
            }
            $data['etapa_siembra'] = $stage >= 1 ? '1' : null;
            $data['etapa_riego'] = $stage >= 2 ? '1' : null;
            $data['etapa_cosecha'] = $stage === 3 ? '1' : null;

            $lote = $this->database->transaction(function () use ($data, $user) {
                $lote = $this->service->create($user['id_usuario'], $data);
                $registered = [];
                foreach (is_array($data['plagas'] ?? null) ? $data['plagas'] : [] as $item) {
                    $name = trim((string) (is_array($item) ? ($item['nombre'] ?? '') : $item));
                    if ($name === '' || in_array($name, $registered, true)) {
                        continue;
                    }
                    $this->plagas->create($user['id_usuario'], ['id_lote' => $lote->id, 'nombre' => $name]);
                    $registered[] = $name;
                }

                return $lote;
            });

            return $this->json(['success' => true, 'id_lote' => $lote->id]);
        } catch (ValidationException $exception) {
            return $this->json(['error' => 'Revise los datos enviados.', 'details' => $exception->errors()], 422);
        } catch (Throwable $exception) {
            error_log('Error al guardar lote: ' . $exception->getMessage());
            return $this->json(['error' => 'No se pudo guardar el lote.'], 500);
        }
    }
}
