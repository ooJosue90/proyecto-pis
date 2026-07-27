<?php

declare(strict_types=1);

namespace App\Shared\Support;

use JsonException;

final class ActionGuidance
{
    private const TYPES = ['success', 'info', 'warning', 'danger'];

    public static function encode(
        string $title,
        string $message,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
        string $type = 'info',
        string $icon = 'fa-arrow-right'
    ): string {
        return json_encode([
            'type' => in_array($type, self::TYPES, true) ? $type : 'info',
            'title' => trim($title),
            'message' => trim($message),
            'action_label' => self::nullableTrim($actionLabel),
            'action_url' => self::nullableTrim($actionUrl),
            'icon' => trim($icon) ?: 'fa-arrow-right',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string, string|null>|null */
    public static function decode(?string $payload): ?array
    {
        if ($payload === null || trim($payload) === '') {
            return null;
        }

        try {
            $guidance = json_decode($payload, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($guidance)
            || !is_string($guidance['title'] ?? null)
            || !is_string($guidance['message'] ?? null)
            || trim($guidance['title']) === ''
            || trim($guidance['message']) === '') {
            return null;
        }

        $type = is_string($guidance['type'] ?? null) && in_array($guidance['type'], self::TYPES, true)
            ? $guidance['type']
            : 'info';

        return [
            'type' => $type,
            'title' => trim($guidance['title']),
            'message' => trim($guidance['message']),
            'action_label' => self::nullableTrim($guidance['action_label'] ?? null),
            'action_url' => self::nullableTrim($guidance['action_url'] ?? null),
            'icon' => is_string($guidance['icon'] ?? null) && trim($guidance['icon']) !== ''
                ? trim($guidance['icon'])
                : 'fa-arrow-right',
        ];
    }

    private static function nullableTrim(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
