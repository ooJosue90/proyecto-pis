<?php

declare(strict_types=1);

namespace Tests\Shared;

use App\Shared\Support\ActionGuidance;
use PHPUnit\Framework\TestCase;

final class ActionGuidanceTest extends TestCase
{
    public function testEncodesAndDecodesAContextualNextStep(): void
    {
        $guidance = ActionGuidance::decode(ActionGuidance::encode(
            'Siguiente paso',
            'Registre el lote asociado.',
            'Ir a Lotes',
            '#lote',
            'success',
            'fa-location-dot'
        ));

        self::assertSame('success', $guidance['type']);
        self::assertSame('Siguiente paso', $guidance['title']);
        self::assertSame('Registre el lote asociado.', $guidance['message']);
        self::assertSame('Ir a Lotes', $guidance['action_label']);
        self::assertSame('#lote', $guidance['action_url']);
    }

    public function testRejectsMalformedPayloads(): void
    {
        self::assertNull(ActionGuidance::decode(null));
        self::assertNull(ActionGuidance::decode('{invalid'));
        self::assertNull(ActionGuidance::decode('{"title":"","message":"Texto"}'));
    }
}
