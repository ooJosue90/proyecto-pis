<?php

declare(strict_types=1);

namespace App\Modules\Cultivos\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Cultivos\Repositories\AdminAgricultureRepository;
use App\Modules\Cultivos\Services\CultivoService;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ValidationException;

final class AdminAgricultureController extends Controller
{
    public function __construct(
        private readonly AdminAgricultureRepository $repository,
        private readonly CultivoService $cultivos,
        private readonly Csrf $csrf
    ) {
    }

    public function fragment(Request $request): Response
    {
        return $this->render(__DIR__ . '/../Views/admin-fragment.php', $this->repository->dashboard() + [
            'csrfToken' => $this->csrf->token(),
        ]);
    }

    public function delete(Request $request): Response
    {
        $this->csrf->validate((string) $request->input('_token', ''));
        $id = $this->idFromInput($request);
        try {
            if ((string) $request->input('action') === 'eliminar_cultivo') {
                $this->cultivos->delete($id);
            } elseif ((string) $request->input('action') === 'eliminar_lote') {
                if (!$this->repository->deleteLot($id)) {
                    throw new NotFoundException('El lote ya no existe.');
                }
            } else {
                throw new ValidationException(['action' => ['Acción no reconocida.']]);
            }
            return $this->json(['success' => true, 'message' => 'Registro eliminado correctamente.']);
        } catch (ValidationException|NotFoundException $exception) {
            return $this->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function cropDetail(Request $request): Response
    {
        $row = $this->repository->cropDetail($this->routeId($request));
        return $this->render(__DIR__ . '/../Views/crop-detail.php', ['row' => $row]);
    }

    public function lotDetail(Request $request): Response
    {
        $row = $this->repository->lotDetail($this->routeId($request));
        return $this->render(__DIR__ . '/../Views/lot-detail.php', ['row' => $row]);
    }

    public function lotHistory(Request $request): Response
    {
        return $this->render(__DIR__ . '/../Views/lot-history.php', [
            'rows' => $this->repository->lotHistory($this->routeId($request)),
        ]);
    }

    private function routeId(Request $request): int
    {
        $id = filter_var($request->route('id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new ValidationException(['id' => ['Identificador inválido.']]);
        }
        return (int) $id;
    }

    private function idFromInput(Request $request): int
    {
        $candidate = $request->input('id_cultivo', $request->input('id_lote'));
        $id = filter_var($candidate, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new ValidationException(['id' => ['Identificador inválido.']]);
        }
        return (int) $id;
    }
}
