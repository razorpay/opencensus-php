<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Plan\Subscription;

class SubscriptionAuthTransactionTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->mockTokenex();
    }

    public function testSubscriptionAuthTxnNormalWithStartAt()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $response = $this->doAuthPayment($paymentRequest);

        $actualSignature = $response['razorpay_signature'];

        $signatureData = [
            'subscription_id' => $subscription['id'],
            'razorpay_payment_id' => $response['razorpay_payment_id'],
        ];

        ksort($signatureData);
        $exceptedSignature = $this->getSignature($signatureData, 'TheKeySecretForTests');

        $this->assertEquals($exceptedSignature, $actualSignature);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);
        $refund = $this->getLastEntity('refund', true);
        $token = $this->getLastEntity('token', true);
        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);

        $this->assertNull($invoice);
        $this->assertNull($order);

        $this->assertEquals('authenticated', $subscription['status']);
        $this->assertEquals($token['id'], 'token_' . $subscription['token_id']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(500, $payment['amount_refunded']);
        $this->assertNull($payment['order_id']);

        $this->assertEquals($payment['id'], $refund['payment_id']);
    }

    public function testSubscriptionAuthTxnNormalWithoutStartAt()
    {
        $subscription = $this->createSubscription(false);

        $oldScheduleTask = $this->getLastEntity('schedule_task', true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2000);

        $this->doAuthPayment($paymentRequest);

        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);
        $subscription = $this->getLastEntity('subscription', true);
        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);
        $refund = $this->getLastEntity('refund', true);
        $newScheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertEquals($subscription['current_start'], $invoice['billing_start']);
        $this->assertEquals($subscription['current_end'], $invoice['billing_end']);

        $this->assertEquals('paid', $order['status']);

        $this->assertNotNull($token);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals($token['id'], 'token_' . $subscription['token_id']);
        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertEquals($payment['created_at'], $subscription['start_at']);
        $this->assertNotNull($subscription['end_at']);
        $expectedStartAt = Carbon::createFromTimestamp($subscription['start_at'], Timezone::IST)
                                 ->addMonthsNoOverflow(2)
                                 ->startOfDay()
                                 ->timestamp;
        $this->assertEquals($expectedStartAt, $subscription['charge_at']);
        $this->assertEquals($payment['created_at'], $subscription['current_start']);
        $this->assertNotNull($subscription['current_end']);
        $this->assertNull($subscription['ended_at']);
        $this->assertEmpty($subscription['addons']);
        $this->assertNotNull($subscription['authenticated_at']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);
        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertEquals(2000, $payment['amount']);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertTrue($payment['recurring']);
        $this->assertNull($payment['global_customer_id']);

        $this->assertNull($refund);

        $this->assertEquals($oldScheduleTask['next_run_at'], $newScheduleTask['last_run_at']);
        $this->assertEquals($subscription['charge_at'], $newScheduleTask['next_run_at']);
    }

    public function testSubscriptionAuthTxnAddonWithStartAt()
    {
        $subscription = $this->createSubscription(true, [], [], true);

        $oldScheduleTask = $this->getLastEntity('schedule_task', true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 300);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);
        $subscription = $this->getLastEntity('subscription', true);
        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);
        $refund = $this->getLastEntity('refund', true);
        $newScheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        // Since the invoice is only for addons,
        // billing_start and billing_end should be null.
        $this->assertNull($invoice['billing_start']);
        $this->assertNull($invoice['billing_end']);
        $this->assertNull($invoice['subscription_status']);
        $this->assertContains('http://dwarf.razorpay.in', $invoice['short_url']);
        $this->assertEquals($payment['id'], $invoice['payment_id']);
        // TODO: expire_by should be null for subscriptions.
        // Since this hasn't been implemented yet in invoices,
        // commenting it out. UNCOMMENT LATER.
        // $this->assertNull($invoice['expire_by']);

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(300, $order['amount']);

        $this->assertEquals('authenticated', $subscription['status']);
        $this->assertEquals($token['id'], 'token_' . $subscription['token_id']);
        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertNotNull($subscription['charge_at']);
        $this->assertEquals($subscription['start_at'], $subscription['charge_at']);
        $this->assertNull($subscription['current_start']);
        $this->assertNull($subscription['current_end']);
        $this->assertNull($subscription['ended_at']);
        $this->assertNotEmpty($subscription['addons']);
        $this->assertNotNull($subscription['authenticated_at']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);
        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertEquals(300, $payment['amount']);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
        $this->assertTrue($payment['auto_captured']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertTrue($payment['recurring']);
        $this->assertNull($payment['global_customer_id']);

        $this->assertNull($refund);

        $this->assertNull($newScheduleTask['last_run_at']);
        $this->assertEquals($subscription['charge_at'], $newScheduleTask['next_run_at']);
    }

    // TODO: Test subscription charges with multiple quantity.
    // It should get the amount correctly.

    public function testSubscriptionAuthTxnAutoCaptureAddonWithoutStartAt()
    {
        $subscription = $this->createSubscription(false, [], [], true);

        $oldScheduleTask = $this->getLastEntity('schedule_task', true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 2300);

        $this->doAuthPayment($paymentRequest);

        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);
        $subscription = $this->getLastEntity('subscription', true);
        $token = $this->getLastEntity('token', true);
        $payment = $this->getLastEntity('payment', true);
        $refund = $this->getLastEntity('refund', true);
        $newScheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertEquals($payment['created_at'], $invoice['billing_start']);
        $this->assertEquals($subscription['current_end'], $invoice['billing_end']);
        $this->assertEquals(2300, $invoice['amount']);
        $this->assertNull($invoice['subscription_status']);
        $this->assertNotNull($invoice['issued_at']);
        $this->assertEquals($payment['id'], $invoice['payment_id']);
        $this->assertNotNull($invoice['billing_start']);
        $this->assertNotNull($invoice['billing_end']);
        // TODO: expire_by should be null for subscriptions.
        // Since this hasn't been implemented yet in invoices,
        // commenting it out. UNCOMMENT LATER.
        // $this->assertNull($invoice['expire_by']);

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(2300, $order['amount']);

        $this->assertNotNull($token);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals($token['id'], 'token_' . $subscription['token_id']);
        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertEquals($payment['created_at'], $subscription['start_at']);
        $this->assertNotNull($subscription['end_at']);
        $expectedStartAt = Carbon::createFromTimestamp($subscription['start_at'], Timezone::IST)
                                ->addMonthsNoOverflow(2)
                                ->startOfDay()
                                ->timestamp;
        $this->assertEquals($expectedStartAt, $subscription['charge_at']);
        $this->assertEquals($payment['created_at'], $subscription['current_start']);
        $this->assertNotNull($subscription['current_end']);
        $this->assertNull($subscription['ended_at']);
        $this->assertNotEmpty($subscription['addons']);
        $this->assertNotNull($subscription['authenticated_at']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);
        $this->assertEquals($token['id'], $payment['token_id']);
        $this->assertEquals(2300, $payment['amount']);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
        $this->assertEquals('cust_100000customer', $payment['customer_id']);
        $this->assertTrue($payment['recurring']);
        $this->assertNull($payment['global_customer_id']);
        $this->assertTrue($payment['auto_captured']);

        $this->assertNull($refund);

        $this->assertEquals($oldScheduleTask['next_run_at'], $newScheduleTask['last_run_at']);
        $this->assertEquals($subscription['charge_at'], $newScheduleTask['next_run_at']);
    }

    public function testSubscriptionAuthTxnWithRecurringFalse()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        unset($paymentRequest['recurring']);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('Subscription payment cannot be made without saving the card', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithWrongAmount()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, 123);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('The amount does not match with the expected amount for the '.
                'transaction. It might have been tampered.', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithPastStartAt()
    {
        Carbon::setTestNow(Carbon::createFromTimestamp(1379631300, Timezone::IST));

        $subscription = $this->createSubscription(true, [], ['start_at' => 1379631400], false, true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        Carbon::setTestNow();

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('Subscription\'s start time is past the current time. ' .
                'Cannot do an auth transaction now.', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithNotCreatedState()
    {
        $subscription = $this->createSubscription(true);

        $subscriptionEntity = (new Subscription\Repository)->findByPublicId($subscription['id']);

        $subscriptionEntity->setStatus('cancelled');

        (new Subscription\Repository)->saveOrFail($subscriptionEntity);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals(
                'The subscription is in a terminal state',
                $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithExpiredState()
    {
        $subscription = $this->createSubscription(true);

        $subscriptionEntity = (new Subscription\Repository)->findByPublicId($subscription['id']);

        $subscriptionEntity->setStatus('expired');

        (new Subscription\Repository)->saveOrFail($subscriptionEntity);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('The subscription is in a terminal state', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithTokenAlreadyAssociated()
    {
        $token = $this->fixtures->create('token', ['token' => '20000cardtoken']);

        $subscription = $this->createSubscription(true);

        $subscriptionEntity = (new Subscription\Repository)->findByPublicId($subscription['id']);

        $subscriptionEntity->setTokenId($token->getId());

        (new Subscription\Repository)->saveOrFail($subscriptionEntity);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('The subscription already has a token associated with it', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    protected function createFixturesForAddon($subscription, $plan, $totalAmount, $addonAmount, $first = false)
    {
        $order = $this->fixtures->create(
            'order',
            [
                'amount' => $totalAmount,
                'payment_capture' => 1,
            ]);

        $invoice = $this->fixtures->create(
            'invoice',
            [
                'sms_status'      => null,
                'email_status'    => null,
                'subscription_id' => $subscription->getId(),
                'order_id'        => $order->getId(),
                'amount'          => $totalAmount,
                'issued_at'       => time(),
            ]);

        // TODO: create an add on item with wrong type.
        // Test case should fail.

        $item = $this->fixtures->create(
            'item:addon',
            [
                'name'   => 'Sample Upfront Amount',
                'amount' => $addonAmount,
            ]);

        $this->fixtures->create(
            'addon',
            [
                'subscription_id' => $subscription->getId(),
                'invoice_id'      => $invoice->getId(),
                'item_id'         => $item->getId(),
            ]);

        $this->fixtures->create(
            'line_item',
            [
                'name'      => $item->getName(),
                'amount'    => $item->getAmount(),
                'entity_id' => $invoice->getId(),
                'item_id'   => $item->getId(),
            ]);

        if ($first === true)
        {
            $this->fixtures->create(
                'line_item',
                [
                    'id'        => '200000lineitem',
                    'name'      => $plan->item->getName(),
                    'amount'    => $plan->item->getAmount(),
                    'entity_id' => $invoice->getId(),
                    'item_id'   => null,
                ]);
        }
    }
}
