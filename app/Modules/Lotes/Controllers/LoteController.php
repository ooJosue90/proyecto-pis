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
use App\Shared\Domain\CultivationStage;
use App\Shared\Exceptions\ValidationException;
use App\Shared\Support\ActionGuidance;
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
            'nextStep' => ActionGuidance::decode($this->session->flash('next_step')),
        ]);
    }

    public function store(Request $request): Response
    {
        $user = $this->auth->user();
        $legacy = (string) $request->input('legacy', '') === '1';
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $lote = $this->service->create($user['id_usuario'], $request->all());
            $this->session->flash('success', "Lote #{$lote->id} registrado correctamente en la etapa Siembra.");
            $this->session->flash('next_step', ActionGuidance::encode(
                'Siembra activa',
                'Revise el lote registrado y complete Siembra antes de avanzar a Riego; las fechas futuras permanecen como planificación.',
                'Ver lote registrado',
                $legacy ? '#lotes-registrados' : Url::route('/lotes'),
                'info',
                'fa-list-check'
            ));
        } catch (ValidationException $exception) {
            $messages = array_merge(...array_values($exception->errors()));
            $this->session->flash('error', implode(' ', $messages));
        }

        return $this->redirect($legacy
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
                'estados_fases' => $lote->phaseStates,
            ]]);
        }

        $stageError = $this->session->flash('error');
        try {
            $selectedStage = $this->service->reviewStage(
                $lote,
                (int) $request->input('fase', CultivationStage::NONE)
            );
        } catch (ValidationException $exception) {
            $selectedStage = $lote->etapaActual === CultivationStage::NONE
                ? CultivationStage::PLANTING
                : $lote->etapaActual;
            $stageError = implode(' ', array_merge(...array_values($exception->errors())));
        }

        return $this->render(self::VIEW_PATH . 'show.php', [
            'lote' => $lote,
            'user' => $user,
            'selectedStage' => $selectedStage,
            'success' => $this->session->flash('success'),
            'error' => $stageError,
            'nextStep' => ActionGuidance::decode($this->session->flash('next_step')),
            'csrfToken' => $this->csrf->token(),
        ]);
    }

    public function advanceStage(Request $request): Response
    {
        $user = $this->auth->user();
        $id = filter_var($request->route('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        try {
            if ($id === false) {
                throw new ValidationException(['id' => ['El identificador del lote no es válido.']]);
            }
            $this->csrf->validate((string) $request->input('_token', ''));
            $updated = $this->service->advanceStage(
                (int) $id,
                (string) $user['id_usuario'],
                (string) $user['rol'],
                (int) $request->input('fase', CultivationStage::NONE)
            );
            $this->session->flash('success', 'Fase actualizada. Ahora puede trabajar en ' . $updated->etapaLabel() . '.');
            $isHarvest = $updated->etapaActual === CultivationStage::HARVEST;
            $this->session->flash('next_step', ActionGuidance::encode(
                $isHarvest ? 'Cosecha en progreso' : 'Continúe con ' . $updated->etapaLabel(),
                $isHarvest
                    ? 'Al terminar la recolección, registre la producción para cerrar el ciclo del lote.'
                    : 'Realice las actividades planificadas y complete esta fase antes de avanzar a la siguiente.',
                $isHarvest ? 'Registrar producción' : 'Ver fase activa',
                $isHarvest
                    ? Url::route('/dashboard/agricultor', ['tab' => 'lote'])
                    : Url::route('/lotes/' . (int) $id, ['fase' => $updated->etapaActual]),
                $isHarvest ? 'warning' : 'info',
                $isHarvest ? 'fa-wheat-awn' : 'fa-arrow-right'
            ));
        } catch (ValidationException $exception) {
            $messages = array_merge(...array_values($exception->errors()));
            $this->session->flash('error', implode(' ', $messages));
        }

        return $this->redirect(Url::route('/lotes/' . (int) $id));
    }

    public function storeWithPests(Request $request): Response
    {
        $user = $this->auth->user();
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $data = $request->all();
            $stage = (int) ($data['etapa_actual'] ?? CultivationStage::NONE);
            if (!array_key_exists($stage, CultivationStage::labels(false))) {
                $legacyStages = is_array($data['etapas'] ?? null) ? $data['etapas'] : [];
                $legacyCodes = array_map(
                    static fn (mixed $legacyStage): int => CultivationStage::fromName((string) $legacyStage),
                    $legacyStages
                );
                $legacyCodes = array_values(array_filter(
                    $legacyCodes,
                    static fn (int $legacyCode): bool => $legacyCode !== CultivationStage::NONE
                ));
                $stage = $legacyCodes === [] ? CultivationStage::PLANTING : max($legacyCodes);
            }
            $data['etapa_siembra'] = $stage >= CultivationStage::PLANTING ? '1' : null;
            $data['etapa_riego'] = $stage >= CultivationStage::IRRIGATION ? '1' : null;
            $data['etapa_cosecha'] = $stage === CultivationStage::HARVEST ? '1' : null;

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
