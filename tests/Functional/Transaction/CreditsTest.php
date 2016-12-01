<?php

namespace RZP\Tests\Functional\Transaction;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CreditsTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TransactionData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_billdesk_terminal');
        $this->fixtures->create('pricing:zero_pricing_plan');
    }

    /**
     * When payment is not authorized on payment network gateway
     */
    public function testCredits()
    {
        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');

        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['service_tax']);
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
        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['service_tax']);
        $this->assertEquals(true, $txn['gratis']);
        $this->assertEquals('1ZeroPricingR2', $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        // Only payment authorized. So equal to original credits.
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(100000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(100000, $nodalBalance['credits']);

        $this->capturePayment($payment['razorpay_payment_id'], '50000');

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(50000, $nodalBalance['credits']);
    }

    public function testPartialCredits()
    {
        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');

        $this->fixtures->merchant->editCreditsforNodalAccount('1000000');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['amount'] = '500000';
        $this->doAuthAndCapturePayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['service_tax']);
        $this->assertEquals(true, $txn['gratis']);
        $this->assertEquals('1ZeroPricingR2', $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1500000, $balance['balance']);
        $this->assertEquals(0, $balance['credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1500000, $nodalBalance['balance']);
        $this->assertEquals(900000, $nodalBalance['credits']);
    }

    public function testFeeCredits()
    {
        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('10000', 'fee');

        $this->doAuthAndCapturePayment();

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $payment = $this->getLastEntity('payment', true);
        $txn = $this->getLastEntity('transaction', true);
        $nodalBalance = $this->getNodalAccountBalance();

        $this->assertEquals($txn['fee_credits'], $txn['fee']);
        $this->assertEquals(false, $txn['gratis']);

        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(10000 - $txn['fee_credits'], $balance['fee_credits']);

        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(10000 - $txn['fee_credits'], $nodalBalance['fee_credits']);
    }

    // We authorize, check the fields and capture the payment. We then check if
    // credits are updated or not.
    public function testFeeCredits2()
    {
        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('10000', 'fee');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(10000, $balance['fee_credits']);

        $nodalBalance = $this->getNodalAccountBalance();
        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(10000, $nodalBalance['fee_credits']);

        $this->capturePayment($payment['razorpay_payment_id'], '50000');

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $txn = $this->getLastEntity('transaction', true);
        $nodalBalance = $this->getNodalAccountBalance();

        $this->assertEquals($txn['fee_credits'], $txn['fee']);
        $this->assertEquals(false, $txn['gratis']);

        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(10000 - $txn['fee_credits'], $balance['fee_credits']);

        $this->assertEquals(1050000, $nodalBalance['balance']);
        $this->assertEquals(10000 - $txn['fee_credits'], $nodalBalance['fee_credits']);
    }
}
