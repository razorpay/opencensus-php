<?php

namespace Tests\Functional\AtomGateway;

use Tests\Functional\Helpers\Payment\PaymentTrait;
use Tests\Functional\TestCase;

class SharedTerminalTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/netbanking.php';

        parent::setUp();

        $this->gateway = 'atom';

        $this->merchant = $this->fixtures->create('merchant:with_keys');

        $this->ba->setDefaultKey('rzp_test_AltTestAuthKey')->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_atom_terminal');
    }


    public function testNBPaymentOnSharedTerminal()
    {
        $payment = $this->getDefaultNetBankingPaymentArray();
        $this->assertPaymentFields($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertTestResponse($txn);
    }

    /**
     * Payment on atom shared terminal
     */
    public function testCardPaymentOnSharedTerminal()
    {
        $this->assertPaymentFields();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertTestResponse($txn);
    }

    public function testDebitCardPaymentOnSharedTerminal()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000401111111110';
        $payment['amount'] = '200000';

        $this->assertPaymentFields($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertTestResponse($txn);
    }

    public function testDebitCardPaymentOnSharedTerminalWithGreaterThan2000Amount()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4000401111111110';
        $payment['amount'] = '200001';

        $this->assertPaymentFields($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertTestResponse($txn);
    }

    protected function assertPaymentFields($paymentInput = null)
    {
        $payment = $this->doAuthAndCapturePayment($paymentInput);

        $payment = $this->getLastEntity('payment', true);

        if (($paymentInput === null) or
            (isset($paymentInput['method']) === false))
            $method = 'card';
        else
            $method = $paymentInput['method'];

        $this->assertEquals('atom', $payment['gateway']);
        $this->assertEquals($method, $payment['method']);
        $this->assertEquals('1000AtomShared', $payment['terminal_id']);
    }
}
