<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;

class SubscriptionCancelTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;

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

    public function testSubscriptionCancelBasic()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $subscriptionId = $subscription['id'];

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/subscriptions/' . $subscriptionId . '/cancel';

        $this->ba->privateAuth();
        $this->startTest($testData);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertNotNull($subscription['ended_at']);

        Carbon::setTestNow();
    }

    public function testSubscriptionChargeAfterCancel()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);

        Carbon::setTestNow($chargeAt);

        $this->makeSubscriptionChargeCronRequest();

        $this->createWebhook(
            [
                'events' => ['subscription.cancelled' => '1']
            ]);

        $this->mockAndTestWebhookDataCustom('subscription.cancelled', 'subscriptionWebhookDataForCancel');

        $this->makeCancelRequest($subscription['id']);

        $subscription = $this->getLastEntity('subscription', true);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $this->assertEquals(0, $result['invoices_created']);

        Carbon::setTestNow();
    }

    public function testSubscriptionCancelWhenPending()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $this->failCharge();

        $subscription = $this->getLastEntity('subscription', true);

        $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);
        $this->assertEquals('auth_failure', $subscription['error_status']);

        $this->makeCancelRequest($subscription['id']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('cancelled', $subscription['status']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $this->assertEquals(0, $result['invoices_created']);
        $this->assertEquals('cancelled', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);

        Carbon::setTestNow();
    }

    public function testSubscriptionCancelWhenHalted()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->failCharge();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        foreach (range(1,3) as $i)
        {
            $this->failCharge();

            $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'], Timezone::IST)
                ->addDay(1)
                ->addMinute(1);

            Carbon::setTestNow($chargeAt);

            $result = $this->makeSubscriptionRetryCronRequest();
            $this->assertEquals(1, $result['queued']);

            $subscription = $this->getLastEntity('subscription', true);
        }

        $invoices = $this->getEntities('invoice', [], true);
        $this->assertEquals(2, $invoices['count']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('halted', $subscription['status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $this->clearMock();

        $this->makeCancelRequest($subscription['id']);

        Carbon::setTestNow();

        try
        {
            $this->chargeSubscriptionInvoiceManually($invoice);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('BAD_REQUEST_SUBSCRIPTION_NOT_IN_ACTIVE_OR_HALTED_STATE', $ex->getCode());

            Carbon::setTestNow();

            return;
        }

        $this->assertTrue(false);

        Carbon::setTestNow();
    }

    public function testFutureCancellation()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, 'Asia/Kolkata');

        Carbon::setTestNow($chargeAt);

        $this->makeSubscriptionChargeCronRequest();

        $this->makeCancelRequest($subscription['id'], '1');

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals($subscription['cancel_at'], $subscription['current_end']);
        $this->assertNotEquals($subscription['cancel_at'], $subscription['cancelled_at']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        // Charge cron should not pick this up
        $this->assertEquals(0, $result['invoices_created']);

        $cancelAt = Carbon::createFromTimestamp($subscription['cancel_at'] + 10, 'Asia/Kolkata');

        Carbon::setTestNow($cancelAt);

        $this->createWebhook(
            [
                'events' => ['subscription.cancelled' => '1']
            ]);

        $this->mockAndTestWebhookDataCustom('subscription.cancelled', 'subscriptionWebhookDataForCancel');

        $result = $this->makeSubscriptionCancelDueRequest();

        // Charge cron should not pick this up
        $this->assertEquals(1, $result['queued']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('cancelled', $subscription['status']);

        Carbon::setTestNow();
    }

    public function testFutureCancellationLastCycle()
    {
        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        foreach (range(1,5) as $i)
        {
            $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'], 'Asia/Kolkata')
                              ->addDay(1)
                              ->addMinute(1);

            Carbon::setTestNow($chargeAt);

            $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
            $this->assertEquals(1, $result['invoices_created']);

            $subscription = $this->getLastEntity('subscription', true);
        }

        $invoices = $this->getEntities('invoice', [], true);
        $this->assertEquals(5, $invoices['count']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(5, $subscription['paid_count']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('paid', $invoice['status']);

        try
        {
            $this->makeCancelRequest($subscription['id'], '1');
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('BAD_REQUEST_SUBSCRIPTION_LAST_CYCLE_CANNOT_CANCEL', $ex->getCode());

            Carbon::setTestNow();

            return;
        }

        $this->assertTrue(false);

        Carbon::setTestNow();
    }

    public function testCancelFutureCancellation()
    {
        $this->markTestSkipped("not doing this now. will figure out a way to do this later");

        $this->doAuthTxnForNewSubscription();

        $subscription = $this->getLastEntity('subscription', true);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, 'Asia/Kolkata');

        Carbon::setTestNow($chargeAt);

        $this->makeSubscriptionChargeCronRequest();

        $this->makeCancelRequest($subscription['id'], '1');

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals($subscription['cancel_at'], $subscription['current_end']);
        $this->assertNotEquals($subscription['cancel_at'], $subscription['cancelled_at']);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, 'Asia/Kolkata');

        $cancelAt = Carbon::createFromTimestamp($subscription['cancel_at'] - 3600, 'Asia/Kolkata');

        Carbon::setTestNow($cancelAt);

        $this->makeCancelRequest($subscription['id'], '0');

        Carbon::setTestNow($chargeAt);

        $result = $this->makeSubscriptionChargeCronRequest();
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals(2, $subscription['paid_count']);
        $this->assertNull($subscription['cancel_at']);
        $this->assertNull($subscription['cancelled_at']);
        $this->assertEquals('active', $subscription['status']);

        Carbon::setTestNow();
    }
}
