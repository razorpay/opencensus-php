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

        $this->ba->appAuth();
    }

    public function testCurrencyRatesLatest()
    {
        $this->startTest();
    }

    public function testGetCurrencyRates()
    {
        // set the rates in redis
        $this->testCurrencyRatesLatest();

        // fetch current rates
        $this->startTest();
    }
}
