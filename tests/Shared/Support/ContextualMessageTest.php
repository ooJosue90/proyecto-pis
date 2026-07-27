<?php

declare(strict_types=1);

namespace Tests\Shared\Support;

use App\Shared\Support\ContextualMessage;
use PHPUnit\Framework\TestCase;

final class ContextualMessageTest extends TestCase
{
    public function testItBuildsAConsistentActionableMessage(): void
    {
        $message = ContextualMessage::make(
            ' Farmer Next Step ',
            'warning',
            ' Próxima etapa ',
            ' Continúe con Riego. ',
            ' Abrir lote ',
            '/lotes/8',
            'water_drop'
        );

        self::assertSame('farmer-next-step', $message['id']);
        self::assertSame('warning', $message['type']);
        self::assertSame('Próxima etapa', $message['title']);
        self::assertSame('Continúe con Riego.', $message['message']);
        self::assertSame('Abrir lote', $message['action_label']);
        self::assertSame('/lotes/8', $message['action_url']);
        self::assertSame('water_drop', $message['icon']);
    }

    public function testItFallsBackToInformationForUnknownTypes(): void
    {
        $message = ContextualMessage::make('notice', 'other', 'Aviso', 'Mensaje');

        self::assertSame('info', $message['type']);
        self::assertNull($message['action_label']);
        self::assertNull($message['action_url']);
    }
}
