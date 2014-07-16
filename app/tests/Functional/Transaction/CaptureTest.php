<?php

namespace Tests\Functional\Transaction;

use Tests\Functional\TestCase;

/**
 * Tests that support transactions (capture/refund) are working fine.
 * creates a hold transaction using card 13 and then attempts to capture it followed by refund it
 * Is successful if captured successfully folowed by successful refund.
 * All test cases follow, GIVEN, WHEN, THEN structure
 */

class CaptureTest extends TestCase
{
    use TransactionAuthFlowTrait;

    protected $testData = null;

    protected $authTxn = null;

    public function setUp()
    {
        parent::setUp();

        $this->testData = include(__DIR__.'/helpers/capture.php');

        $this->authTxn = $this->defaultAuthTransaction();
    }

    /**
     * Tests the support transactions, calls capture & refund
     * @group testSupport
     * @group testCapture
     * @group testRefund
     */
    public function testCapture()
    {
        $this->startTest();
    }

    public function testCaptureTwice()
    {
        $authTxn = $this->authTxn;

        $txn = $this->captureTransaction($authTxn['id'], $authTxn['amount']);

        $this->authTxn = $txn;

        $this->startTest();
    }

    public function testCaptureWithLessAmountThanAuth()
    {
        $amount = 10000;

        $this->startTest(null, $amount);
    }

    public function testCaptureWithMoreAmountThanAuth()
    {
        $amount = $this->authTxn['amount'] + 1000;

        $this->startTest(null, $amount);
    }

    public function testCaptureWithNoAmount()
    {
        unset($this->authTxn['amount']);

        $this->startTest();
    }

    public function testCaptureWithZeroAmount()
    {
        $this->authTxn['amount'] = 0;

        $this->startTest();
    }

    public function testCaptureWithMinAmountAllowedMinusOne()
    {
        //
        // Minium amount allowed for capture
        //
        $this->authTxn['amount'] = 99;

        $this->startTest();
    }

    public function testCaptureWithMinAmountAllowed()
    {
        $this->authTxn['amount'] = 100;

        $this->startTest();
    }

    public function testCaptureWithOverflowingAmount()
    {
        $this->authTxn['amount'] = 100000000000000000000000000000000000;

        $this->startTest();
    }

    public function testCaptureWithNegativeAmount()
    {
        $this->authTxn['amount'] = -10000;

        $this->startTest();
    }

    public function testCaptureWithRandomId()
    {
        $this->authTxn['id'] = '2fe34ae575104c0a95c3';

        $this->startTest();
    }

    public function testCaptureWithRefunded()
    {
        $authTxn = $this->authTxn;

        $txn = $this->captureTransaction($authTxn['id'], $authTxn['amount']);

        $txn = $this->refundTransaction($txn['id']);

        $this->authTxn = $txn;

        $this->startTest();
    }

    public function startTest($id = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $func = $trace[1]['function'];

        $name = lcfirst(substr($func, 4));

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $id, $amount);

        $this->runRequestResponseFlow($testData);
    }

    protected function setRequestData(& $request, $id = null, $amount = null)
    {
        $this->checkAndSetIdAndAmount($id, $amount);

        $request['content']['amount'] = $amount;

        $url = '/transactions/'.$id.'/capture';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function checkAndSetIdAndAmount(& $id = null, & $amount = null)
    {
        if ($id === null)
            $id = $this->authTxn['id'];

        if ($amount === null)
        {
            if (isset($this->authTxn['amount']))
                $amount = $this->authTxn['amount'];
        }
    }
}