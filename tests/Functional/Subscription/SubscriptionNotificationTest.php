<?php

namespace RZP\Tests\Functional\Subscription;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Mail\Subscription as SubscriptionMail;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;

class SubscriptionNotificationTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;

    const MAX_AUTH_ATTEMPTS = 4;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->gateway = 'cybersource';

        $this->mockTokenex();
    }

    public function tearDown()
    {
        parent::tearDown();

        Carbon::setTestNow();
    }

    public function testSubscriptionMailNotSent()
    {
        Mail::fake();

        $this->fixtures->merchant->edit(
            '10000000000000',
            [
                'receipt_email_enabled'    => '0',
                'transaction_report_email' => '',
            ]);

        $this->doAuthTxnForNewSubscription();

        Mail::assertNothingSent();
    }

    public function testSubscriptionAuthenticatedMailSentAuthAmount()
    {
        Mail::fake();

        $this->doAuthTxnForNewSubscription();

        Mail::assertSent(SubscriptionMail\Authenticated::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('authenticated', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);
            $this->assertEquals('20 Jan 2018', $data['subscription']['charge_at']);
            $this->assertStringStartsWith(
                'https://api.razorpay.com/v1/t/subscriptions',
                $data['subscription']['hosted_url']);

            $this->assertEquals('test plan', $data['plan_item']['name']);
            $this->assertEquals('Some item description', $data['plan_item']['description']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Token charge
            $this->assertEquals('₹ 5', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);
            $this->assertNull($data['payment']['captured_at']);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

            $this->assertEquals(false, $data['options']['immediate']);
            $this->assertEquals(true, $data['options']['auto_refund']);

            return true;
        });
    }

    public function testSubscriptionAuthenticatedMailSentImmediateWithoutAddon()
    {
        Mail::fake();

        $this->doAuthTxnForSubscriptionImmediateWithoutAddOn();

        $subscription = $this->getLastEntity('subscription', true);

        Mail::assertSent(SubscriptionMail\Authenticated::class, function ($mail) use ($subscription)
        {
            $data = $mail->viewData;

            $this->assertEquals('active', $data['subscription']['status']);
            $this->assertEquals(1, $data['subscription']['type']);

            $this->assertEquals('test plan', $data['plan_item']['name']);
            $this->assertEquals('Some item description', $data['plan_item']['description']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Plan amount
            $this->assertEquals('₹ 20', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);
            $this->assertNotNull($data['payment']['captured_at']);

            $currentStart = Carbon::createFromTimestamp($subscription['current_start'], Timezone::IST)->format('j M Y');
            $currentEnd   = Carbon::createFromTimestamp($subscription['current_end'], Timezone::IST)->format('j M Y');

            $this->assertEquals($currentStart, $data['invoice']['billing_start']);
            $this->assertEquals($currentEnd, $data['invoice']['billing_end']);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

            $this->assertEquals(true, $data['options']['immediate']);
            $this->assertEquals(false, $data['options']['auto_refund']);

            return true;
        });
    }

    public function testSubscriptionAuthenticatedMailSentWithAddon()
    {
        Mail::fake();

        $this->doAuthTxnForSubscriptionWithAddOn();

        Mail::assertSent(SubscriptionMail\Authenticated::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('authenticated', $data['subscription']['status']);
            $this->assertEquals(2, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Addon amount
            $this->assertEquals('₹ 3', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            $this->assertNull($data['invoice']['billing_start']);
            $this->assertNull($data['invoice']['billing_end']);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

            $this->assertEquals(false, $data['options']['immediate']);
            $this->assertEquals(false, $data['options']['auto_refund']);

            return true;
        });
    }

    public function testSubscriptionAuthenticatedMailSentImmediateWithAddon()
    {
        Mail::fake();

        $this->doAuthTxnForSubscriptionImmediateWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);

        Mail::assertSent(SubscriptionMail\Authenticated::class, function ($mail) use ($subscription)
        {
            $data = $mail->viewData;

            $this->assertEquals('active', $data['subscription']['status']);
            $this->assertEquals(3, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Addon amount plus plan amount
            $this->assertEquals('₹ 23', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            $currentStart = Carbon::createFromTimestamp($subscription['current_start'], Timezone::IST)->format('j M Y');
            $currentEnd   = Carbon::createFromTimestamp($subscription['current_end'], Timezone::IST)->format('j M Y');

            $this->assertEquals($currentStart, $data['invoice']['billing_start']);
            $this->assertEquals($currentEnd, $data['invoice']['billing_end']);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

            $this->assertEquals(true, $data['options']['immediate']);
            $this->assertEquals(false, $data['options']['auto_refund']);

            return true;
        });
    }

    public function testSubscriptionChargedMailSent()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        Mail::fake();

        $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        Mail::assertSent(SubscriptionMail\Charged::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('active', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Plan amount
            $this->assertEquals('₹ 20', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            $this->assertNotNull($data['invoice']['billing_start']);
            $this->assertNotNull($data['invoice']['billing_end']);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

            $this->assertEquals(false, $data['options']['card_change']);

            return true;
        });
    }

    public function testSubscriptionChargedAndCardChangeMailSent()
    {
        $subscription = $this->failSubscriptionFirstCharge();

        $paymentRequest = $this->getSubscriptionCardChangeRequest($subscription);

        Mail::fake();

        $this->mockSession();

        $this->doAuthPayment($paymentRequest);

        $this->flushSession();

        $subscription = $this->getLastEntity('subscription', true);

        Mail::assertSent(SubscriptionMail\Charged::class, function ($mail) use ($subscription)
        {
            $data = $mail->viewData;

            $this->assertEquals('active', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Plan amount
            $this->assertEquals('₹ 20', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            // Invoice created for the charge
            $currentStart = Carbon::createFromTimestamp($subscription['current_start'], Timezone::IST)->format('j M Y');
            $currentEnd   = Carbon::createFromTimestamp($subscription['current_end'], Timezone::IST)->format('j M Y');

            $this->assertEquals($currentStart, $data['invoice']['billing_start']);
            $this->assertEquals($currentEnd, $data['invoice']['billing_end']);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

            $this->assertEquals(true, $data['options']['card_change']);

            return true;
        });
    }

    public function testSubscriptionCardChangeMailSent()
    {
        $subscription = $this->failSubscriptionTillHalted();

        $paymentRequest = $this->getSubscriptionCardChangeRequest($subscription);

        Mail::fake();

        $this->mockSession();

        $this->doAuthPayment($paymentRequest);

        $this->flushSession();

        $subscription = $this->getLastEntity('subscription', true);

        Mail::assertSent(SubscriptionMail\CardChanged::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('active', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Token amount
            $this->assertEquals('₹ 5', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            // No invoice generated for token card change
            $this->assertArrayNotHasKey('invoice', $data);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

            $this->assertEquals('halted', $data['options']['old_status']);

            return true;
        });
    }

    public function testSubscriptionCancelledMailSent()
    {
        $this->doAuthTxnForSubscriptionImmediateWithoutAddOn();

        $subscription = $this->getLastEntity('subscription', true);

        Mail::fake();

        $this->makeCancelRequest($subscription['id']);

        Mail::assertSent(SubscriptionMail\Cancelled::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('cancelled', $data['subscription']['status']);
            $this->assertEquals(1, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            $this->assertArrayNotHasKey('invoice', $data);
            $this->assertArrayNotHasKey('payment', $data);

            $this->assertEquals(false, $data['options']['future_cancel']);

            return true;
        });
    }

    public function testSubscriptionInvoiceChargedMailSent()
    {
        $subscription = $this->failSubscriptionTillHalted();

        $oldInvoice = $this->getLastEntity('invoice', true);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $this->assertEquals(1, $result['invoices_created']);
        $this->assertInvoiceCount(2, $subscription['id']);

        Mail::fake();

        $this->chargeSubscriptionInvoiceManually($oldInvoice);

        Mail::assertSent(SubscriptionMail\InvoiceCharged::class, function ($mail) use ($oldInvoice)
        {
            $data = $mail->viewData;

            $this->assertEquals('active', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            $this->assertEquals('₹ 20', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            $billingStart = Carbon::createFromTimestamp($oldInvoice['billing_start'], Timezone::IST)->format('j M Y');
            $billingEnd   = Carbon::createFromTimestamp($oldInvoice['billing_end'], Timezone::IST)->format('j M Y');

            $this->assertEquals($billingStart, $data['invoice']['billing_start']);
            $this->assertEquals($billingEnd, $data['invoice']['billing_end']);

            return true;
        });
    }

    public function testSubscriptionPendingMailSent()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $this->failCharge();

        Mail::fake();

        $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        Mail::assertSent(SubscriptionMail\Pending::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('pending', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            $this->assertArrayNotHasKey('invoice', $data);
            $this->assertArrayNotHasKey('payment', $data);

            $this->assertEmpty($data['options']);

            return true;
        });
    }

    public function testSubscriptionHaltedMailSent()
    {
        $this->doAuthTxnForNewSubscription();

        $this->failCharge();

        $subscription = $this->getLastEntity('subscription', true);

        $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);

        while($subscription['auth_attempts'] < 3)
        {
            $this->retrySubscriptionsViaCron($subscription['charge_at']);

            $subscription = $this->getLastEntity('subscription', true);
        }

        Mail::fake();

        $this->retrySubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);

        Mail::assertSent(SubscriptionMail\Halted::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('halted', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            $this->assertArrayNotHasKey('invoice', $data);
            $this->assertArrayNotHasKey('payment', $data);

            $this->assertEmpty($data['options']);

            return true;
        });
    }

    public function testSubscriptionCompletedMailSent()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        while ($subscription['paid_count'] < $subscription['total_count'] - 1)
        {
            $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

            $subscription = $this->getLastEntity('subscription', true);
        }

        $this->assertEquals('active', $subscription['status']);

        Mail::fake();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);

        Mail::assertSent(SubscriptionMail\Completed::class, function ($mail) use ($subscription)
        {
            $data = $mail->viewData;

            $this->assertEquals('completed', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Plan amount
            $this->assertEquals('₹ 20', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            $currentStart = Carbon::createFromTimestamp($subscription['current_start'], Timezone::IST)->format('j M Y');
            $currentEnd   = Carbon::createFromTimestamp($subscription['current_end'], Timezone::IST)->format('j M Y');

            $this->assertEquals($currentStart, $data['invoice']['billing_start']);
            $this->assertEquals($currentEnd, $data['invoice']['billing_end']);

            $this->assertEquals(true, $data['options']['charge_success']);

            return true;
        });
    }

    public function testSubscriptionCompletedMailSentFromPending()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        while ($subscription['paid_count'] < $subscription['total_count'] - 1)
        {
            $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

            $subscription = $this->getLastEntity('subscription', true);
        }

        $this->assertEquals('active', $subscription['status']);

        $this->failCharge();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);

        $this->clearMock();

        Mail::fake();

        $this->retrySubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);

        Mail::assertSent(SubscriptionMail\Completed::class, function ($mail) use ($subscription)
        {
            $data = $mail->viewData;

            $this->assertEquals('completed', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Plan amount
            $this->assertEquals('₹ 20', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            $currentStart = Carbon::createFromTimestamp($subscription['current_start'], Timezone::IST)->format('j M Y');
            $currentEnd   = Carbon::createFromTimestamp($subscription['current_end'], Timezone::IST)->format('j M Y');

            $this->assertEquals($currentStart, $data['invoice']['billing_start']);
            $this->assertEquals($currentEnd, $data['invoice']['billing_end']);

            $this->assertEquals(true, $data['options']['charge_success']);

            return true;
        });
    }

    public function testSubscriptionCompletedMailSentOnFailure()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        while ($subscription['paid_count'] < $subscription['total_count'] - 1)
        {
            $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

            $subscription = $this->getLastEntity('subscription', true);
        }

        $this->assertEquals('active', $subscription['status']);

        $this->failCharge();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);

        while($subscription['auth_attempts'] < 3)
        {
            $this->retrySubscriptionsViaCron($subscription['charge_at']);

            $subscription = $this->getLastEntity('subscription', true);
        }

        Mail::fake();

        $this->retrySubscriptionsViaCron($subscription['charge_at']);

        Mail::assertSent(SubscriptionMail\Completed::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('completed', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            $this->assertArrayNotHasKey('invoice', $data);
            $this->assertArrayNotHasKey('payment', $data);

            $this->assertEquals(false, $data['options']['charge_success']);

            return true;
        });
    }

    public function testSubscriptionCompletedMailNotSentFromHalted()
    {
        $this->failSubscriptionTillHalted();

        $subscription = $this->getLastEntity('subscription', true);

        foreach(range(1, $subscription['total_count']-2) as $i)
        {
            $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

            $subscription = $this->getLastEntity('subscription', true);
            $this->assertEquals('halted', $subscription['status']);
        }

        Mail::fake();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('completed', $subscription['status']);

        Mail::assertNotSent(SubscriptionMail\Completed::class);
    }
}
