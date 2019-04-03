<?php

namespace RZP\Tests\Functional\Currency;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class CurrencyTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/CurrencyTestData.php';

        parent::setUp();
    }

    public function testCurrencyRatesLatest()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testGetCurrencyRates()
    {
        // set the rates in redis
        $this->testCurrencyRatesLatest();

        $this->ba->adminAuth();

        // fetch current rates
        $this->startTest();
    }

    public function testGetPaymentCurrencies()
    {
        $this->ba->publicAuth();

        $res = $this->startTest();

        $this->assertArrayHasKey('INR', $res);

        $this->assertArrayHasKey('code', $res['INR']);

        $this->assertArrayHasKey('min_value', $res['INR']);

        $this->assertArrayHasKey('min_auth_value', $res['INR']);

        $this->assertArrayHasKey('denomination', $res['INR']);

        $this->assertArrayHasKey('symbol', $res['INR']);
    }
}
