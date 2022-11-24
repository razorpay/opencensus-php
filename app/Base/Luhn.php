<?php

namespace RZP\Base;

/**
 * Utility class for generating Luhn checksum and validating a number of any base
 *
 * Luhn algorithm is used to validate credit card numbers, IMEI numbers, and
 * National Provider Identifier numbers.
 *
 * @see http://en.wikipedia.org/wiki/Luhn_algorithm
 */
class Luhn
{
    const BASE = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    public static $baseValues = array(
        '0' => 0,
        '1' => 1,
        '2' => 2,
        '3' => 3,
        '4' => 4,
        '5' => 5,
        '6' => 6,
        '7' => 7,
        '8' => 8,
        '9' => 9,
        'A' => 10,
        'B' => 11,
        'C' => 12,
        'D' => 13,
        'E' => 14,
        'F' => 15,
        'G' => 16,
        'H' => 17,
        'I' => 18,
        'J' => 19,
        'K' => 20,
        'L' => 21,
        'M' => 22,
        'N' => 23,
        'O' => 24,
        'P' => 25,
        'Q' => 26,
        'R' => 27,
        'S' => 28,
        'T' => 29,
        'U' => 30,
        'V' => 31,
        'W' => 32,
        'X' => 33,
        'Y' => 34,
        'Z' => 35,
        'a' => 36,
        'b' => 37,
        'c' => 38,
        'd' => 39,
        'e' => 40,
        'f' => 41,
        'g' => 42,
        'h' => 43,
        'i' => 44,
        'j' => 45,
        'k' => 46,
        'l' => 47,
        'm' => 48,
        'n' => 49,
        'o' => 50,
        'p' => 51,
        'q' => 52,
        'r' => 53,
        's' => 54,
        't' => 55,
        'u' => 56,
        'v' => 57,
        'w' => 58,
        'x' => 59,
        'y' => 60,
        'z' => 61,
    );

    /**
     * @param string $number
     * @return int
     */
    private static function checksum($number, $base = 10)
    {
        $number = (string) $number;

        $length = strlen($number);

        $sum = 0;

        // Sum all the non-even digits from right directly
        for ($i = $length - 1; $i >= 0; $i -= 2)
        {
            $strDigit = $number[$i];
            $numDigit = self::$baseValues[$strDigit];

            $sum += $numDigit;
        }

        // Sum all the even digits from right after multiplying with two and
        // taking mod.
        for ($i = $length - 2; $i >= 0; $i -= 2)
        {
            $strDigit = $number[$i];
            $numDigit = self::$baseValues[$strDigit];

            $numDigit *= 2;

            if ($numDigit >= $base)
            {
                $numDigit = ($numDigit - $base) + 1;
            }

            $sum += $numDigit;
        }

        return $sum % $base;
    }

    /**
     * This is done in case we want to append the
     * luhn digit at any random place to make it
     * valid luhn
     *
     * @param string $part1
     * @param string $part2
     * @param string $base
     *
     * @return string
     */
    public static function computeCheckDigitWithPart(string $part1, string $part2, int $base = 10)
    {
        $checkDigit = self::checksum($part1 . '0' . $part2, $base);

        // For 0, it should be zero. For others, it should be base - digit.
        $checkDigit = ($base - $checkDigit) % $base;

        $checkDigit = self::BASE[$checkDigit];

        return $checkDigit;
    }

    /**
     * @param $partialNumber
     * @return string
     */
    public static function computeCheckDigit($partialNumber, $base = 10)
    {
        $checkDigit = self::checksum($partialNumber . '0', $base);

        // For 0, it should be zero. For others, it should be base - digit.
        $checkDigit = ($base - $checkDigit) % $base;

        $checkDigit = self::BASE[$checkDigit];

        return $checkDigit;
    }

    /**
     * Checks whether a number (partial number + check digit) is Luhn compliant
     *
     * @param string $number
     * @return bool
     */
    public static function isValid($number, $base = 10)
    {
        return (self::checksum($number, $base) === 0);
    }
}
