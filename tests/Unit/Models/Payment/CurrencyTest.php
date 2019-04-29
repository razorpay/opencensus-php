<?php

namespace RZP\Tests\Unit\Models\Payment;

use RZP\Models\Currency\Currency;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\CustomAssertions;

class CurrencyTest extends TestCase
{
    use CustomAssertions;

    public function testCurrencyData()
    {
        $supportedCurrencies = Currency::SUPPORTED_CURRENCIES;

        $this->assertArrayKeysExist(Currency::ISO_NUMERIC_CODES, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency::DENOMINATION_FACTOR, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency::MIN_VALUE, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency::MIN_AUTH_VALUE, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency::SYMBOL, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency::NAME, $supportedCurrencies);
    }

    public function testSupportedCurrency()
    {
        $inrSupported = Currency::isSupportedCurrency('INR');

        $this->assertTrue($inrSupported);

        $xyzSupported = Currency::isSupportedCurrency('XYZ');

        $this->assertFalse($xyzSupported);
    }
}
