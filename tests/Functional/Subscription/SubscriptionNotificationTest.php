<?php

namespace RZP\Tests\Functional\Subscription;

use Mail;
use Carbon\Carbon;

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

    public function testSubscriptionAuthenticatedMailSentAuthAmount()
    {
        Mail::fake();

        $this->doAuthTxnForNewSubscription();

        Mail::assertSent(SubscriptionMail\Authenticated::class, function ($mail)
        {
            $data = $mail->viewData;

            $this->assertEquals('authenticated', $data['subscription']['status']);
            $this->assertEquals(0, $data['subscription']['type']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            // Token charge
            $this->assertEquals('₹ 5', $data['payment']['amount']);
            $this->assertContains('Card', $data['payment']['method']);
            $this->assertContains('XXXX-XXXX-XXXX-3335', $data['payment']['method']);

            $this->assertEquals('#C15482', $data['card']['color']);
            $this->assertContains('12/2017', $data['card']['expiry']);
            $this->assertContains('VISA', $data['card']['network']);
            $this->assertContains('**** **** **** 3335', $data['card']['number']);

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
            // $this->assertEquals(, $data['subscription']['charge_at']);

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
            // $this->assertEquals(, $data['subscription']['charge_at']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

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
            // $this->assertEquals(, $data['subscription']['charge_at']);

            $this->assertEquals('10000000000000', $data['merchant']['id']);

            $this->assertEquals('test@razorpay.com', $data['customer']['email']);
            $this->assertEquals('1234567890', $data['customer']['phone']);

            return true;
        });
    }
}
