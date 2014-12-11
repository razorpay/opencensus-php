<?php

namespace Tests\Functional\HdfcGateway;

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase payments are used, also tests if payments are automatically
 * captured on successful payments. Hold Payments are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use Tests\Functional\TestCase;
use Tests\Functional\Payment\PaymentAuthFlowTrait;

class HdfcGatewayAuthTest extends TestCase
{
    use PaymentAuthFlowTrait;

    protected $testData = array();

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/../Payment/helpers/cards.php';

        parent::setUp();

        $gateway = \Config::get('gateway.default');

        $this->ba->publicAuth();
    }

    public function testCardTimeout()
    {
        $this->startTest();
    }

    public function testCreditCardSuccess()
    {
        $this->startTest();
    }

    public function testCreditCardAuthNotAvailable1()
    {
        $this->startTest();
    }

    public function testCreditCardAuthNotAvailable2()
    {
        $this->startTest([3]);
    }

    public function testSignatureFailure1()
    {
        $this->startTest();
    }

    public function testSignatureFailure2()
    {
        $this->startTest();
    }

    public function testDebitCardSuccess1()
    {
        $this->startTest([6]);
    }

    public function testDebitCardSuccess2()
    {
        $this->startTest();
    }

    public function testDebitCardSuccess3()
    {
        $this->startTest();
    }

    public function testParesNotSuccess()
    {
        $this->startTest();
    }

    public function testDebitCardAuthNotAvailable1()
    {
        $this->startTest();
    }

    public function testDebitCardAuthNotAvailable2()
    {
        $this->startTest();
    }

    public function testDebitCardSuccess4()
    {
        $this->startTest();
    }

    public function testDebitCardSuccess5()
    {
        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $testData = $this->testData[$func];

        $this->replaceDefualtValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData);
    }
}
