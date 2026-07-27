<?php

declare(strict_types=1);

namespace App\Modules\Asistente\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Modules\Asistente\Services\ContextBuilder;
use App\Modules\Asistente\Services\GeminiService;
use App\Modules\Asistente\Services\PermissionFilter;
use App\Shared\Exceptions\HttpException;
use App\Shared\Exceptions\ValidationException;

final class ChatController extends Controller
{
    public function __construct(
        private readonly PermissionFilter $permissions,
        private readonly ContextBuilder $context,
        private readonly GeminiService $gemini,
        private readonly Auth $auth,
        private readonly Csrf $csrf,
        private readonly Session $session
    ) {
    }

    public function chat(Request $request): Response
    {
        try {
            $this->csrf->validate((string) $request->input('_token', ''));
            $question = trim((string) $request->input('pregunta', ''));
            if ($question === '' || mb_strlen($question) > 1000) {
                throw new ValidationException([
                    'pregunta' => ['Escriba una pregunta válida de hasta 1000 caracteres.'],
                ]);
            }

            $user = $this->auth->user();
            $role = (string) $user['rol'];
            $previousAnalysis = $this->session->get('ada_ultimo_analisis');
            $analysis = $this->permissions->analyze(
                $question,
                is_array($previousAnalysis) ? $previousAnalysis : null
            );

            if (!$this->permissions->authorized($role, $analysis)) {
                return $this->json([
                    'success' => false,
                    'ok' => false,
                    'rol' => $role,
                    'respuesta' => 'Esa información pertenece a un módulo no disponible para su perfil.',
                    'enlaces' => [],
                    'navegar_a' => null,
                ], 403);
            }

            $links = $this->links($role, $analysis['topics']);
            if ($this->navigation($question) && $links !== []) {
                $this->session->put('ada_ultimo_analisis', $analysis);
                return $this->json([
                    'success' => true,
                    'ok' => true,
                    'rol' => $role,
                    'respuesta' => 'Le llevo al módulo solicitado.',
                    'enlaces' => [$links[0]],
                    'navegar_a' => $links[0],
                ]);
            }

            $context = $this->context->build($role, (string) $user['id_usuario'], $analysis);
            $history = $this->history();
            $answer = $this->gemini->answer(
                $question,
                $context,
                $role,
                (string) ($user['nombre'] ?? 'Usuario'),
                $history
            );

            $history[] = ['question' => $question, 'answer' => $answer];
            $this->session->put('ada_historial', array_slice($history, -4));
            $this->session->put('ada_ultimo_analisis', $analysis);
            $this->session->put('ada_ultima_interaccion_fecha', date('Y-m-d H:i:s'));

            return $this->json([
                'success' => true,
                'ok' => true,
                'rol' => $role,
                'respuesta' => $answer,
                'enlaces' => $links,
                'navegar_a' => null,
            ]);
        } catch (ValidationException $exception) {
            return $this->json([
                'success' => false,
                'ok' => false,
                'rol' => $this->auth->role() ?? 'Invitado',
                'respuesta' => implode(' ', array_merge(...array_values($exception->errors()))),
                'enlaces' => [],
                'navegar_a' => null,
            ], 422);
        } catch (HttpException $exception) {
            return $this->json([
                'success' => false,
                'ok' => false,
                'rol' => $this->auth->role() ?? 'Invitado',
                'respuesta' => $exception->getMessage(),
                'enlaces' => [],
                'navegar_a' => null,
            ], $exception->statusCode());
        }
    }

    /** @return list<array{question:string,answer:string}> */
    private function history(): array
    {
        $history = $this->session->get('ada_historial', []);
        if (!is_array($history)) {
            return [];
        }

        return array_values(array_filter(
            $history,
            static fn (mixed $turn): bool => is_array($turn)
                && is_string($turn['question'] ?? null)
                && is_string($turn['answer'] ?? null)
        ));
    }

    private function navigation(string $question): bool
    {
        $text = mb_strtolower($question, 'UTF-8');
        return str_contains($text, 'llévame')
            || str_contains($text, 'llevame')
            || str_contains($text, 'abre el módulo')
            || str_contains($text, 'abre el modulo')
            || str_contains($text, 'ir a ');
    }

    private function links(string $role, array $topics): array
    {
        $admin = Url::route('/dashboard/admin');
        $farmer = Url::route('/dashboard/agricultor');
        $warehouse = Url::route('/dashboard/bodega');
        $base = [
            'Administrador' => [
                'usuarios' => ['Ir a Usuarios', $admin . '#usuarios'],
                'reportes' => ['Ir a Reportes', $admin . '#reportes'],
                'facturas' => ['Ir a Facturas', $admin . '#facturas'],
                'cultivos' => ['Ir a Cultivos', $admin . '#cultivos'],
                'lotes' => ['Ir a Lotes', $admin . '#cultivos'],
                'agricultura' => ['Ir a Monitoreo', $admin . '#cultivos'],
                'inventario' => ['Ir a Inventario', Url::route('/inventario')],
                'solicitudes' => ['Ir a Solicitudes', $admin . '#solicitudes'],
                'plagas' => ['Ir a Fitosanitario', Url::route('/plagas')],
            ],
            'Agricultor' => [
                'cultivos' => ['Ir a Cultivos', $farmer . '?tab=cultivo'],
                'lotes' => ['Ir a Lotes', $farmer . '?tab=lote'],
                'agricultura' => ['Ir a Monitoreo', $farmer . '?tab=lote'],
                'solicitudes' => ['Solicitar insumos', $farmer . '?tab=insumos'],
                'plagas' => ['Ir a Fitosanitario', Url::route('/plagas')],
                'produccion' => ['Ir a Producción', Url::route('/produccion')],
            ],
            'Bodeguero' => [
                'inventario' => ['Ir al Inventario', Url::route('/inventario')],
                'facturas' => ['Ir a Facturas', Url::route('/facturas/recepcion')],
                'solicitudes' => ['Ir a Solicitudes', $warehouse . '#warehouse-approved-requests'],
            ],
        ];

        $links = [];
        foreach ($topics as $topic) {
            if (isset($base[$role][$topic])) {
                [$label, $href] = $base[$role][$topic];
                $links[] = ['label' => $label, 'href' => $href, 'icon' => 'fas fa-arrow-right'];
                if (count($links) === 3) {
                    break;
                }
            }
        }

        return $links;
    }
}
