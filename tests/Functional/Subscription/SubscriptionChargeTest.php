<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionChargeTest extends TestCase
{
    use SubscriptionTrait;

    const MAX_AUTH_ATTEMPTS = 3;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->fixtures->create('terminal:shared_first_data_recurring_terminals');

        $this->gateway = 'first_data';

        $this->mockTokenex();
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

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, 'Asia/Kolkata');

        Carbon::setTestNow($chargeAt);

        $result = $this->makeSubscriptionChargeCronRequest();

        $this->assertEquals(1, $result['total']);

        $plan = $this->getLastEntity('plan', true);
        $item = $this->getLastEntity('item', true);
        $lineItems = $this->getEntities('line_item', [], true);
        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);
        $chargedPayment = $this->getLastEntity('payment', true);
        $subscription = $this->getLastEntity('subscription', true);
        $token = $this->getLastEntity('token', true);
        $scheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertEquals('cust_100000customer', $invoice['customer_id']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('paid', $invoice['status']);
        $this->assertNull($invoice['subscription_status']);
        $this->assertNull($invoice['email_status']);
        $this->assertEquals(2000, $invoice['amount']);
        $this->assertEquals($subscription['start_at'], $invoice['billing_start']);
        $this->assertEquals($subscription['current_end'], $invoice['billing_end']);
        $this->assertEquals($chargedPayment['id'], $invoice['payment_id']);

        $this->assertCount(1, $lineItems['items']);
        $lineItem = $lineItems['items'][0];
        $this->assertEquals($invoice['id'], 'inv_' . $lineItem['entity_id']);
        $this->assertEquals(2000, $lineItem['amount']);
        $this->assertEquals($plan['item']['name'], $lineItem['name']);
        $this->assertEquals($item['id'], $lineItem['item_id']);

        $this->assertEquals($plan['item']['id'], $item['id']);
        $this->assertEquals('plan', $item['type']);
        $this->assertEquals(2000, $item['amount']);

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(2000, $order['amount']);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertNull($subscription['error_status']);
        $this->assertEquals(0, $subscription['auth_attempts']);
        $this->assertEquals($subscription['start_at'], $subscription['current_start']);
        $expectedEndAt = Carbon::createFromTimestamp($subscription['start_at'], 'Asia/Kolkata')
                               ->addMonths(2)
                               ->startOfDay()
                               ->timestamp;
        $this->assertEquals($expectedEndAt, $subscription['current_end']);
        $this->assertEquals($expectedEndAt, $subscription['charge_at']);
        $this->assertNull($subscription['ended_at']);

        $this->assertEquals('captured', $chargedPayment['status']);
        $this->assertEquals(2000, $chargedPayment['amount']);
        $this->assertEquals($subscription['id'], $chargedPayment['subscription_id']);
        $this->assertEquals($order['id'], $chargedPayment['order_id']);
        $this->assertEquals($invoice['id'], $chargedPayment['invoice_id']);
        $this->assertEquals($token['id'], $chargedPayment['token_id']);
        $this->assertTrue($chargedPayment['recurring']);

        $this->assertNotNull($scheduleTask['last_run_at']);
        $this->assertEquals($subscription['charge_at'], $scheduleTask['next_run_at']);

        $allPayments = $this->getEntities('payment', [], true);

        $this->assertEquals(2, $allPayments['count']);

        Carbon::setTestNow();
    }

    public function testSubscriptionCompleteCycle()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        $expectedPaidCount = 0;
        // Subscription has only been authenticated, never paid
        $this->assertEquals($expectedPaidCount, $subscription['paid_count']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertNotNull($invoice);

        while ($expectedPaidCount < $subscription['total_count'])
        {
            $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
            // Subscription got charged
            $this->assertEquals(1, $result['total']);

            $expectedPaidCount++;

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

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertNull($subscription['charge_at']);

        $this->assertEquals($subscription['end_at'], $subscription['ended_at']);

        $invoices = $this->getEntities('invoice', [], true);

        $this->assertEquals($subscription['total_count'] + 1, $invoices['count']);

        // Long time from now
        $longTimeFromNow = Carbon::now()->addYear()->timestamp;
        $result = $this->chargeSubscriptionsViaCron($longTimeFromNow);

        // Subscription is complete, so will not get charged
        $this->assertEquals(0, $result['total']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionRetryAuth()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        // Subscription has only been authenticated, never paid
        $this->assertEquals(0, $subscription['paid_count']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $payment = $this->getLastEntity('payment', true);
        // First charge
        $this->assertEquals('captured', $payment['status']);

        // Subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);

        $this->failCharge();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $payment = $this->getLastEntity('payment', true);
        // But subscription charge failed
        $this->assertEquals('failed', $payment['status']);

        // Subscription marked as overdue
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('overdue', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);
        $this->assertEquals('auth_failure', $subscription['error_status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertNull($invoice['subscription_status']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Subscription is in overdue state, so won't be picked up by charge cron
        $this->assertEquals(0, $result['invoices_created']);
        $this->assertEquals('overdue', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        $this->passCharge();

        $result = $this->makeSubscriptionRetryCronRequest();
        // Subscription was queued
        $this->assertEquals(1, $result['queued']);

        // Retry succeeded, subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertNull($subscription['error_status']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($payment['id'], $invoice['payment_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('paid', $order['status']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionRetryCapture()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        // Subscription has only been authenticated, never paid
        $this->assertEquals(0, $subscription['paid_count']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $payment = $this->getLastEntity('payment', true);
        // First charge
        $this->assertEquals('captured', $payment['status']);

        // Subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);

        $this->failOnCapture($subscription);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $payment = $this->getLastEntity('payment', true);
        // But subscription charge failed
        $this->assertEquals('authorized', $payment['status']);

        // Subscription marked as overdue
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('overdue', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);
        $this->assertEquals('capture_failure', $subscription['error_status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertNull($invoice['subscription_status']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Subscription is in overdue state, so won't be picked up by charge cron
        $this->assertEquals(0, $result['invoices_created']);
        $this->assertEquals('overdue', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        $this->passOnCapture($subscription);

        $result = $this->makeSubscriptionRetryCronRequest();
        // Subscription was queued
        $this->assertEquals(1, $result['queued']);

        // Retry succeeded, subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertNull($subscription['error_status']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($payment['id'], $invoice['payment_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('paid', $order['status']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionRetryAuthAndThenCapture()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        // Subscription has only been authenticated, never paid
        $this->assertEquals(0, $subscription['paid_count']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $payment = $this->getLastEntity('payment', true);
        // First charge
        $this->assertEquals('captured', $payment['status']);

        // Subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);

        $this->failCharge();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);
        $invoice = $this->getLastEntity('invoice', true);

        $this->passCharge();
        $this->failOnCapture($subscription);

        $result = $this->makeSubscriptionRetryCronRequest();
        // Subscription was queued
        $this->assertEquals(1, $result['queued']);

        // Retry succeeded, subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('overdue', $subscription['status']);
        $this->assertEquals('capture_failure', $subscription['error_status']);
        $this->assertEquals(2, $subscription['auth_attempts']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);

        $this->passOnCapture($subscription);

        $result = $this->makeSubscriptionRetryCronRequest();
        // Subscription was queued
        $this->assertEquals(1, $result['queued']);

        // Retry succeeded, subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertNull($subscription['error_status']);
        $this->assertEquals(0, $subscription['auth_attempts']);

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals($payment['id'], $invoice['payment_id']);

        $order = $this->getLastEntity('order', true);
        $this->assertEquals('paid', $order['status']);

        $invoices = $this->getEntities('invoice', [], true);
        $this->assertEquals(3, $invoices['count']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionManualRetrySuccess()
    {
        $this->doAuthTxnForNewSubscription();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);

        $this->failCharge();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        foreach (range(1,2) as $i)
        {
            $this->failCharge();

            $result = $this->makeSubscriptionRetryCronRequest();
            $this->assertEquals(1, $result['queued']);
        }

        $invoices = $this->getEntities('invoice', [], true);
        $this->assertEquals(1, $invoices['count']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('on_hold', $subscription['status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('on_hold', $invoice['subscription_status']);

        $this->passCharge();

        $result = $this->chargeSubscriptionInvoiceManually($invoice);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $payments = $this->getEntities('payment', [], true);

        // 3 failures, 1 success, 1 auth txn
        $this->assertEquals(5, $payments['count']);

        $this->assertEquals('active', $subscription['status']);
        $this->assertNull($subscription['error_status']);
        $expectedChargeAt = Carbon::createFromTimestamp($subscription['start_at'], 'Asia/Kolkata')
                                  ->addMonths(2)
                                  ->startOfDay()
                                  ->timestamp;
        $this->assertEquals($expectedChargeAt, $subscription['charge_at']);

        $task = $this->getLastEntity('schedule_task', true);

        $this->assertEquals($expectedChargeAt, $task['next_run_at']);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($payments['items'][0]['id'], $invoice['payment_id']);

        Carbon::setTestNow();
    }

    public function testSubscriptionManualRetryFailure()
    {

    }

    public function testSubscriptionOnHoldAuthFailure()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        $expectedPaidCount = 0;

        // Subscription has only been authenticated, never paid
        $this->assertEquals($expectedPaidCount, $subscription['paid_count']);

        $this->failCharge();

        $subscription = $this->getLastEntity('subscription', true);
        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);
        $payment = $this->getLastEntity('payment', true);
        // But subscription charge failed
        $this->assertEquals('failed', $payment['status']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals(1, $subscription['auth_attempts']);

        while($subscription['auth_attempts'] < self::MAX_AUTH_ATTEMPTS)
        {
            // Subscription marked as overdue
            $this->assertEquals('overdue', $subscription['status']);

            $result = $this->makeSubscriptionRetryCronRequest();
            $this->assertEquals(1, $result['queued']);
            $subscription = $this->getLastEntity('subscription', true);
        }

        // Retries exhausted, subscription marked as on_hold
        $this->assertEquals('on_hold', $subscription['status']);

        $this->passCharge();

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        // Auth successful, subscription marked as active again
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionOnHoldCaptureFailure()
    {

    }

    public function testSubscriptionExpire()
    {
        // Subscription is created with start_at and with add_on
        $subscription = $this->createSubscription(true, [], [], true);

        // Subscription is not authenticated before start_at
        $expireBy = Carbon::createFromTimestamp(
            $subscription['start_at'] + 1,
            'Asia/Kolkata');

        Carbon::setTestNow($expireBy);
        $result = $this->makeSubscriptionExpireCronRequest();
        // Invoice got created
        $this->assertEquals(1, $result['expired']);

        // Subscription marked as expired
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('expired', $subscription['status']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionCycleWebhooks()
    {
        $this->createWebhook(
            [
                'events' => [
                    'subscription.activated' => '1',
                    'subscription.overdue'   => '1',
                    // TODO: Re-add when status name is updated
                    // 'subscription.on_hold'   => '1',
                ]
            ]);

        $this->doAuthTxnForSubscriptionWithAddOn();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);

        // subscription.activated event fired after first charge
        $this->mockAndTestWebhookData('subscription.activated');
        $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        $this->failCharge();
        // subscription.overdue event fired after
        // first and second failed charge attempts
        $this->mockAndTestWebhookData('subscription.overdue', self::MAX_AUTH_ATTEMPTS - 1);
        // First failure
        $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('overdue', $subscription['status']);

        // Second failure
        $this->makeSubscriptionRetryCronRequest();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('overdue', $subscription['status']);

        // subscription.on_hold event fired after final failed charge
        // TODO: Uncomment after unhold is renamed
        // $this->mockAndTestWebhookData('subscription.on_hold');

        // Third failure
        $this->makeSubscriptionRetryCronRequest();
        $subscription = $this->getLastEntity('subscription', true);
        // Retries exhausted, subscription marked as on_hold
        $this->assertEquals('on_hold', $subscription['status']);

        $this->passCharge();
        // subscription.on_hold event fired after successful re-auth
        $this->mockAndTestWebhookData('subscription.activated');
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $recurringPayment = $this->doAuthPayment($paymentRequest);

        // Auth successful, subscription marked as active again
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        // Reset time
        Carbon::setTestNow();
    }
}
