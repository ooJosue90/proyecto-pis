<?php

declare(strict_types=1);

namespace App\Core;

use App\Shared\Exceptions\AuthenticationException;
use App\Shared\Exceptions\HttpException;
use App\Shared\Exceptions\ValidationException;
use ErrorException;
use Throwable;

final class ExceptionHandler
{
    public function __construct(
        private readonly string $environment,
        private readonly bool $debug,
        private readonly string $logDirectory
    ) {
    }

    public function register(): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        set_exception_handler(fn (Throwable $exception) => $this->render($exception));
    }

    public function render(Throwable $exception, ?Request $request = null): void
    {
        $this->log($exception);
        $status = $exception instanceof HttpException ? $exception->statusCode() : 500;
        $request ??= Request::capture();

        if ($exception instanceof AuthenticationException && !$request->expectsJson()) {
            Response::redirect(Url::route('/login'))->send();
            return;
        }

        $message = ($this->debug || $exception instanceof HttpException)
            ? $exception->getMessage()
            : 'Ocurrió un error interno. Inténtelo nuevamente.';

        if ($request->expectsJson()) {
            $payload = ['ok' => false, 'message' => $message];
            if ($exception instanceof ValidationException) {
                $payload['errors'] = $exception->errors();
            }
            Response::json($payload, $status)->send();
            return;
        }

        $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $details = $this->debug
            ? '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>'
            : '';
        (new Response(
            "<!doctype html><html lang=\"es\"><meta charset=\"utf-8\"><title>Error</title>"
            . "<body><main><h1>Error {$status}</h1><p>{$safeMessage}</p>{$details}</main></body></html>",
            $status,
            ['Content-Type' => 'text/html; charset=utf-8']
        ))->send();
    }

    private function log(Throwable $exception): void
    {
        if (!is_dir($this->logDirectory)) {
            mkdir($this->logDirectory, 0775, true);
        }

        $line = sprintf(
            "[%s] %s: %s in %s:%d%s%s%s",
            date('c'),
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            PHP_EOL,
            $exception->getTraceAsString(),
            PHP_EOL
        );
        error_log($line, 3, $this->logDirectory . '/app.log');
    }
}
