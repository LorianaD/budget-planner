<?php
// src/Util/DecimalFormatter.php

namespace App\Util;

// Formats a validated decimal string the same way MySQL returns a DECIMAL(10,2),
// so the API answers "60.00" both right after a save and when reading it again
final class DecimalFormatter
{
    // Works on the text only: going through a float could introduce rounding errors
    public static function withTwoDecimals(string $value): string
    {
        $isNegative = str_starts_with($value, '-');
        $unsignedValue = ltrim($value, '-');

        $parts = explode('.', $unsignedValue);
        $integerPart = self::removeLeadingZeros($parts[0]);

        $decimalPart = '';
        if (count($parts) === 2) {
            $decimalPart = $parts[1];
        }
        $decimalPart = str_pad($decimalPart, 2, '0');

        $formatted = $integerPart . '.' . $decimalPart;

        // "-0" and "-0.00" are stored as 0.00 by MySQL
        if ($isNegative && $formatted !== '0.00') {
            return '-' . $formatted;
        }

        return $formatted;
    }

    private static function removeLeadingZeros(string $integerPart): string
    {
        $trimmed = ltrim($integerPart, '0');

        if ($trimmed === '') {
            return '0';
        }

        return $trimmed;
    }
}
