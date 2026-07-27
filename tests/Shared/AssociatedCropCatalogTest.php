<?php

declare(strict_types=1);

namespace Tests\Shared;

use App\Shared\Domain\AssociatedCropCatalog;
use PHPUnit\Framework\TestCase;

final class AssociatedCropCatalogTest extends TestCase
{
    public function testCatalogContainsTheApprovedAssociatedCrops(): void
    {
        $options = AssociatedCropCatalog::options();

        self::assertCount(12, $options);
        self::assertSame('Frijol (caupí, canavalia o común)', $options['frijol']);
        self::assertSame('Sorgo', $options['sorgo']);
    }

    public function testCatalogProvidesPresentationMetadataForEveryCrop(): void
    {
        $catalog = AssociatedCropCatalog::catalog();

        self::assertSame(array_keys(AssociatedCropCatalog::options()), array_keys($catalog));
        foreach ($catalog as $crop) {
            self::assertNotSame('', $crop['category']);
            self::assertNotSame('', $crop['description']);
            self::assertNotSame('', $crop['icon']);
        }
    }

    public function testSelectionIsNormalizedWithoutDuplicates(): void
    {
        self::assertSame(
            ['maiz', 'frijol'],
            AssociatedCropCatalog::normalizeSelection(['maiz', 'maiz', 'frijol', 'desconocido'])
        );
    }

    public function testDuplicateOrUnknownSelectionsAreInvalid(): void
    {
        self::assertFalse(AssociatedCropCatalog::isValidSelection(['maiz', 'maiz']));
        self::assertFalse(AssociatedCropCatalog::isValidSelection(['desconocido']));
        self::assertTrue(AssociatedCropCatalog::isValidSelection([]));
    }
}
