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
}
