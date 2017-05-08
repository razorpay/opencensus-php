<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionChargeTest extends TestCase
{
    use SubscriptionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['recurring', 'tokens']);

        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');

        $this->gateway = 'first_data';

        $this->mockTokenex();
    }

    public function testSubscriptionCompleteCycle()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        $expectedPaidCount = 0;
        // Subscription has only been authenticated, never paid
        $this->assertEquals($expectedPaidCount, $subscription['paid_count']);

        while ($expectedPaidCount < $subscription['total_count'])
        {
            $chargeAt = Carbon::createFromTimestamp($subscription['charge_at']+1);
            Carbon::setTestNow($chargeAt, 'Asia/Kolkata');
            $result = $this->makeSubscriptionChargeCronRequest();
            // Subscription got charged
            $this->assertEquals(1, $result['total']);

            $expectedPaidCount += 1;

            $subscription = $this->getLastEntity('subscription', true);
            $this->assertEquals($expectedPaidCount, $subscription['paid_count']);

            $expectedStatus = 'active';

            // After large charge, subscription is marked completed
            if ($expectedPaidCount === $subscription['total_count'])
            {
                $expectedStatus = 'completed';
            }
            $this->assertEquals($expectedStatus, $subscription['status']);
        }

        $this->assertNull($subscription['charge_at']);
        $this->assertNotNull($subscription['ended_at']);

        // Long time from now
        Carbon::setTestNow(Carbon::now()->addYear(), 'Asia/Kolkata');
        $result = $this->makeSubscriptionChargeCronRequest();
        // Subscription is complete, so will not get charged
        $this->assertEquals(0, $result['total']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionRetry()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        $expectedPaidCount = 0;
        // Subscription has only been authenticated, never paid
        $this->assertEquals($expectedPaidCount, $subscription['paid_count']);

        $this->failCharge();

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at']+1);
        Carbon::setTestNow($chargeAt, 'Asia/Kolkata');
        $result = $this->makeSubscriptionChargeCronRequest();
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $payment = $this->getLastEntity('payment', true);
        // But subscription charge failed
        $this->assertEquals('failed', $payment['status']);

        // Subscription marked as overdue
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('overdue', $subscription['status']);

        $this->passCharge();

        $result = $this->makeSubscriptionRetryCronRequest();
        $this->assertEquals(1, $result['queued']);

        // Retry succeeded, subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionExpire()
    {
        // Subscription is created with start_at and with add_on
        $subscription = $this->createSubscription(true, [], [], true);

        // Subscription is not authenticated before start_at
        $expireBy = Carbon::createFromTimestamp($subscription['start_at']+1, 'Asia/Kolkata');
        Carbon::setTestNow($expireBy);
        $result = $this->makeSubscriptionExpireCronRequest();
        // Invoice got created
        $this->assertEquals(1, $result['expired']);

        // Subscription marked as expired
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('expired', $subscription['status']);

        Carbon::setTestNow();
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

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1);

        Carbon::setTestNow($chargeAt, 'Asia/Kolkata');

        $result = $this->makeSubscriptionChargeCronRequest();

        $this->assertEquals(1, $result['total']);

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
}
