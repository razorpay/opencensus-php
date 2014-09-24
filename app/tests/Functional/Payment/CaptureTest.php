<?php

namespace Tests\Functional\Payment;

use Tests\Functional\TestCase;

/**
 * Tests for capture payments
 *
 * For capture payments, first we need to create an
 * authorized payment. By default, an authorized txn entity
 * is provided. However, it doesn't have a corresponding record
 * in hdfc gateway.
 *
 * So capture tests which supposedly hit hdfc gateway for capture,
 * should first call for a normal hdfc authorized payment instead
 * of utilizing the default created payment entity.
 */

class CaptureTest extends TestCase
{
    use PaymentAuthFlowTrait;

    protected $testData = null;

    protected $txn = null;

    public function setUp()
    {
        parent::setUp();

        $this->testData = require(__DIR__.'/helpers/capture.php');

        $txn = $this->fixtures->createPaymentAuthorizedEntity();
        $this->txn = $txn->toArrayPublic();

        $this->setupPrivateBasicAuthParams();
    }

    public function testCapture()
    {
        $this->txn = $this->defaultAuthPayment();

        $this->setupPrivateBasicAuthParams();

        $this->startTest();
    }

    public function testCaptureTwice()
    {
        $txn = $this->fixtures->createPaymentCapturedEntity()->toArrayPublic();

        $this->txn = $txn;

        $this->startTest();
    }

    public function testCaptureWithLessAmountThanAuth()
    {
        $amount = 10000;

        $this->txn = $this->defaultAuthPayment();

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
        $this->txn = $this->defaultAuthPayment();

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
        $txn = $this->defaultAuthPayment();

        $txn = $this->capturePayment($txn['id'], $txn['amount']);

        $refund = $this->refundPayment($txn['id']);

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

        $url = '/payments/'.$id.'/capture';

        $this->setRequestUrlAndMethod($request, $url, 'POST');
    }

    protected function checkAndSetIdAndAmount(& $id = null, & $amount = null)
    {
        if ($id === null)
        {
            $id = $this->txn['id'];
        }

        if ($amount === null)
        {
            if (isset($this->txn['amount']))
                $amount = $this->txn['amount'];
        }
    }
}