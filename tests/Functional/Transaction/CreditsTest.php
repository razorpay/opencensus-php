<?php

namespace RZP\Tests\Functional\Transaction;

use Mail;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Merchant\FeeCreditsAlert;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CreditsTest extends TestCase
{
    use PaymentTrait;

    protected function setUp(): void
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

    public function testAmountCreditsWithDisableRegMerchantFeatureOnRegisteredMerchants()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 1
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => '100000razorpay',
            'name'          => 'disable_free_credit_reg',
        ]);

        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(1000, $txn['fee']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(false, $txn['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1049000, $balance['balance']);
        $this->assertEquals(100000, $balance['credits']);
    }

    public function testAmountCreditsWithDisableRegMerchantFeatureOnUnregisteredMerchants()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => '100000razorpay',
            'name'          => 'disable_free_credit_reg',
        ]);

        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(true, $txn['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);
    }

    public function testAmountCreditsWithDisableUnRegMerchantFeatureOnUnregisteredMerchants()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 2
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => '100000razorpay',
            'name'          => 'disable_free_credit_unreg',
        ]);

        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(1000, $txn['fee']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(false, $txn['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1049000, $balance['balance']);
        $this->assertEquals(100000, $balance['credits']);
    }

    public function testAmountCreditsWithDisableUnRegMerchantFeatureOnRegisteredMerchants()
    {
        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
            'merchant_id' => '10000000000000',
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id' => '10000000000000',
            'contact_name'=> 'Aditya',
            'business_type' => 1
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');

        $this->fixtures->feature->create([
            'entity_type'   => 'org',
            'entity_id'     => '100000razorpay',
            'name'          => 'disable_free_credit_unreg',
        ]);

        $this->doAuthAndCapturePayment();

        $txn = $this->getLastEntity('transaction', true);
        $this->assertEquals(0, $txn['fee']);
        $this->assertEquals(0, $txn['tax']);
        $this->assertEquals(true, $txn['gratis']);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(50000, $balance['credits']);
    }

    public function testPartialCredits()
    {
        $this->fixtures->create('credits', [
            'type'  => 'amount',
            'value' => 100000,
        ]);

        $this->fixtures->create('credits', [
            'type'        => 'amount',
            'value'       => 100000,
            'merchant_id' => '10NodalAccount',
        ]);

        $this->fixtures->merchant->editCredits('100000', '10000000000000');
        $this->fixtures->merchant->editCreditsforNodalAccount('100000');

        $this->fixtures->merchant->editCreditsforNodalAccount('1000000');

        $payment           = $this->getDefaultNetbankingPaymentArray();
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

    // We authorize, check the fields and capture the payment. We then check if
    // credits are crossing threshold and an alert is triggered.
    public function testFeeCreditsThresholdAlerts()
    {
        Mail::fake();

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 10000,
        ]);

        $this->fixtures->merchant->editFeeCredits('10000', '10000000000000');

        $this->fixtures->merchant->editFeeCreditsThreshold('9999', '10000000000000');

        // Check if mail is getting triggered when 1st Threshold is crossed
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment = $this->doAuthPayment($payment);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1000000, $balance['balance']);

        $this->assertEquals(10000, $balance['fee_credits']);

        $this->capturePayment($payment['razorpay_payment_id'], '50000');

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($txn['fee_credits'], $txn['fee']);

        $this->assertEquals(1050000, $balance['balance']);

        $this->assertEquals(10000 - $txn['fee_credits'], $balance['fee_credits']);

        Mail::assertQueued(FeeCreditsAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals(1, $viewData['alert_ratio']);

            $this->assertEquals(['test@razorpay.com'], $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('dashboard.razorpay.in', $viewData['org_hostname']);

            $this->assertEquals('emails.merchant.fee_credits_alert', $mail->view);

            return true;
        });

        // Check if mail is getting triggered when 2nd and 3rd Threshold is crossed together
        $payment = $this->getDefaultNetbankingPaymentArray();

        $payment['amount'] = 150000;

        $payment = $this->doAuthPayment($payment);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $this->assertEquals(1050000, $balance['balance']);

        $this->assertEquals(10000 - $txn['fee_credits'], $balance['fee_credits']);

        $this->capturePayment($payment['razorpay_payment_id'], '150000');

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($txn['fee_credits'], $txn['fee']);

        $this->assertEquals(1200000, $balance['balance']);

        $this->assertEquals(4098, $balance['fee_credits']);

        Mail::assertQueued(FeeCreditsAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            if ($viewData['fee_credits'] === '₹ 40.98')
            {
                $this->assertEquals(0.5, $viewData['alert_ratio']);

                $this->assertEquals(['test@razorpay.com'], $viewData['email']);

                $this->assertEquals(10000000000000, $viewData['merchant_id']);

                $this->assertEquals('emails.merchant.fee_credits_alert', $mail->view);

                return true;
            }

            return false;
        });
    }

    public function testFeeCreditsAlertNotFiredCases()
    {
        Mail::fake();

        $this->fixtures->create('credits', [
            'type'        => 'fee',
            'value'       => 9998,
        ]);

        $this->fixtures->merchant->editFeeCredits('9998', '10000000000000');
        $this->fixtures->merchant->editFeeCreditsThreshold('9999', '10000000000000');

        // Check if mail is getting triggered when 1st Threshold is crossed
        $payment = $this->getDefaultNetbankingPaymentArray();
        $payment = $this->doAuthPayment($payment);

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $this->assertEquals(1000000, $balance['balance']);
        $this->assertEquals(9998, $balance['fee_credits']);

        $this->capturePayment($payment['razorpay_payment_id'], '50000');

        $balance = $this->getEntityById('balance', '10000000000000', true);
        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals($txn['fee_credits'], $txn['fee']);

        $this->assertEquals(1050000, $balance['balance']);
        $this->assertEquals(9998 - $txn['fee_credits'], $balance['fee_credits']);

        Mail::assertNotQueued(FeeCreditsAlert::class);
    }

    public function testRefundCredits()
    {
        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 100000
            ]);

        $this->fixtures->merchant->editRefundCredits('100000', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $this->doAuthCaptureAndRefundPayment();

        $balanceAfterPayment = $this->getEntityById('balance', '10000000000000', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('refund', $txn['type']);
        $this->assertEquals($txn['fee_credits'], $txn['amount']);
        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals(false, $txn['gratis']);
        $this->assertEquals('refund', $txn['credit_type']);

        // Balance is increased by the payment amount - fees
        $this->assertEquals(1049000, $balanceAfterPayment['balance']);
        // Refund Credits get decreased by refund amount
        $this->assertEquals(100000 - $txn['fee_credits'], $balanceAfterPayment['refund_credits']);

        $credits = $this->getLastEntity('credits', true);
        $this->assertEquals(50000, $credits['used']);
    }

    public function testRefundCreditsForAuthOnlyPayment()
    {
        $creditEntry = $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 100000
            ]);

        $this->fixtures->merchant->editRefundCredits('100000', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $terminal = $this->fixtures->create('terminal:shared_netbanking_hdfc_terminal');

        $payment = $this->getDefaultNetbankingPaymentArray("HDFC");

        $payment = $this->doAuthPayment($payment);

        // This time we do not capture the payment and refund it
        $this->refundAuthorizedPayment($payment['razorpay_payment_id']);

        $balanceAfterPayment = $this->getEntityById('balance', '10000000000000', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals('refund', $txn['type']);
        $this->assertEquals(50000, $txn['amount']);

        // 1. Fee Credits are not set to 0 where payment is not captured and refunded.
        // 2. Balance is set to merchant Balance instead of 0.
        // 3. And, Credit Type is default.
        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals(0, $txn['balance']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertEquals(0, $txn['debit']);
        $this->assertEquals(false, $txn['gratis']);

        // Balance is not increased as payment is not captured yet
        $this->assertEquals(1000000, $balanceAfterPayment['balance']);
        // Refund Credits are also not decreased as payment is not captured yet
        $this->assertEquals(100000, $balanceAfterPayment['refund_credits']);

        $credits = $this->getLastEntity('credits', true);
        $this->assertEquals(0, $credits['used']);
        // Checking if the last entry is still the same.
        // because We don't want a zero entry to be created in this scenario.
        $this->assertEquals($creditEntry['created_at'], $credits['created_at']);
    }

    public function testRefundWithPartialCredits()
    {
        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 10000
            ]);

        $this->fixtures->merchant->editRefundCredits('10000', '10000000000000');

        $this->fixtures->merchant->edit('10000000000000', ['refund_source' => 'credits']);

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->doAuthCaptureAndRefundPayment();
        });
    }

    public function testRefundWithCreditsDisabled()
    {
        $this->fixtures->create('credits',
            [
                'type'  => 'refund',
                'value' => 100000
            ]);

        $this->fixtures->merchant->editRefundCredits('100000', '10000000000000');

        $this->doAuthCaptureAndRefundPayment();

        $balance = $this->getEntityById('balance', '10000000000000', true);

        $txn = $this->getLastEntity('transaction', true);

        $this->assertEquals(0, $txn['fee_credits']);
        $this->assertEquals(50000, $txn['debit']);
        $this->assertEquals(false, $txn['gratis']);
        $this->assertEquals('default', $txn['credit_type']);

        $this->assertEquals(999000, $balance['balance']);
        $this->assertEquals(100000, $balance['refund_credits']);
    }
}
