<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionAuthTransactionTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['recurring']);

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->mockTokenex();
    }

    public function testSubscriptionAuthTxnNormal()
    {
        $plan = $this->fixtures->create(
            'plan',
            [
                'interval' => 3,
                'period' => 'monthly'
            ]);

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('created', $subscription['status']);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);
        $refund = $this->getLastEntity('refund', true);
        $token = $this->getLastEntity('token', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('authenticated', $subscription['status']);
        $this->assertEquals($token['id'], $subscription['token_id']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('refunded', $payment['status']);
        $this->assertEquals(500, $payment['amount_refunded']);

        $this->assertEquals($payment['id'], $refund['payment_id']);

        $this->assertNull($invoice);
    }

    // TODO: Test subscription charges with multiple quantity.
    // It should get the amount correctly.

    public function testSubscriptionAuthTxnAutoCaptureAddon()
    {
        $plan = $this->fixtures->create(
            'plan',
            [
                'interval' => 3,
                'period' => 'monthly',
                'amount' => 123456,
            ]);

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
                'charge_at' => 1579631400,
            ]);

        $this->createFixturesForAddon($subscription, $plan, 1000, 1000);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['amount'] = 1000;

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);
        $token = $this->getLastEntity('token', true);
        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);

        $this->assertEquals('authenticated', $subscription['status']);
        $this->assertEquals($token['id'], $subscription['token_id']);
        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertNotNull($subscription['charge_at']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
        $this->assertEquals(1000, $payment['amount']);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertTrue($payment['auto_captured']);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals(1000, $invoice['amount']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertNull($invoice['sub_status']);
        $this->assertNotNull($invoice['issued_at']);
        $this->assertContains('http://bitly', $invoice['short_url']);
        $this->assertEquals($payment['id'], $invoice['payment_id']);
        // Since the invoice is only for addons,
        // billing_start and billing_end should be null.
        $this->assertNull($invoice['billing_start']);
        $this->assertNull($invoice['billing_end']);
        // TODO: expire_by should be null for subscriptions.
        // Since this hasn't been implemented yet in invoices,
        // commenting it out. UNCOMMENT LATER.
        // $this->assertNull($invoice['expire_by']);

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals(1000, $order['amount']);

        $subscription = $this->getLastEntity('subscription', true);
        $plan = $this->getLastEntity('plan', true);
    }

    public function testSubscriptionAuthTxnAutoCaptureFirstCharge()
    {
        $plan = $this->fixtures->create(
            'plan',
            [
                'interval' => 3,
                'period' => 'monthly'
            ]);

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => null,
                'total_count' => 3,
            ]);

        $schedule = $this->fixtures->create(
            'schedule',
            [
                'interval' => $plan->getInterval(),
                'period' => $plan->getPeriod(),
                'anchor' => null,
                'name' => $plan->item->getName(),
            ]);

        $this->fixtures->create(
            'schedule_task',
            [
                'schedule_id' => $schedule->getId(),
                'entity_id' => $subscription->getId(),
                'entity_type' => 'subscription',
                'type' => 'subscription',
            ]);

        $this->createFixturesForInvoice($subscription, $plan);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['amount'] = $plan->item->getAmount();

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('created', $subscription['status']);
        $this->assertEquals(null, $subscription['start_at']);
        $this->assertEquals(null, $subscription['end_at']);
        $this->assertEquals(null, $subscription['charge_at']);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);
        $token = $this->getLastEntity('token', true);
        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals($payment['created_at'], $subscription['start_at']);
        $this->assertNotNull($subscription['end_at']);
        // Should be 90-92 days (3 months) ideally.
        $this->assertLessThanOrEqual($payment['created_at'] + 7948800, $subscription['charge_at']);
        $this->assertGreaterThan($payment['created_at'] + 7776000, $subscription['charge_at']);
        $this->assertEquals($payment['created_at'], $subscription['current_start']);
        $this->assertNotNull($subscription['current_end']);
        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertEquals($token['id'], $subscription['token_id']);
        $this->assertNull($subscription['ended_at']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);
        $this->assertEquals($token['id'], $payment['token_id']);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertEquals($payment['created_at'], $invoice['billing_start']);
        $this->assertEquals($subscription['current_end'], $invoice['billing_end']);

        $this->assertEquals('paid', $order['status']);
    }

    public function testSubscriptionAuthTxnAutoCaptureFirstChargeAndAddon()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => null,
                'total_count' => 3,
            ]);

        $this->createFixturesForAddon($subscription, $plan, $plan->item->getAmount() + 1000, 1000, true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['amount'] = 1000 + $plan->item->getAmount();

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);
        $token = $this->getLastEntity('token', true);
        $invoice = $this->getLastEntity('invoice', true);
        $order = $this->getLastEntity('order', true);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals($token['id'], $subscription['token_id']);
        $this->assertEquals(1, $subscription['paid_count']);
        $this->assertNotNull($subscription['charge_at']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);
        $this->assertEquals($invoice['id'], $payment['invoice_id']);
        $this->assertEquals($plan->item->getAmount() + 1000, $payment['amount']);
        $this->assertEquals($order['id'], $payment['order_id']);
        $this->assertTrue($payment['auto_captured']);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals($plan->item->getAmount() + 1000, $invoice['amount']);
        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals($order['id'], $invoice['order_id']);
        $this->assertNull($invoice['sub_status']);
        $this->assertNotNull($invoice['issued_at']);
        $this->assertContains('http://bitly', $invoice['short_url']);
        $this->assertEquals($payment['id'], $invoice['payment_id']);
        $this->assertNotNull($invoice['billing_start']);
        $this->assertNotNull($invoice['billing_end']);
        // TODO: expire_by should be null for subscriptions.
        // Since this hasn't been implemented yet in invoices,
        // commenting it out. UNCOMMENT LATER.
        // $this->assertNull($invoice['expire_by']);

        $this->assertEquals('paid', $order['status']);
        $this->assertEquals($plan->item->getAmount() + 1000, $order['amount']);
    }

    public function testSubscriptionAuthTxnWithRecurringFalse()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        unset($paymentRequest['recurring']);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('Recurring is not set for the subscription payment', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithWrongAmount()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                //'upfront_amount' => 1000,
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
            ]);

        $this->createFixturesForAddon($subscription, $plan, 1000, 1000);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('Payment amount provided does not match with the amount in order', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithPastStartAt()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => 1379631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

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
        $plan = $this->fixtures->create('plan');

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
                'status' => 'cancelled',
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (LogicException $ex)
        {
            $this->assertEquals('Subscription is neither in created state nor has ever been authenticated.', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithExpiredState()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
                'status' => 'expired',
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('The subscription has been expired.', $ex->getMessage());

            return;
        }

        $this->assertTrue(false);
    }

    public function testSubscriptionAuthTxnWithTokenAlreadyAssociated()
    {
        $plan = $this->fixtures->create('plan');

        $token = $this->fixtures->create('token', ['token' => '20000cardtoken']);

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
                'token_id' => $token->getId(),
            ]);

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
                'sms_status' => null,
                'email_status' => null,
                'subscription_id' => $subscription->getId(),
                'order_id' => $order->getId(),
                'amount' => $totalAmount,
                'issued_at' => time(),
            ]);

        // TODO: create an add on item with wrong type.
        // Test case should fail.

        $item = $this->fixtures->create(
            'item',
            [
                'name' => 'Sample Upfront Amount',
                'amount' => $addonAmount,
                'type' => 'addon',
            ]);

        $this->fixtures->create(
            'addon',
            [
                'subscription_id' => $subscription->getId(),
                'invoice_id' => $invoice->getId(),
                'item_id' => $item->getId(),
            ]);

        $this->fixtures->create(
            'line_item',
            [
                'name' => $item->getName(),
                'amount' => $item->getAmount(),
                'entity_id' => $invoice->getId(),
                'item_id' => $item->getId(),
            ]);

        if ($first === true)
        {
            $this->fixtures->create(
                'line_item',
                [
                    'id' => '200000lineitem',
                    'name' => $plan->item->getName(),
                    'amount' => $plan->item->getAmount(),
                    'entity_id' => $invoice->getId(),
                    'item_id' => null,
                ]);
        }
    }

    protected function createFixturesForInvoice($subscription, $plan, $amount = null)
     {
         if ($amount === null)
         {
             $amount = $plan->item->getAmount();
         }

        $order = $this->fixtures->create(
            'order',
            [
                'amount' => $amount,
                'payment_capture' => 1,
            ]);

        $invoice = $this->fixtures->create(
            'invoice',
            [
                'sms_status' => null,
                'email_status' => null,
                'subscription_id' => $subscription->getId(),
                'order_id' => $order->getId(),
                'amount' => $plan->item->getAmount(),
            ]);

        $this->fixtures->create(
            'line_item',
            [
                'name' => $plan->item->getName(),
                'amount' => $amount,
                'entity_id' => $invoice->getId(),
                'item_id' => null,
            ]);
    }

    protected function createSubscription($plan)
    {
        $this->ba->publicAuth();

        $paymentRequest = $this->getDefaultRecurringPaymentArray();
        $recurringPayment = $this->doAuthAndCapturePayment($paymentRequest);

        $tokenId = $recurringPayment['token_id'];

        $requestContent = $this->getCreateSubscriptionRequestContent('testCreateSubscription', $plan->getPublicId());

        $requestContent['request']['url'] = '/plans/' . $plan->getPublicId() . '/subscriptions/';
        // $requestContent['request']['content']['token_id'] = $tokenId;

        $this->ba->privateAuth();

        $this->startTest($requestContent);

        $subscription = $this->getLastEntity('subscription', true);

        return $subscription;
    }
}
