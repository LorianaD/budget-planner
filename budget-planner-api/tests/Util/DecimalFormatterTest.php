<?php

namespace App\Tests\Util;

use App\Util\DecimalFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecimalFormatterTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function valueProvider(): array
    {
        return [
            'entier' => ['60', '60.00'],
            'une décimale' => ['12.5', '12.50'],
            'déjà deux décimales' => ['12.50', '12.50'],
            'zéro' => ['0', '0.00'],
            'zéros inutiles devant' => ['007.5', '7.50'],
            'moins d\'un euro' => ['0.05', '0.05'],
            'négatif' => ['-120.3', '-120.30'],
            'moins zéro' => ['-0', '0.00'],
            'plus grand montant' => ['99999999.99', '99999999.99'],
        ];
    }

    #[DataProvider('valueProvider')]
    public function testValueIsFormattedWithTwoDecimals(string $value, string $expected): void
    {
        self::assertSame($expected, DecimalFormatter::withTwoDecimals($value));
    }
}
