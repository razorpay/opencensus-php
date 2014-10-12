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

        $this->setupPublicBasicAuthParams();
    }

    /**
     * @group testReponseTimeOut
     * @group testFailure
     */
    public function testCardTimeout()
    {
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testCC
     */
    public function testCreditCardSuccess()
    {
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCreditCardAuthNotAvailable1()
    {
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCreditCardAuthNotAvailable2()
    {
        $this->startTest([3]);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testSignatureFailure1()
    {
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testSignatureFailure2()
    {
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess1()
    {
        $this->startTest([6]);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess2()
    {
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess3()
    {
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseParesNotSuccess
     */
    public function testParesNotSuccess()
    {
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testDebitCardAuthNotAvailable1()
    {
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testDebitCardAuthNotAvailable2()
    {
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess4()
    {
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
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
