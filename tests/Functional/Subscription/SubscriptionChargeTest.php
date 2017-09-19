<?php

namespace RZP\Tests\Functional\Subscription;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use Mockery;

use RZP\Models\Item;
use RZP\Models\Plan\Subscription\Addon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class SubscriptionChargeTest extends TestCase
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

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);

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
        $expectedEndAt = Carbon::createFromTimestamp($subscription['start_at'], Timezone::IST)
                               ->addMonthsNoOverflow(2)
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

        // Subscription marked as pending
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);
        $this->assertEquals('auth_failure', $subscription['error_status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertNull($invoice['subscription_status']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Subscription is in pending state, so won't be picked up by charge cron
        $this->assertEquals(0, $result['invoices_created']);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        $this->clearMock();

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

        $this->failOnCapture();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $payment = $this->getLastEntity('payment', true);
        // But subscription charge failed
        $this->assertEquals('authorized', $payment['status']);

        // Subscription marked as pending
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);
        $this->assertEquals('capture_failure', $subscription['error_status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertNull($invoice['subscription_status']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Subscription is in pending state, so won't be picked up by charge cron
        $this->assertEquals(0, $result['invoices_created']);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        $task = $this->getLastEntity('schedule_task', true);
        // First success, then fail
        $expectedNextRun = Carbon::createFromTimestamp($subscription['start_at'], Timezone::IST)
                                 ->addMonthsNoOverflow(2)
                                 ->addDays(1)
                                 ->timestamp;
        $this->assertEquals($expectedNextRun, $task['next_run_at']);

        $this->clearMock();

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

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('failed', $payment['status']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);

        $this->clearMock();
        $this->failOnCapture();

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'], Timezone::IST)
                          ->addDay(1)
                          ->addMinute(1);

        Carbon::setTestNow($chargeAt);

        $result = $this->makeSubscriptionRetryCronRequest();
        // Subscription was queued
        $this->assertEquals(1, $result['queued']);

        // Retry succeeded, subscription marked as active
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals('capture_failure', $subscription['error_status']);
        $this->assertEquals(2, $subscription['auth_attempts']);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('authorized', $payment['status']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);

        $this->clearMock();

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'], Timezone::IST)
                          ->addDay(1)
                          ->addMinute(1);

        Carbon::setTestNow($chargeAt);

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
        $this->assertEquals(1, $invoices['count']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('halted', $subscription['status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $this->clearMock();

        $result = $this->chargeSubscriptionInvoiceManually($invoice);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $payments = $this->getEntities('payment', [], true);

        // 3 failures, 1 success, 1 auth txn
        $this->assertEquals(6, $payments['count']);

        $this->assertEquals('active', $subscription['status']);
        $this->assertNull($subscription['error_status']);
        $expectedChargeAt = Carbon::createFromTimestamp($subscription['start_at'], Timezone::IST)
                                  ->addMonthsNoOverflow(2)
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
        $this->doAuthTxnForNewSubscription();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);

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
        $this->assertEquals(1, $invoices['count']);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals('halted', $subscription['status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $this->failCharge();

        $result = $this->chargeSubscriptionInvoiceManually($invoice);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $payments = $this->getEntities('payment', [], true);

        // 4 failures, 1 success, 1 auth txn
        $this->assertEquals(6, $payments['count']);

        $this->assertEquals('halted', $subscription['status']);
        $this->assertEquals('auth_failure', $subscription['error_status']);
        $expectedChargeAt = Carbon::createFromTimestamp($subscription['start_at'], Timezone::IST)
                                  ->addMonthsNoOverflow(2)
                                  ->startOfDay()
                                  ->timestamp;
        $this->assertEquals($expectedChargeAt, $subscription['charge_at']);

        $task = $this->getLastEntity('schedule_task', true);

        // In manual flow, the task's next_run should not change
        $this->assertEquals($expectedChargeAt, $task['next_run_at']);

        $this->assertEquals('issued', $invoice['status']);
        $this->assertNull($invoice['payment_id']);

        Carbon::setTestNow();
    }

    public function testSubscriptionHaltedAuthFailure()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        $expectedPaidCount = 0;

        // Subscription has only been authenticated, never paid
        $this->assertEquals($expectedPaidCount, $subscription['paid_count']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

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
            // Subscription marked as pending
            $this->assertEquals('pending', $subscription['status']);

            $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'], Timezone::IST)
                              ->addDay(1)
                              ->addMinute(1);

            Carbon::setTestNow($chargeAt);

            $result = $this->makeSubscriptionRetryCronRequest();
            $this->assertEquals(1, $result['queued']);
            $subscription = $this->getLastEntity('subscription', true);
        }

        // Retries exhausted, subscription marked as halted
        $this->assertEquals('halted', $subscription['status']);

        $this->clearMock();

        $task = $this->getLastEntity('schedule_task', true);
        Carbon::setTestNow(Carbon::createFromTimestamp($task['next_run_at'] + 1));

        $subscription = $this->getLastEntity('subscription', true);

        $result = $this->chargeSubscriptionsViaCron();
        $this->assertEquals(1, $result['invoices_created']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('halted', $subscription['status']);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        // Auth successful, subscription marked as active again
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertEquals('auth_failure', $subscription['error_status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('halted', $invoice['subscription_status']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2000, $invoice['amount']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionHaltedCaptureFailure()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        $expectedPaidCount = 0;

        // Subscription has only been authenticated, never paid
        $this->assertEquals($expectedPaidCount, $subscription['paid_count']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        $this->failOnCapture();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);

        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);
        $payment = $this->getLastEntity('payment', true);
        // But subscription charge failed
        $this->assertEquals('authorized', $payment['status']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals(1, $subscription['auth_attempts']);

        $task = $this->getLastEntity('schedule_task', true);

        while($subscription['auth_attempts'] < self::MAX_AUTH_ATTEMPTS)
        {
            // Subscription marked as pending
            $this->assertEquals('pending', $subscription['status']);

            $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'], Timezone::IST)
                              ->addDay(1)->addMinute(1);

            Carbon::setTestNow($chargeAt);

            $result = $this->makeSubscriptionRetryCronRequest();
            $this->assertEquals(1, $result['queued']);
            $subscription = $this->getLastEntity('subscription', true);
        }

        // Retries exhausted, subscription marked as halted
        $this->assertEquals('halted', $subscription['status']);

        $task = $this->getLastEntity('schedule_task', true);
        Carbon::setTestNow(Carbon::createFromTimestamp($task['next_run_at'] + 1));

        $subscription = $this->getLastEntity('subscription', true);

        $result = $this->chargeSubscriptionsViaCron();
        $this->assertEquals(1, $result['invoices_created']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('halted', $subscription['status']);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $this->clearMock();

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        // Auth successful, subscription marked as active again
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertEquals('capture_failure', $subscription['error_status']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('halted', $invoice['subscription_status']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2000, $invoice['amount']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSubscriptionHaltedInvoicesCreate()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $subscription = $this->getLastEntity('subscription', true);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        // Invoice got created
        $this->assertEquals(1, $result['invoices_created']);

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
            // Subscription marked as pending
            $this->assertEquals('pending', $subscription['status']);

            $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'], Timezone::IST)
                ->addDay(1)
                ->addMinute(1);

            Carbon::setTestNow($chargeAt);

            $result = $this->makeSubscriptionRetryCronRequest();
            $this->assertEquals(1, $result['queued']);
            $subscription = $this->getLastEntity('subscription', true);
        }

        // Retries exhausted, subscription marked as halted
        $this->assertEquals('halted', $subscription['status']);

        $this->clearMock();

        $task = $this->getLastEntity('schedule_task', true);
        $firstRunAt = $task['next_run_at'];
        Carbon::setTestNow(Carbon::createFromTimestamp($firstRunAt + 1));

        $subscription = $this->getLastEntity('subscription', true);

        $result = $this->chargeSubscriptionsViaCron();
        $this->assertEquals(1, $result['invoices_created']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $subscription = $this->getLastEntity('subscription', true);
        $task = $this->getLastEntity('schedule_task', true);
        $secondRunAt = $task['next_run_at'];
        // second run should be at least 50 days from first run (2 months ahead)
        $this->assertGreaterThan($firstRunAt + 4320000, $secondRunAt);
        $this->assertEquals($secondRunAt, $subscription['charge_at']);
        Carbon::setTestNow(Carbon::createFromTimestamp($secondRunAt + 1));

        $result = $this->chargeSubscriptionsViaCron();
        $this->assertEquals(1, $result['invoices_created']);

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $task = $this->getLastEntity('schedule_task', true);
        $thirdRunAt = $task['next_run_at'];
        // third run should be at least 50 days from second run (2 months ahead)
        $this->assertGreaterThan($secondRunAt + 4320000, $thirdRunAt);
    }

    public function testSubscriptionExpire()
    {
        // Subscription is created with start_at and with add_on
        $subscription = $this->createSubscription(true, [], [], true);

        // Subscription is not authenticated before start_at
        $expireBy = Carbon::createFromTimestamp(
            $subscription['start_at'] + 1,
            Timezone::IST);

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
                    'subscription.pending'   => '1',
                    'subscription.halted'    => '1',
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
        $this->assertEquals(1, $subscription['paid_count']);

        $this->failCharge();
        // subscription.pending event fired after
        // first and second failed charge attempts
        $this->mockAndTestWebhookData('subscription.pending', self::MAX_AUTH_ATTEMPTS - 1);
        // First failure
        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals('auth_failure', $subscription['error_status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);
        Carbon::setTestNow($chargeAt);

        // Second failure
        $res = $this->makeSubscriptionRetryCronRequest();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(2, $subscription['auth_attempts']);

        // $task = $this->getLastEntity('schedule_task', true);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);
        Carbon::setTestNow($chargeAt);

        // Third failure
        $this->makeSubscriptionRetryCronRequest();
        $subscription = $this->getLastEntity('subscription', true);

        // subscription.halted event fired after final failed charge
        $this->mockAndTestWebhookData('subscription.halted');

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);
        Carbon::setTestNow($chargeAt);

        // Fourth failure
        $this->makeSubscriptionRetryCronRequest();
        $subscription = $this->getLastEntity('subscription', true);

        // Retries exhausted, subscription marked as halted
        $this->assertEquals('halted', $subscription['status']);
        $this->assertEquals(4, $subscription['auth_attempts']);

        $this->clearMock();
        // subscription.halted event fired after successful re-auth
        $this->mockAndTestWebhookData('subscription.activated');
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $recurringPayment = $this->doAuthPayment($paymentRequest);

        // Auth successful, subscription marked as active again
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testAuthFailurePendingWebhookEventData()
    {
        $this->createWebhook(
            [
                'events' => [
                    'subscription.activated' => '1',
                    'subscription.pending'   => '1',
                    'subscription.halted'    => '1',
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
        $this->assertEquals(1, $subscription['paid_count']);

        $this->failCharge();
        // subscription.pending event fired after
        // first failed charge attempts
        $this->mockAndTestWebhookDataCustom('subscription.pending', 'subscriptionWebhookDataForAuthFailurePending');
        // First failure
        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals('auth_failure', $subscription['error_status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testCaptureFailurePendingWebhookEventData()
    {
        $this->createWebhook(
            [
                'events' => [
                    'subscription.activated' => '1',
                    'subscription.pending'   => '1',
                    'subscription.halted'    => '1',
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
        $this->assertEquals(1, $subscription['paid_count']);

        $this->failOnCapture();
        // subscription.pending event fired after
        // first failed charge attempts
        $this->mockAndTestWebhookDataCustom(
            'subscription.pending', 'subscriptionWebhookDataForCaptureFailurePending');
        // First failure
        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals('capture_failure', $subscription['error_status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testSuccessAfterPendingActivateWebhookEventData()
    {
        $this->createWebhook(
            [
                'events' => [
                    'subscription.activated' => '1',
                    'subscription.pending'   => '1',
                    'subscription.halted'    => '1',
                ]
            ]);

        $this->doAuthTxnForSubscriptionWithAddOn();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);

        // subscription.activated event fired after first charge
        $this->mockAndTestWebhookDataCustom('subscription.activated', 'subscriptionWebhookDataForFirstActivated');
        $this->chargeSubscriptionsViaCron($subscription['charge_at'] + 10);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(1, $subscription['paid_count']);

        $this->failOnCapture();

        // First failure
        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at'] + 10);
        $this->assertEquals(1, $result['invoices_created']);
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals('capture_failure', $subscription['error_status']);
        $this->assertEquals(1, $subscription['auth_attempts']);

        $this->clearMock();

        $this->mockAndTestWebhookDataCustom('subscription.activated', 'subscriptionWebhookDataForSuccessAfterPending');

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);
        Carbon::setTestNow($chargeAt);

        // Second failure
        $res = $this->makeSubscriptionRetryCronRequest();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals(0, $subscription['auth_attempts']);

        // Reset time
        Carbon::setTestNow();
    }

    public function testChargeWebhookEventData()
    {
        $this->createWebhook(
            [
                'events' => [
                    'subscription.activated' => '1',
                    'subscription.charged'   => '1',
                ]
            ]);

        $this->doAuthTxnForNewSubscription();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);

        $chargeAt = Carbon::createFromTimestamp($subscription['charge_at'] + 1, Timezone::IST);

        Carbon::setTestNow($chargeAt);

        $this->mockAndTestWebhookDataCustom('subscription.charged', 'subscriptionWebhookDataForCharge');

        $result = $this->makeSubscriptionChargeCronRequest();

        $this->assertEquals(1, $result['total']);

        Carbon::setTestNow();
    }

    public function testSubscriptionChargeWithDueAddon()
    {
        $this->doAuthTxnForNewSubscription(false);
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        $item = $this->fixtures->create('item', ['id' => '2000000000item']);
        $this->fixtures->create(
            'addon',
            [
                'item_id'           => '2000000000item',
                'subscription_id'   => substr($subscription['id'], 4),
                'invoice_id'        => null,
            ]);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $addon = $this->getLastEntity('addon', true);

        $this->assertEquals(2, $subscription['paid_count']);
        $this->assertEquals($item['amount']+2000, $invoice['amount_paid']);
        $this->assertEquals($invoice['id'], $addon['invoice_id']);

        // To ensure that the next charge doesn't take the already used addon

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals(3, $subscription['paid_count']);
        $this->assertEquals(2000, $invoice['amount_paid']);
    }

    public function testSubscriptionChargeWithDeletedAddon()
    {
        $this->doAuthTxnForNewSubscription(false);
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('active', $subscription['status']);

        $item = $this->fixtures->create('item', ['id' => '2000000000item']);
        $this->fixtures->create(
            'addon',
            [
                'item_id'           => '2000000000item',
                'subscription_id'   => substr($subscription['id'], 4),
                'invoice_id'        => null,
                'deleted_at'        => 100000000,
            ]);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $addon = $this->getLastEntity('addon', true);

        $this->assertEquals(2, $subscription['paid_count']);

        // no addon should have been applied
        $this->assertEquals(2000, $invoice['amount_paid']);
        $this->assertNull($addon['invoice_id']);
    }

    public function testAddonAfterSubscriptionCreateImmediate()
    {
        $subscription = $this->createSubscription(false);

        $item = $this->fixtures->create('item', ['id' => '2000000000item']);
        $this->fixtures->create(
            'addon',
            [
                'item_id'           => '2000000000item',
                'subscription_id'   => substr($subscription['id'], 4),
                'invoice_id'        => null,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $invoice = $this->getLastEntity('invoice', true);
        $subscription = $this->getLastEntity('subscription', true);
        $addon = $this->getLastEntity('addon', true);

        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertEquals(2000, $invoice['amount_paid']);
        $this->assertNull($addon['invoice_id']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $addon = $this->getLastEntity('addon', true);

        $this->assertEquals(2, $subscription['paid_count']);

        $this->assertEquals(2, $subscription['paid_count']);
        $this->assertEquals($item['amount']+2000, $invoice['amount_paid']);
        $this->assertEquals($invoice['id'], $addon['invoice_id']);
    }

    public function testAddonAfterSubscriptionCreateFuture()
    {
        $subscription = $this->createSubscription(true);

        $item = $this->fixtures->create('item:addon_type', ['id' => '2000000000item']);
        $this->fixtures->create(
            'addon',
            [
                'item_id'           => '2000000000item',
                'subscription_id'   => substr($subscription['id'], 4),
                'invoice_id'        => null,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $invoice = $this->getLastEntity('invoice', true);
        $subscription = $this->getLastEntity('subscription', true);
        $addon = $this->getLastEntity('addon', true);

        $this->assertEquals('authenticated', $subscription['status']);
        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertNull($invoice);
        $this->assertNull($addon['invoice_id']);

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $addon = $this->getLastEntity('addon', true);

        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertEquals($item['amount']+2000, $invoice['amount_paid']);
        $this->assertEquals($invoice['id'], $addon['invoice_id']);
    }

    public function testToArrayPublicConversion()
    {
        $this->doAuthTxnForSubscriptionWithAddOn();

        $addOn = $this->getLastEntity('addon', true);

        $repo = (new Addon\Repository);

        $id = Addon\Entity::verifyIdAndStripSign($addOn['id']);

        $addOn = $repo->findOrFailPublicWithRelations($addOn['id'], ['item']);

        $entityArray = $addOn->toArray();

        $publicArray = $addOn->toArrayPublic();

        // we verify item publicArray has a sign and id is same
        // If it does not return a public id, verifyIdAndStripSign fails
        $itemStrippedId = Item\Entity::verifyIdAndStripSign($publicArray['item']['id']);

        $this->assertEquals($entityArray['item']['id'], $itemStrippedId);

        // deleted_at is part of visible attributes but not public attributes
        // in item entity, check for it
        $this->assertArrayHasKey('deleted_at', $entityArray['item']);

        $this->assertArrayNotHasKey('deleted_at', $publicArray['item']);
    }
}
