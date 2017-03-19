<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionChargeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['recurring', 'tokens']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->mockTokenex();
    }

    public function testSubscriptionCreateAuthTxnAllCharges()
    {
        // Test creation of plan, subscription
        // make auth transaction
        // make all the charges
    }

    public function testSubscriptionFirstCharge()
    {
        $details = $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertEquals('authenticated', $subscription['status']);
        $this->assertEquals(0, $subscription['auth_attempts']);
        $this->assertNotNull($subscription['end_at']);

        $authPayment = $this->getLastEntity('payment', true);

        $this->assertEquals($details['payment_id'], $authPayment['id']);
        $this->assertEquals($subscription['id'], $authPayment['subscription_id']);
        $this->assertEquals(500, $authPayment['amount']);
        $this->assertEquals('refunded', $authPayment['status']);

        Carbon::setTestNow(Carbon::createFromTimestamp($subscription['charge_at'], 'Asia/Kolkata'));

        $result = $this->makeCreateSubscriptionInvoicesRequest();

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['invoices_created']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);

        $result = $this->makeChargeCronRequest();

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['queued']);

        $subscription = $this->getLastEntity('subscription', true);
        $chargedPayment = $this->getLastEntity('payment', true);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);

        $this->assertEquals('captured', $chargedPayment['status']);
        $this->assertEquals(2000, $chargedPayment['amount']);
        $this->assertEquals($subscription['id'], $chargedPayment['subscription_id']);

        $allPayments = $this->getEntities('payment', [], true);

        $this->assertEquals(2, $allPayments['count']);

        Carbon::setTestNow();
    }

    protected function doAuthTxnForNewSubscription($plan = null, $subscription = null)
    {
        if ($plan === null)
        {
            $plan = $this->fixtures->create('plan');
        }

        if ($subscription === null)
        {
            // This is required so that the auth transaction
            // is not made with past start_at.
            $currentTime = time() + 10;

            $subscription = $this->fixtures->create(
                'subscription',
                [
                    'plan_id' => $plan->getId(),
                    'start_at' => $currentTime,
                    'end_at' => $currentTime + 20736000, // add 8 months
                    'charge_at' => $currentTime,
                ]);
        }

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        return [
            'subscription_id'   => $subscription->getId(),
            'plan_id'           => $plan->getId(),
            'payment_id'        => $recurringPayment['razorpay_payment_id'],
        ];
    }
}
