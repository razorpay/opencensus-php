<?php

namespace RZP\Tests\Unit\Utility;

use RZP\Tests\TestCase;

class AmountFormatINTest extends TestCase
{
    /**
     * @param  string $expected
     * @param  int    $number
     *
     * @dataProvider getAmountFormatINTestData
     */
    public function testAmountFormatIN(string $expected, int $number)
    {
        $this->assertSame($expected, amount_format_IN($number));
    }

    public function getAmountFormatINTestData(): array
    {
        return [
            // [Expected]         [Amount in paise]
            ['0.00',            0],
            ['1.23',            123],
            ['1,23,456.78',     12345678],
            ['1,23,45,678.90',  1234567890],

            ['-1.23',           -123],
            ['-1,23,456.78',    -12345678],
            ['-1,23,45,678.90', -1234567890],
        ];
    }
}
