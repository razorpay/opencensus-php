<?php

namespace Tests\Functional\Transaction;

use Tests\Functional\TestCase;

/**
 * Tests for capture transactions
 *
 * For capture transactions, first we need to create an
 * authorized transaction. By default, an authorized txn entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So capture tests which supposedly hit hdfc gateway for capture,
 * should first call for a normal hdfc authorized transaction instead
 * of utilizing the default created transaction entity.
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
        $this->txn = $this->defaultAuthTransaction();

        $this->setupPrivateBasicAuthParams();

        $this->startTest();
    }

    public function testCaptureTwice()
    {
        $txn = $this->txn;

        $this->txn = $this->defaultAuthTransaction();

        $txn = $this->captureTransaction($txn['id'], $txn['amount']);

        $this->txn = $txn;

        $this->startTest();
    }

    public function testCaptureWithLessAmountThanAuth()
    {
        $amount = 10000;

        $this->txn = $this->defaultAuthTransaction();

        $this->setupPrivateBasicAuthParams();

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
        $this->txn = $this->defaultAuthTransaction();

        $this->txn['amount'] = 100;

        $this->setupPrivateBasicAuthParams();
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

    public function testCaptureAfterRefund()
    {
        $txn = $this->defaultAuthTransaction();

        $txn = $this->captureTransaction($txn['id'], $txn['amount']);

        $txn = $this->refundTransaction($txn['id']);

        $this->txn = $txn;

        $this->startTest();
    }

    public function startTest($id = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

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