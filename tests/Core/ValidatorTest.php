<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Validator;
use App\Shared\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testItAcceptsValidCultivoInput(): void
    {
        $data = ['tipo' => 'Mango', 'fecha_siembra' => '2026-07-21'];
        $validated = (new Validator())->validate($data, [
            'tipo' => 'required|max_length:150',
            'fecha_siembra' => 'required|date',
        ]);

        self::assertSame($data, $validated);
    }

    public function testItRejectsInvalidDatesAndRequiredValues(): void
    {
        $this->expectException(ValidationException::class);
        (new Validator())->validate(['tipo' => '', 'fecha_siembra' => '2026-02-30'], [
            'tipo' => 'required',
            'fecha_siembra' => 'required|date',
        ]);
    }

    public function testItRejectsDatesOutsideBusinessBounds(): void
    {
        foreach (['2026-05-17', date('Y-m-d', strtotime('+1 day'))] as $date) {
            try {
                (new Validator())->validate(['fecha' => $date], [
                    'fecha' => 'required|date|date_min:2026-05-18|date_max:today',
                ]);
                self::fail("La fecha {$date} debió ser rechazada.");
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('fecha', $exception->errors());
            }
        }
    }

    public function testItAcceptsOnlyPlainDecimalsWithAtMostTwoPlaces(): void
    {
        $validator = new Validator();
        self::assertSame(
            ['price' => '1234.50'],
            $validator->validate(['price' => '1234.50'], ['price' => 'required|decimal:2|min:0.01'])
        );

        foreach (['-1', '1,000.00', '12.345', '2e3', '$10'] as $value) {
            try {
                $validator->validate(['price' => $value], ['price' => 'required|decimal:2|min:0.01']);
                self::fail("El valor {$value} debió ser rechazado.");
            } catch (ValidationException $exception) {
                self::assertArrayHasKey('price', $exception->errors());
            }
        }
    }
}
