<?php

namespace Tests\Functional\Gateway\Atom;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class AtomTransactionTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AtomTransactionTestData.php';

        parent::setUp();

        $this->fixtures->create('terminal:atom_terminal');

        $this->payment = array(
            'method' => 'netbanking',
            'bank' => 'ICIC',
            'amount' => '5000',
            'email' => 'ab@g.com',
            'contact' => '9431495816',
            'currency' => 'INR');

        $this->gateway = 'atom';

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_atom_terminal');
    }

    public function testTransactionAfterAtomPayment()
    {
        $this->fixtures->create('terminal:atom_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthAndCapturePayment($payment);

        $txn = $this->getLastTransaction();

        $testData = $this->testData['txnDataAfterCapturingAtomPayment'];
        $testData['entity_id'] = $payment['id'];

        $this->assertArraySelectiveEquals($testData, $txn);
    }

    public function testTransactionAfterAtomRefund()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $refund = $this->doAuthCaptureAndRefundPayment($payment);

        $txn = $this->getLastTransaction();

        $testData = $this->testData['txnDataAfterRefundingAtomPayment'];
        $testData['entity_id'] = $refund['id'];

        $this->assertArraySelectiveEquals($testData, $txn);

        return $refund;
    }

    public function testTransactionOnAtomSharedTerminal()
    {
        $merchant = $this->fixtures->create('merchant_fluid')
                         ->addKeys()
                         ->addPaymentBanks()
                         ->addBankAccount()
                         ->get();

        $this->ba->setDefaultKey('rzp_test_AltTestAuthKey');

        $payment = $this->getDefaultNetbankingPaymentArray();
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
        $this->fixtures->create('merchant_fluid:instance')
                       ->getMerchant('1MercShareTerm')
                       ->addBalance()
                       ->addKeys()
                       ->addPaymentBanks();

        $this->ba->setDefaultKey('rzp_test_AltTestAuthKey');

        $payment = $this->getDefaultNetbankingPaymentArray();
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
