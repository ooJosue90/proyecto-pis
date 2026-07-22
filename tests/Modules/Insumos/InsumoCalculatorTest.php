<?php
declare(strict_types=1);
namespace Tests\Modules\Insumos;
use App\Modules\Insumos\Services\InsumoCalculator;
use PHPUnit\Framework\TestCase;
final class InsumoCalculatorTest extends TestCase
{
    public function testQuantitiesScaleWithArea():void{$items=(new InsumoCalculator())->calculate(2);self::assertCount(14,$items);self::assertSame(334.0,(float)$items[0]['cantidad_total']);}
    public function testInvalidAreaReturnsNoRecommendations():void{self::assertSame([],(new InsumoCalculator())->calculate(0));}
}
