<?php

declare(strict_types=1);

namespace App\Shared\Support;

final class ContextualMessage
{
    private const TYPES = ['success', 'info', 'warning', 'danger'];

    /** @return array<string, mixed> */
    public static function make(
        string $id,
        string $type,
        string $title,
        string $message,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
        string $icon = 'lightbulb'
    ): array {
        $id = preg_replace('/[^a-z0-9._-]+/i', '-', trim($id)) ?: 'context-message';
        $type = in_array($type, self::TYPES, true) ? $type : 'info';

        return [
            'id' => strtolower(trim($id, '-')),
            'type' => $type,
            'title' => trim($title),
            'message' => trim($message),
            'action_label' => $actionLabel !== null ? trim($actionLabel) : null,
            'action_url' => $actionUrl !== null ? trim($actionUrl) : null,
            'icon' => trim($icon) ?: 'lightbulb',
        ];
    }
}
