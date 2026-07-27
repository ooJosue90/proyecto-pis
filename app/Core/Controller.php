<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function render(string $view, array $data = [], int $status = 200): Response
    {
        $resolved = realpath($view);
        $appRoot = realpath(dirname(__DIR__));
        if ($resolved === false || $appRoot === false || !str_starts_with($resolved, $appRoot)) {
            throw new RuntimeException('La vista solicitada no es válida.');
        }

        $content = (static function (string $resolvedView, array $viewData): string {
            extract($viewData, EXTR_SKIP);
            ob_start();
            require $resolvedView;
            return (string) ob_get_clean();
        })($resolved, $data);

        return new Response($content, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /** @param array<string, mixed> $data */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $location): Response
    {
        return Response::redirect($location);
    }
}
