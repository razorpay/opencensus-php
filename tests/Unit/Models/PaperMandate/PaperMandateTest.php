<?php

namespace RZP\Tests\Unit\Models\PaperMandate;

use RZP\Tests\Functional\TestCase;
use RZP\Models\PaperMandate\HyperVerge;

class PaperMandateTest extends TestCase
{
    public function testFormatAmountToWords()
    {
        $hyperVerge = new HyperVerge;

        self::assertEquals('one rupees', $hyperVerge->getAmountInWords(100));
        self::assertEquals('twenty rupees and fifty paise', $hyperVerge->getAmountInWords(2050));
        self::assertEquals('three hundred sixty rupees and eighty paise', $hyperVerge->getAmountInWords(36080));
        self::assertEquals('four thousand eight hundred eight rupees', $hyperVerge->getAmountInWords(480800));
        self::assertEquals('fifty thousand nine hundred seven rupees', $hyperVerge->getAmountInWords(5090700));
        self::assertEquals('six lakh nine thousand ninety rupees and ninety paise', $hyperVerge->getAmountInWords(60909090));
        self::assertEquals('seventy lakh eight thousand eight rupees', $hyperVerge->getAmountInWords(700800800));
        self::assertEquals('eight crore seventy thousand seventy rupees and seventy paise', $hyperVerge->getAmountInWords(8007007070));
        self::assertEquals('ninety crore twelve thousand three hundred forty nine rupees and eighty seven paise', $hyperVerge->getAmountInWords(90001234987));
    }
}
