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
    }

    /**
     * When payment is not authorized on payment network gateway
     */
    public function testCredits()
    {

        $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 100000,
                   ]);

         $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 100000,
                       'merchant_id' => '10NodalAccount',
                   ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');

        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(true, $txn['gratis']);
        $this->assertEquals('1ZeroPricingR1', $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);

        // $nodalBalance = $this->getNodalAccountBalance();
        // $this->assertEquals(1050000, $nodalBalance['balance']);
        // $this->assertEquals(50000, $nodalBalance['credits']);
    }

    /**
     * When payment authorized on payment network gateway
     */
    public function testCredits2()
    {
        $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 100000,
                   ]);

         $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 100000,
                       'merchant_id' => '10NodalAccount',
                   ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(false, $txn['gratis']);
        $this->assertEquals(null, $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        // Only payment authorized. So equal to original credits.
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(100000, $balance['credits']);

        // $nodalBalance = $this->getNodalAccountBalance();
        // $this->assertEquals(1050000, $nodalBalance['balance']);
        // $this->assertEquals(100000, $nodalBalance['credits']);

        $this->capturePayment($payment['razorpay_payment_id'], '50000');

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);

        // $nodalBalance = $this->getNodalAccountBalance();
        // $this->assertEquals(1050000, $nodalBalance['balance']);
        // $this->assertEquals(50000, $nodalBalance['credits']);
    }

    public function testPartialCredits()
    {
        $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 100000,
                   ]);

         $this->fixtures->create('credits', [
                       'type'        => 'amount',
                       'value'       => 100000,
                       'merchant_id' => '10NodalAccount',
                   ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');

        $this->fixtures->merchant->editCreditsforNodalAccount('1000000');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment['amount'] = '500000';
        $this->doAuthAndCapturePayment($payment);

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(true, $txn['gratis']);
        $this->assertEquals('1ZeroPricingR2', $txn['pricing_rule_id']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1500000, $balance['balance']);
        $this->assertEquals(0, $balance['credits']);

        // $nodalBalance = $this->getNodalAccountBalance();
        // $this->assertEquals(1500000, $nodalBalance['balance']);
        // $this->assertEquals(900000, $nodalBalance['credits']);
    }

    public function testFeeCredits()
    {
        $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 10000,
                   ]);

         $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 10000,
                       'merchant_id' => '10NodalAccount',
                   ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('10000', 'fee');

        $this->doAuthAndCapturePayment();

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $payment = $this->getLastEntity('payment', true);
        $txn = $this->getLastEntity('transaction', true);
        // $nodalBalance = $this->getNodalAccountBalance();

        $this->assertEquals($txn['fee_credits'], $txn['fee']);
        $this->assertEquals(false, $txn['gratis']);

        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(10000 - $txn['fee_credits'], $balance['fee_credits']);

        // $this->assertEquals(1050000, $nodalBalance['balance']);
        // $this->assertEquals(10000 - $txn['fee_credits'], $nodalBalance['fee_credits']);
    }

    // We authorize, check the fields and capture the payment. We then check if
    // credits are updated or not.
    public function testFeeCredits2()
    {
        $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 10000,
                   ]);

         $this->fixtures->create('credits', [
                       'type'        => 'fee',
                       'value'       => 10000,
                       'merchant_id' => '10NodalAccount',
                   ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('10000', 'fee');

        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(10000, $balance['fee_credits']);

        // $nodalBalance = $this->getNodalAccountBalance();
        // $this->assertEquals(1050000, $nodalBalance['balance']);
        // $this->assertEquals(10000, $nodalBalance['fee_credits']);

        $this->capturePayment($payment['razorpay_payment_id'], '50000');

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $txn = $this->getLastEntity('transaction', true);
        // $nodalBalance = $this->getNodalAccountBalance();

        $this->assertEquals($txn['fee_credits'], $txn['fee']);
        $this->assertEquals(false, $txn['gratis']);

        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(10000 - $txn['fee_credits'], $balance['fee_credits']);

        // $this->assertEquals(1050000, $nodalBalance['balance']);
        // $this->assertEquals(10000 - $txn['fee_credits'], $nodalBalance['fee_credits']);
    }

    public function testRefundCredits()
    {
        $this->fixtures->create('credits',
          [
            'type'        => 'refund',
            'value'       => 100000
          ]);

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->doAuthCaptureAndRefundPayment();

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($txn['fee_credits'], $txn['amount']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals(false, $txn['gratis']);
        $this->assertEquals('refund', $txn['credit_type']);

        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(10000 - $txn['fee_credits'], $balance['refund_credits']);

        // do refund and validate transactions values
    }

    public function testRefundWithPartialCredits()
    {
        // $this->fixtures->create('credits',
        //   [
        //     'type'        => 'refund',
        //     'value'       => 10000
        //   ]);

        // $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        // $this->doAuthAndCapturePayment();

        // do refund, it should fail
    }


    public function testRefundWithCreditsDisabled()
    {
        // $this->fixtures->create('credits',
        //   [
        //     'type'        => 'refund',
        //     'value'       => 10000
        //   ]);

        // $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        // $this->doAuthAndCapturePayment();

        // do refund, it should not use credits
    }
}
