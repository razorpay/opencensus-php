<?php

namespace RZP\Tests\Functional\Transaction;

use Carbon\Carbon;
use RZP\Models\Transaction;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class TransactionTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testAddAdjustment()
    {
        $this->setAdminForInternalAuth();

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $adj = $this->startTest();

        $testData = $this->testData['testGetAdjustment'];
        $testData['request']['url'] = '/adjustments/'.$adj['id'];


        $this->ba->proxyAuth();

        $adj = $this->runRequestResponseFlow($testData);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterAddingAdjustment'];
        $testData['entity_id'] = $adj['id'];
        $this->assertArraySelectiveEquals($testData, $txn);

        return $adj;
    }

    public function testAddReverseAdjustment()
    {
        $this->ba->appAuth();
        $adj = $this->testAddAdjustment();

        $testData = $this->testData['testAddReverseAdjustment'];
        $testData['request']['content']['ids'] = [
            $adj['id']
        ];


        $this->ba->appAuth();
        $response = $this->runRequestResponseFlow($testData);

        $rev = $this->getLastEntity('adjustment', true);

        $this->assertEquals($rev['amount'] + $adj['amount'], 0);
    }

    public function testTransactionAfterCapturingPayment()
    {
        $payment = $this->doAuthAndCapturePayment();

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterCapturingPayment'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testFetchPaymentTransaction()
    {
        $payment = $this->doAuthAndCapturePayment();

        $request = [
            'url'     => '/payments/'.$payment['id'].'/transaction',
            'method'  => 'GET',
            'content' => [],
        ];

        $this->ba->privateAuth();

        $txn = $this->makeRequestAndGetContent($request);

        $this->assertEquals($txn['entity_id'], $payment['id']);
        $this->assertEquals($txn['type'], 'payment');

        return $payment;
    }

    public function testTransactionCreateForOldPayment()
    {
        $this->markTestSkipped();

        $payment = $this->fixtures->times(5)->create('payment:authorized',
            ['created_at' => 1467301400]);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['testTransactionCreateForOldPayment'];
        $testData['fee'] = $txn['fee'];
        $testData['service_tax'] = $txn['service_tax'];
        $testData['tax'] = $txn['tax'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $payment;
    }

    public function testTransactionAfterRefund()
    {
        $refund = $this->doAuthCaptureAndRefundPayment();

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterRefundingPayment'];
        $testData['entity_id'] = $refund['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $refund;
    }

    public function testCreateDisputeWithDeduct()
    {
        $payment = $this->fixtures->create('payment:captured');

        $dispute = $this->disputePayment($payment, 1);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterDisputingPayment'];
        $testData['entity_id'] = $dispute['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $dispute;
    }

    public function testCreateDisputeWithoutDeduct()
    {
        $payment = $this->fixtures->create('payment:captured');

        $dispute = $this->disputePayment($payment);

        $txn = $this->getLastTransaction(true);

        $testData = $this->testData['txnDataAfterDisputingPaymentWithoutDeduct'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $dispute;
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setAdminForInternalAuth()
    {
        $this->org = $this->fixtures->create('org');

        $this->authToken = $this->getAuthTokenForOrg($this->org);
    }
}
