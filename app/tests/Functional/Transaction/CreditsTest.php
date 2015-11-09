<?php

namespace Tests\Functional\Transaction;

use Carbon\Carbon;
use Tests\Functional\TestCase;
use Tests\Functional\Helpers\Payment\PaymentTrait;

class CreditsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');
        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->create('pricing:zero_pricing_plan');
    }

    /**
     * When payment is not authorized on payment network gateway
     */
    public function testCredits()
    {
        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(true, $txn['gratis']);
        $this->assertEquals('1ZeroPricingR1', $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(50000, $nodalBalance['credits']);
    }

    /**
     * When payment authorized on payment network gateway
     */
    public function testCredits2()
    {
        $payment = $this->getDefaultNetbankingPaymentArray();
        $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(true, $txn['gratis']);
        $this->assertEquals('1ZeroPricingR2', $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(50000, $nodalBalance['credits']);
    }

    public function testPartialCredits()
    {
        $this->fixtures->merchant->editCreditsforNodalAccount('1000000');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['amount'] = '500000';
        $this->doAuthAndCapturePayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(true, $txn['gratis']);
        $this->assertEquals('1ZeroPricingR2', $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1500000, $balance['balance']);
        $this->assertEquals(0, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1500000, $nodalBalance['balance']);
        $this->assertEquals(900000, $nodalBalance['credits']);
    }
}
