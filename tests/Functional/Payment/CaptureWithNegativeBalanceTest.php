<?php

namespace RZP\Tests\Functional\Payment;

use Mail;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Services\Mock\Reminders;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Merchant\BalancePositiveAlert;
use RZP\Mail\Merchant\NegativeBalanceAlert;
use RZP\Mail\Payment\Captured as CapturedMail;
use RZP\Mail\Merchant\NegativeBalanceThresholdAlert;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class CaptureWithNegativeBalanceTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected $testData = null;

    protected $payment = null;


    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/CaptureTestData.php';

        parent::setUp();

        $payment = $this->fixtures->create('payment:authorized');

        $this->payment = $payment->toArrayPublic();

        $this->ba->privateAuth();
    }

    //Negative Balance Tests
    public function testCaptureWithNegativeBalance()
    {
        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'     => -110000,
            ]
        );

        Mail::fake();

        $this->payment = $this->defaultAuthPayment(['amount' => 100,'currency' => 'INR']);

        $this->ba->privateAuth();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals(-109902, $balance['balance']);

        Mail::assertQueued(CapturedMail::class);
    }

    public function testEmandateCaptureWithSufficientBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(5000, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(3820, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(3820, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testEmandateCaptureWithZeroBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(0,0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals('10000000000000', $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-1180, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('-11.8 INR' , $viewData['balance']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_alert', $mail->view);

            return true;
        });
    }

    public function testEmandateCaptureWithZeroBalanceWithAutoRecurringType()
    {
        Mail::fake();

        $this->setUpEmandateFixtures(0,0, 'UTIB', 'auto');

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testEmandateCaptureWithNegativeBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(-248820, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-250000, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-250000, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(50, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testEmandateCaptureWithNegativeBalanceCrossingThreshold()
    {
        Mail::fake();

        $this->setUpEmandateFixtures(-500000, 0);

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testEmandateCaptureWithNegativeAndReserveBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(-399000, 0);

        $this->fixtures->create('balance',
            [
                'type'          => 'reserve_primary',
                'balance'       => 200000,
                'merchant_id'   => '10000000000000'
            ]
        );
        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', 10000000000000);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-400180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-400180, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(80, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testEmandateCaptureWithSufficientFeeCredits()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(0, 5000);

        $this->startTest();

        $credit_txn = $this->getLastEntity('credit_transaction', true);

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals($credit_txn['credits_used'], 1180);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(0, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');
        $this->assertEquals($transaction['credit_type'], 'fee');

        $this->assertEquals(0, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(3820, $balance['fee_credits']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testEmandateCaptureWithInSufficientFeeCredits()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(0, 700);

        $this->startTest();

        $credit_txn = $this->getLastEntity('credit_transaction', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($credit_txn);

        $balance = $this->getDbEntityById('balance', 10000000000000);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-1180, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(700, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('-11.8 INR' , $viewData['balance']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_alert', $mail->view);

            return true;
        });
    }

    public function testEmandateCaptureWithFeeCreditsAndReserveBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(0, 2000);

        $this->fixtures->create('balance',
            [
                'type'          => 'reserve_primary',
                'balance'       => 200000,
                'merchant_id'   => '10000000000000'
            ]
        );
        $this->startTest();

        $credit_txn = $this->getLastEntity('credit_transaction', true);

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', 10000000000000);

        $this->assertEquals($credit_txn['credits_used'], 1180);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(0, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');
        $this->assertEquals($transaction['credit_type'], 'fee');

        $this->assertEquals(0, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(820, $balance['fee_credits']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testNachCaptureWithFeeCreditsAndReserveBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpNachFixtures(0, 2000);

        $this->fixtures->create('balance',
            [
                'type'          => 'reserve_primary',
                'balance'       => 200000,
                'merchant_id'   => '10000000000000'
            ]
        );
        $this->startTest();

        $credit_txn = $this->getLastEntity('credit_transaction', true);

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', 10000000000000);

        $this->assertEquals($credit_txn['credits_used'], 1180);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(0, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');
        $this->assertEquals($transaction['credit_type'], 'fee');

        $this->assertEquals(0, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(820, $balance['fee_credits']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testNachCaptureWithSufficientFeeCredits()
    {
        Mail::fake();

        $paymentId = $this->setUpNachFixtures(0, 5000);

        $this->startTest();

        $credit_txn = $this->getLastEntity('credit_transaction', true);

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals($credit_txn['credits_used'], 1180);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(0, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(0, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');
        $this->assertEquals($transaction['credit_type'], 'fee');

        $this->assertEquals(0, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(3820, $balance['fee_credits']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testNachCaptureWithNegativeAndReserveBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpNachFixtures(-399000, 0);

        $this->fixtures->create('balance',
            [
                'type'          => 'reserve_primary',
                'balance'       => 200000,
                'merchant_id'   => '10000000000000'
            ]
        );
        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', 10000000000000);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-400180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-400180, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(80, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testNachCaptureWithNegativeBalanceCrossingThreshold()
    {
        Mail::fake();

        $this->setUpNachFixtures(-500000, 0);

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testNachCaptureWithNegativeBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpNachFixtures(-248820, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-250000, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-250000, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceThresholdAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals(50, $viewData['percentage']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_threshold_alert', $mail->view);

            return true;
        });
    }

    public function testNachCaptureWithZeroBalanceWithAutoRecurringType()
    {
        Mail::fake();

        $this->setUpNachFixtures(0,0, 'UTIB', 'auto');

        $this->startTest();

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testNachCaptureWithZeroBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpNachFixtures(0,0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals('10000000000000', $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-1180, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('-11.8 INR' , $viewData['balance']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_alert', $mail->view);

            return true;
        });
    }

    public function testNachCaptureWithSufficientBalance()
    {
        Mail::fake();

        $paymentId = $this->setUpNachFixtures(5000, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(3820, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(3820, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(0, $balance['fee_credits']);

        Mail::assertNotQueued(NegativeBalanceAlert::class);
        Mail::assertNotQueued(NegativeBalanceThresholdAlert::class);
        Mail::assertNotQueued(BalancePositiveAlert::class);
    }

    public function testNachCaptureWithInSufficientFeeCredits()
    {
        Mail::fake();

        $paymentId = $this->setUpNachFixtures(0, 700);

        $this->startTest();

        $credit_txn = $this->getLastEntity('credit_transaction', true);

        $transaction = $this->getLastEntity('transaction', true);

        $this->assertNull($credit_txn);

        $balance = $this->getDbEntityById('balance', 10000000000000);

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(0, $transaction['credit'] );
        $this->assertEquals(1180, $transaction['debit']);
        $this->assertEquals(1180, $transaction['fee']);
        $this->assertEquals(1180, $transaction['mdr']);
        $this->assertEquals(180, $transaction['tax']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);

        $this->assertEquals($transaction['fee_model'], 'prepaid');

        $this->assertEquals(-1180, $balance['balance']);
        $this->assertEquals('primary', $balance['type']);
        $this->assertEquals(700, $balance['fee_credits']);

        Mail::assertQueued(NegativeBalanceAlert::class, function ($mail)
        {
            $viewData = $mail->viewData;

            $this->assertEquals('test@razorpay.com', $viewData['email']);

            $this->assertEquals(10000000000000, $viewData['merchant_id']);

            $this->assertEquals('-11.8 INR' , $viewData['balance']);

            $this->assertArrayHasKey('email_logo', $viewData);

            $this->assertArrayHasKey('org_name', $viewData);

            $this->assertArrayHasKey('custom_branding', $viewData);

            $this->assertEquals('emails.merchant.negative_balance_alert', $mail->view);

            return true;
        });
    }

    public function testNegativeBalanceReminderCreation()
    {
        Mail::fake();

        $paymentId = $this->setUpEmandateFixtures(0, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);
        $this->assertEquals('primary', $balance['type']);

        Mail::assertQueued(NegativeBalanceAlert::class);

        $reminderEntity = $this->getLastEntity('merchant_reminders', true);

        $this->assertEquals('negative_balance', $reminderEntity['reminder_namespace']);
        $this->assertEquals('in_progress', $reminderEntity['reminder_status']);
        $this->assertEquals('10000000000000', $reminderEntity['merchant_id']);
    }

    public function testNegativeBalanceReminderCreationFromDisabledReminder()
    {
        Mail::fake();

        $reminderEntity = [
            'reminder_id'           => '10xyz00uvw0000',
            'merchant_id'           => '10000000000000',
            'reminder_status'       => 'disabled',
            'reminder_namespace'    => 'negative_balance',
        ];

        $merchantReminder = $this->fixtures->create('merchant_reminders', $reminderEntity);

        $this->assertNotNull($reminderEntity);
        $paymentId = $this->setUpEmandateFixtures(0, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);
        $this->assertEquals('primary', $balance['type']);

        Mail::assertQueued(NegativeBalanceAlert::class);

        $reminderEntity = $this->getDbEntityById('merchant_reminders', $merchantReminder['id']);

        $this->assertEquals('negative_balance', $reminderEntity['reminder_namespace']);
        $this->assertEquals('in_progress', $reminderEntity['reminder_status']);
        $this->assertEquals('10000000000000', $reminderEntity['merchant_id']);
        $this->assertNotEquals($merchantReminder['reminder_id'], $reminderEntity['reminder_id']);
    }

    public function testNegativeBalanceReminderCreationFromNullReminderId()
    {
        Mail::fake();

        $reminderEntity = [
            'merchant_id'           => '10000000000000',
            'reminder_status'       => 'pending',
            'reminder_namespace'    => 'negative_balance',
        ];

        $merchantReminder = $this->fixtures->create('merchant_reminders', $reminderEntity);

        $this->assertNotNull($reminderEntity);
        $paymentId = $this->setUpEmandateFixtures(0, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);
        $this->assertEquals('primary', $balance['type']);

        Mail::assertQueued(NegativeBalanceAlert::class);

        $reminderEntity = $this->getDbEntityById('merchant_reminders', $merchantReminder['id']);

        $this->assertEquals('negative_balance', $reminderEntity['reminder_namespace']);
        $this->assertEquals('in_progress', $reminderEntity['reminder_status']);
        $this->assertEquals('10000000000000', $reminderEntity['merchant_id']);
        $this->assertNotEquals($merchantReminder['reminder_id'], $reminderEntity['reminder_id']);
    }

    public function testNegativeBalanceReminderDeletion()
    {
        Mail::fake();

        $reminderEntity = [
            'reminder_id'           => '10xyz00uvw0000',
            'merchant_id'           => '10000000000000',
            'reminder_status'       => 'in_progress',
            'reminder_namespace'    => 'negative_balance',
        ];

        $merchantReminder = $this->fixtures->create('merchant_reminders', $reminderEntity);
        $this->assertNotNull($reminderEntity);

        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'     => -1000,
            ]
        );

        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertQueued(BalancePositiveAlert::class);

        $reminderEntity = $this->getDbEntityById('merchant_reminders', $merchantReminder['id']);
        $this->assertEquals('disabled', $reminderEntity['reminder_status']);
    }

    public function testNegativeBalanceDisabledReminderDeletion()
    {
        Mail::fake();

        $reminderEntity = [
            'merchant_id'           => '10000000000000',
            'reminder_status'       => 'disabled',
            'reminder_namespace'    => 'negative_balance',
        ];

        $merchantReminder = $this->fixtures->create('merchant_reminders', $reminderEntity);
        $this->assertNotNull($reminderEntity);

        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'     => -1000,
            ]
        );

        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        //$reminderMock = $this->createRemindersMock(['deleteReminder']);
        $reminderMock = \Mockery::mock(Reminders::class);

        $reminderMock->shouldReceive('deleteReminder')->never();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertQueued(BalancePositiveAlert::class);

        $reminderEntity = $this->getDbEntityById('merchant_reminders', $merchantReminder['id']);
        $this->assertEquals('disabled', $reminderEntity['reminder_status']);
    }

    public function testNegativeBalanceReminderDeletionWithException()
    {
        Mail::fake();

        $reminderEntity = [
            'reminder_id'           => '10xyz00uvw0000',
            'merchant_id'           => '10000000000000',
            'reminder_status'       => 'in_progress',
            'reminder_namespace'    => 'negative_balance',
        ];

        $merchantReminder = $this->fixtures->create('merchant_reminders', $reminderEntity);
        $this->assertNotNull($reminderEntity);

        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'     => -1000,
            ]
        );

        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $reminderMock = $this->createRemindersMock(['deleteReminder']);

        $reminderMock->method('deleteReminder')->willThrowException(new BadRequestException(ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE));

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertQueued(BalancePositiveAlert::class);

        $reminderEntity = $this->getDbEntityById('merchant_reminders', $merchantReminder['id']);
        $this->assertEquals('disabled', $reminderEntity['reminder_status']);
    }

    public function testNegativeBalanceReminderUpdateWithException()
    {
        Mail::fake();

        $reminderEntity = [
            'reminder_id'           => '10xyz00uvw0000',
            'merchant_id'           => '10000000000000',
            'reminder_status'       => 'pending',
            'reminder_namespace'    => 'negative_balance',
        ];

        $this->fixtures->create('merchant_reminders', $reminderEntity);
        $this->assertNotNull($reminderEntity);

        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $reminderMock = $this->createRemindersMock(['updateReminder']);

        $reminderMock->method('updateReminder')->willThrowException(new BadRequestException(ErrorCode::BAD_REQUEST_REMINDER_NOT_APPLICABLE));

        $paymentId = $this->setUpEmandateFixtures(0, 0);

        $this->startTest();

        $transaction = $this->getLastEntity('transaction', true);

        $balance = $this->getDbEntityById('balance', '10000000000000');

        $this->assertEquals('pay_'.$paymentId, $transaction['entity_id']);
        $this->assertEquals('payment', $transaction['type']);
        $this->assertEquals(-1180, $transaction['balance']);

        $this->assertEquals($balance['id'], $transaction['balance_id']);
        $this->assertEquals('primary', $balance['type']);

        Mail::assertQueued(NegativeBalanceAlert::class);

        $reminderEntity = $this->getLastEntity('merchant_reminders', true);

        $this->assertEquals('negative_balance', $reminderEntity['reminder_namespace']);
        $this->assertEquals('pending', $reminderEntity['reminder_status']);
        $this->assertEquals('10000000000000', $reminderEntity['merchant_id']);
    }

    public function testCaptureAddBalanceToNegativeBalance()
    {
        Mail::fake();

        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'     => -1000,
            ]
        );

        $this->payment = $this->defaultAuthPayment();

        $this->ba->privateAuth();

        $this->startTest();

        $payment = $this->getLastEntity('payment', true);

        $this->assertEquals(true, $payment['gateway_captured']);

        Mail::assertQueued(CapturedMail::class);
        Mail::assertQueued(BalancePositiveAlert::class);
    }

    private function setUpEmandateFixtures(int $balanceAmount = 0, int $feeCredits = 0,
                                           string $bank = 'UTIB',
                                           string $recurringType = 'initial')
    {
        $this->fixtures->merchant->addFeatures(['charge_at_will', 's2s', 'emandate_mrn']);

        $paymentData = $this->getEmandateNetbankingRecurringPaymentArray($bank);

        $paymentData['customer_id'] = '100000customer';
        $paymentData['status'] = 'authorized';
        $paymentData['gateway_captured'] = true;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $paymentData['amount']]);

        $paymentData['order_id'] = $order['id'];

        $tokenData = [
            'merchant_id'      => '10000000000000',
            'customer_id'      => '100000customer',
            'method'           => 'emandate',
            'bank'             => 'UTIB',
            'recurring'        => '1',
            'auth_type'        => 'netbanking',
            'account_number'   => '9170100000000319',
            'ifsc'             => 'UTIB0000194',
            'beneficiary_name' => 'Axis',
            'recurring_status' => 'initiated',
        ];

        $token = $this->fixtures->create('token', $tokenData);

        $paymentData['token_id']  = $token['id'];
        $paymentData['recurring_type']  = $recurringType;
        $paymentData['recurring']  = 1;

        $payment = $this->fixtures->create('payment', $paymentData);

        $this->payment = $payment->toArrayPublic();

        $this->payment['bank_account'] = [
            'account_number'    => '9170100000000319',
            'ifsc'               => 'UTIB0000194',
            'name'               => 'Axis',
        ];

        if($feeCredits !== 0 )
        {
            $this->fixtures->create('credits',
                [
                    'type'  => 'fee',
                    'value' => $feeCredits,
                ]
            );
        }

        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'     => $balanceAmount,
                'fee_credits' => $feeCredits
            ]
        );

        $this->ba->privateAuth();

        return $payment['id'];
    }

    private function setUpNachFixtures(int $balanceAmount = 0, int $feeCredits = 0,
                                       string $bank = 'UTIB',
                                       string $recurringType = 'initial')
    {
        $this->fixtures->merchant->addFeatures(['charge_at_will', 's2s', 'emandate_mrn']);

        $paymentData = $this->getNachNetbankingRecurringPaymentArray($bank);

        $paymentData['customer_id'] = '100000customer';
        $paymentData['status'] = 'authorized';
        $paymentData['gateway_captured'] = true;

        $order = $this->fixtures->create('order:emandate_order', ['amount' => $paymentData['amount']]);

        $paymentData['order_id'] = $order['id'];

        $tokenData = [
            'merchant_id'      => '10000000000000',
            'customer_id'      => '100000customer',
            'method'           => 'nach',
            'bank'             => 'UTIB',
            'recurring'        => '1',
            'auth_type'        => 'netbanking',
            'account_number'   => '9170100000000319',
            'ifsc'             => 'UTIB0000194',
            'beneficiary_name' => 'Axis',
            'recurring_status' => 'initiated',
        ];

        $token = $this->fixtures->create('token', $tokenData);

        $paymentData['token_id']  = $token['id'];
        $paymentData['recurring_type']  = $recurringType;
        $paymentData['recurring']  = 1;

        $payment = $this->fixtures->create('payment', $paymentData);

        $this->payment = $payment->toArrayPublic();

        $this->payment['bank_account'] = [
            'account_number'    => '9170100000000319',
            'ifsc'               => 'UTIB0000194',
            'name'               => 'Axis',
        ];

        if($feeCredits !== 0 )
        {
            $this->fixtures->create('credits',
                [
                    'type'  => 'fee',
                    'value' => $feeCredits,
                ]
            );
        }

        $this->fixtures->base->editEntity('balance', '10000000000000',
            [
                'balance'     => $balanceAmount,
                'fee_credits' => $feeCredits
            ]
        );

        $this->ba->privateAuth();

        return $payment['id'];
    }

    public function startTest($id = null, $amount = null)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->setRequestData($testData['request'], $id, $amount);

        return $this->runRequestResponseFlow($testData);
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
            $id = $this->payment['id'];
        }

        if ($amount === null)
        {
            if (isset($this->payment['amount']))
                $amount = $this->payment['amount'];
        }
    }

    private function createRemindersMock($withMethods)
    {
        $reminderMock = $this->getMockBuilder(Reminders::class)
            ->setConstructorArgs([$this->app])
            ->setMethods($withMethods)
            ->getMock();

        $this->app->instance('reminders', $reminderMock);

        return $reminderMock;
    }
}
