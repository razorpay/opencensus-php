<?php

namespace RZP\Tests\Unit\Utility;

use RZP\Tests\TestCase;
use RZP\Models\Base\Utility;


class BaseUtilityTest extends TestCase
{
    /**
     * @dataProvider getGetAmountComponentsTestData
     */
    public function testGetAmountComponents($expected, array $payment)
    {
        $actual = Utility::getAMountComponents(
            $payment['amount'],
            $payment['currency']);

        $this->assertSame($expected, $actual);
    }

    public function getGetAmountComponentsTestData(): array
    {
        return [
            [['₹', '10', '00'], ['amount' => '1000', 'currency' => 'INR']],
            [['₹', '1,000', '45'], ['amount' => '100045', 'currency' => 'INR']],
            [['₹', '10,099', '12'], ['amount' => '1009912', 'currency' => 'INR']],
            [['₹', '0', '00'], ['amount' => '0', 'currency' => 'INR']],
            [['$', '1,00,000', '00'], ['amount' => '10000000', 'currency' => 'USD']],
            [['$', '0', '00'], ['amount' => '0', 'currency' => 'USD']],
            [['$', '10', '00'], ['amount' => '1000', 'currency' => 'USD']],
        ];
    }

    /**
     * @dataProvider getGetTimestampFormattedData
     */
    public function testGetTimestampFormatted(string $expected, int $epoch, string $format)
    {
        $this->assertSame(
            $expected,
            Utility::getTImestampFormatted($epoch, $format));
    }

    public function getGetTimestampFormattedData(): array
    {
        return [
            [date('jS M, Y', 1), 1, 'jS M, Y'],
            [date('jS M, Y', 10000), 10000, 'jS M, Y'],
            [date('jS M, Y', 1000000), 1000000, 'jS M, Y'],
            [date('jS M, Y', 1621880999), 1621880999, 'jS M, Y'],
            [date('D, d/j/Y', 1621880999), 1621880999, 'D, d/j/Y'],
        ];
    }
}
