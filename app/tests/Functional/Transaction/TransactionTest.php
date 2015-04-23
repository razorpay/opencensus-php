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

    public function testTransactionAfterAtomPayment()
    {
        $this->gateway = 'atom';
        $this->fixtures->create('terminal:atom_terminal');

        $payment = $this->getDefaultNetBankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $txn = $this->getLastTransaction();

        $testData = $this->testData['txnDataAfterCapturingAtomPayment'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);
    }

    public function testTransactionAfterAtomRefund()
    {
        $this->gateway = 'atom';
        $this->fixtures->create('terminal:atom_terminal');

        $payment = $this->getDefaultNetBankingPaymentArray();
        $refund = $this->doAuthCaptureAndRefundPayment($payment);

        $txn = $this->getLastTransaction();

        $testData = $this->testData['txnDataAfterRefundingAtomPayment'];
        $testData['entity_id'] = $refund['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $refund;
    }

    public function testTransactionOnAtomSharedTerminal()
    {
        $this->gateway = 'atom';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $merchant = $this->fixtures->create('merchant_fluid:entity')
                         ->addKeys()
                         ->addPaymentBanks()
                         ->addBankAccount()
                         ->get();

        $this->ba->setDefaultKey('rzp_test_AltTestAuthKey');

        $payment = $this->getDefaultNetBankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('atom', $payment['gateway']);
        $this->assertEquals('1000AtomShared', $payment['terminal_id']);

        $txn = $this->getLastTransaction(true);
        $this->assertEquals('kotak', $txn['channel']);

        $testData = $this->testData['txnDataAfterPaymentOnSharedTerminal'];
        $testData['entity_id'] = $payment['id'];
        $testData['settled_at'] = (new \Models\Transaction\Core)->getSettledAtTimestamp(time(), 3);

        $this->assertArraySelectiveEquals($testData, $txn);
    }

    public function testTransactionOnAtomSharedTerminalMerchant()
    {
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_atom_terminal');

        $this->fixtures->create('merchant_fluid:instance')
                       ->getMerchant('10AtomRazorpay')
                       ->addBalance()
                       ->addKeys()
                       ->addPaymentBanks();

        $this->ba->setDefaultKey('rzp_test_AltTestAuthKey');

        $payment = $this->getDefaultNetBankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('atom', $payment['gateway']);
        $this->assertEquals('1000AtomShared', $payment['terminal_id']);

        $txn = $this->getLastTransaction(true);
        $this->assertEquals('kotak', $txn['channel']);

        $testData = $this->testData['txnDataAfterPaymentOnSharedTerminal'];
        $testData['entity_id'] = $payment['id'];
        $testData['settled_at'] = (new \Models\Transaction\Core)->getSettledAtTimestamp(time(), 3);

        $this->assertArraySelectiveEquals($testData, $txn);
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