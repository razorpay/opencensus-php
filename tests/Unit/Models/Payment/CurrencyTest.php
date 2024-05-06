<?php

namespace RZP\Tests\Unit\Models\Payment;

use RZP\Models\Currency;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\CustomAssertions;

class CurrencyTest extends TestCase
{
    use CustomAssertions;
    use PaymentTrait;

    public function testCurrencyData()
    {
        $supportedCurrencies = (new Currency\Core)->getSupportedCurrencies();

        $this->assertArrayKeysExist(Currency\Currency::ISO_NUMERIC_CODES, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency\Currency::DENOMINATION_FACTOR, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency\Currency::MIN_VALUE, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency\Currency::MIN_AUTH_VALUE, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency\Currency::SYMBOL, $supportedCurrencies);

        $this->assertArrayKeysExist(Currency\Currency::NAME, $supportedCurrencies);
    }

    public function testSupportedCurrency()
    {
        $inrSupported = Currency\Currency::isSupportedCurrency('INR');

        $this->assertTrue($inrSupported);

        $xyzSupported = Currency\Currency::isSupportedCurrency('XYZ');

        $this->assertFalse($xyzSupported);
    }

    public function testGetSupportedCurrencies()
    {
        $this->mockDCSResponse();
        $BHDSupported = Currency\Currency::isSupportedCurrency('BHD');
        $this->assertFalse($BHDSupported);
        $INRSupported = Currency\Currency::isSupportedCurrency('INR');
        $this->assertTrue($INRSupported);
    }
    private function mockDCSResponse()
    {
        $dcsConfigService = $this->getMockBuilder(DcsConfigService::class)
            ->setConstructorArgs([$this->app])
            ->getMock();
        $this->app->instance('dcs_config_service', $dcsConfigService);
        $this->app['dcs_config_service']->method('fetchConfiguration')->
        willReturn(['disabled_currencies' => array('BHD','KWD')]);
    }

}
