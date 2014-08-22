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

    protected $txn = null;

    public function setUp()
    {
        parent::setUp();

        $this->testData = include(__DIR__.'/helpers/capture.php');

        $this->txn = $this->createAuthorizedTransactionEntity();

        $this->setupPrivateBasicAuthParams();
    }

    public function testCapture()
    {
        $this->startTest();
    }

    public function testCaptureTwice()
    {
        $txn = $this->txn;

        $txn = $this->captureTransaction($txn['id'], $txn['amount']);

        $this->txn = $txn;

        $this->startTest();
    }

    public function testCaptureWithLessAmountThanAuth()
    {
        $amount = 10000;

        $this->startTest(null, $amount);
    }

    public function testCaptureWithMoreAmountThanAuth()
    {
        $amount = $this->txn['amount'] + 1000;

        $this->startTest(null, $amount);
    }

    public function testCaptureWithNoAmount()
    {
        unset($this->txn['amount']);

        $this->startTest();
    }

    public function testCaptureWithZeroAmount()
    {
        $this->txn['amount'] = 0;

        $this->startTest();
    }

    public function testCaptureWithMinAmountAllowedMinusOne()
    {
        //
        // Minium amount allowed for capture
        //
        $this->txn['amount'] = 99;

        $this->startTest();
    }

    public function testCaptureWithMinAmountAllowed()
    {
        $this->txn['amount'] = 100;

        $this->startTest();
    }

    public function testCaptureWithOverflowingAmount()
    {
        $this->txn['amount'] = 100000000000000000000000000000000000;

        $this->startTest();
    }

    public function testCaptureWithNegativeAmount()
    {
        $this->txn['amount'] = -10000;

        $this->startTest();
    }

    public function testCaptureWithRandomId()
    {
        $this->txn['id'] = '2fe34ae575104c0a95c3';

        $this->startTest();
    }

    public function testCaptureWithRefunded()
    {
        $txn = $this->txn;

        $txn = $this->captureTransaction($txn['id'], $txn['amount']);

        $txn = $this->refundTransaction($txn['id']);

        $this->txn = $txn;

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
            $id = $this->txn['id'];

        if ($amount === null)
        {
            if (isset($this->txn['amount']))
                $amount = $this->txn['amount'];
        }
    }
}