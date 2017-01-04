<?php

namespace RZP\Tests\Functional\Currency;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ExchangeTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ExchangeTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testExchangeRatesLatest()
    {
        $this->startTest();
    }
}
