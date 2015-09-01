<?php

namespace Tests\Functional\Transaction;

use Carbon\Carbon;
use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class TransactionTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testAddAdjustment()
    {
        $adj = $this->startTest();

        $testData = $this->testData['testGetAdjustment'];
        $testData['request']['url'] = '/adjustments/'.$adj['id'];

        $adj = $this->runRequestResponseFlow($testData);

        $txn = $this->getLastTransaction();

        $testData = $this->testData['txnDataAfterAddingAdjustment'];
        $testData['entity_id'] = $adj['id'];
        $this->assertArraySelectiveEquals($testData, $txn);
    }

    public function testAddAdjustmentWithoutUpdatingEscrowBalance()
    {
        $adj = $this->startTest();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertTestResponse($txn, 'txnDataAfterAddingAdjWithNoEscrowUpdate');
    }

    public function testTransactionAfterCapturingPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $txn = $this->getLastTransaction();

        $testData = $this->testData['txnDataAfterCapturingPayment'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testTransactionAfterRefund()
    {
        $refund = $this->doAuthCaptureAndRefundPayment();

        $txn = $this->getLastTransaction();

        $testData = $this->testData['txnDataAfterRefundingPayment'];
        $testData['entity_id'] = $refund['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $refund;
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}