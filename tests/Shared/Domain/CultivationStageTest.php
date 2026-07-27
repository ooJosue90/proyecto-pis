<?php

declare(strict_types=1);

namespace Tests\Shared\Domain;

use App\Shared\Domain\CultivationStage;
use PHPUnit\Framework\TestCase;

final class CultivationStageTest extends TestCase
{
    public function testCanonicalLabelsAreUnique(): void
    {
        $labels = CultivationStage::labels();

        self::assertSame(['Siembra', 'Riego', 'Cosecha', 'Sin etapa'], array_values($labels));
        self::assertCount(count($labels), array_unique($labels));
    }

    public function testLegacyNamesResolveToTheCanonicalIrrigationStage(): void
    {
        self::assertSame(CultivationStage::IRRIGATION, CultivationStage::fromName('Riego'));
        self::assertSame(CultivationStage::IRRIGATION, CultivationStage::fromName(' desarrollo '));
        self::assertSame('Riego', CultivationStage::normalizeName('CRECIMIENTO'));
    }

    public function testUnknownStoredTypesArePreserved(): void
    {
        self::assertSame('Fertilización', CultivationStage::normalizeName(' Fertilización '));
    }

    public function testStageSelectionMustBeCumulative(): void
    {
        self::assertTrue(CultivationStage::isSequentialSelection(true, true, true));
        self::assertTrue(CultivationStage::isSequentialSelection(true, false, false));
        self::assertFalse(CultivationStage::isSequentialSelection(false, true, false));
        self::assertFalse(CultivationStage::isSequentialSelection(true, false, true));
    }

    public function testCurrentStageComesFromTheHighestCompletedStage(): void
    {
        self::assertSame(
            CultivationStage::IRRIGATION,
            CultivationStage::currentFromSelection(true, true, false)
        );
    }

    public function testStatesForCurrentStageKeepFutureStagesPending(): void
    {
        $states = CultivationStage::statesForCurrent(CultivationStage::IRRIGATION);

        self::assertSame(CultivationStage::STATUS_COMPLETED, $states[CultivationStage::PLANTING]);
        self::assertSame(CultivationStage::STATUS_IN_PROGRESS, $states[CultivationStage::IRRIGATION]);
        self::assertSame(CultivationStage::STATUS_PENDING, $states[CultivationStage::HARVEST]);
        self::assertTrue(CultivationStage::canAccess(
            CultivationStage::PLANTING,
            CultivationStage::IRRIGATION,
            $states
        ));
        self::assertFalse(CultivationStage::canAccess(
            CultivationStage::HARVEST,
            CultivationStage::IRRIGATION,
            $states
        ));
    }
}
