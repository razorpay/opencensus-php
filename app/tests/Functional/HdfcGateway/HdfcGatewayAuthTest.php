<?php

namespace Tests\Functional\HdfcGateway;

/**
 * Tests all cards in cards.php to ensure they return expected response,
 * Purchase transactions are used, also tests if transactions are automatically
 * captured on successful transactions. Hold Transactions are tested in support test
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

use Tests\Functional\TestCase;
use Tests\Functional\Transaction\TransactionAuthFlowTrait;

class HdfcGatewayAuthTest extends TestCase
{
    use TransactionAuthFlowTrait;

    protected $testData = array();

    public function setUp()
    {
        parent::setUp();

        $gateway = \Config::get('gateway.default');

        if ($gateway !== 'hdfc')
        {
            $this->markTestSkipped('Hdfc gateway auth tests are disabled');
        }

        //
        // load test data
        //
        $this->testData = include(__DIR__.'/../Transaction/helpers/cards.php');
    }

    /**
     * @group testReponseTimeOut
     * @group testFailure
     */
    public function testCardTimeout()
    {
        echo "\nTesting: CC Transaction \n";
        echo "Expected Response: Timeout Transaction \n";
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testCC
     */
    public function testCreditCardSuccess()
    {
        echo "\nTesting: CC Transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCreditCardAuthNotAvailable1()
    {
        echo "\nTesting: CC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testCC
     * @group testResponseAuthNotAvailable
     */
    public function testCreditCardAuthNotAvailable2()
    {
        echo "\nTesting: CC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->startTest([3]);
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testSignatureFailure1()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Signature Failure error. \n";
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseSignatureFailure
     */
    public function testSignatureFailure2()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Signature Failure error. \n";
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess1()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->startTest([6]);
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess2()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess3()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseParesNotSuccess
     */
    public function testParesNotSuccess()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Pares Not Success error. \n";
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testDebitCardAuthNotAvailable1()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->startTest();
    }

    /**
     * @group testFailure
     * @group testDC
     * @group testResponseAuthNotAvailable
     */
    public function testDebitCardAuthNotAvailable2()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Fails with Auth Not Available error. \n";
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess4()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Sucessfull Auth \n";
        $this->startTest();
    }

    /**
     * @group testSuccess
     * @group testDC
     */
    public function testDebitCardSuccess5()
    {
        echo "\nTesting: DC transaction \n";
        echo "Expected Response: Successful Auth \n";
        $this->startTest();
    }

    public function startTest()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $name = lcfirst(substr($func, 4));

        $testData = $this->testData[$name];

        $this->replaceDefualtValues($testData['request']['content']);

        $this->runRequestResponseFlow($testData);
    }
}
