<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Exception\BadRequestException;
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

    public function testSubscriptionAuthTxnAutoCaptureUpfrontAmount()
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
                'upfront_amount' => 1000,
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['amount'] = 1000;

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);
        $token = $this->getLastEntity('token', true);
        $invoice = $this->getLastEntity('invoice', true);

        $this->assertEquals('authenticated', $subscription['status']);
        $this->assertEquals($token['id'], $subscription['token_id']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);

        $this->assertNull($invoice);
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

        $this->createFixturesForInvoice($subscription, $plan);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['amount'] = $plan->getAmount();

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

    public function testSubscriptionAuthTxnAutoCaptureFirstChargeAndUpfrontAmount()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->fixtures->create(
            'subscription',
            [
                'plan_id' => $plan->getId(),
                'upfront_amount' => 1000,
                'start_at' => null, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);
        $paymentRequest['amount'] = 1000 + $plan->getAmount();

        $this->createFixturesForInvoice($subscription, $plan, ($plan->getAmount() + $subscription->getUpfrontAmount()));

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        $subscription = $this->getLastEntity('subscription', true);
        $payment = $this->getLastEntity('payment', true);
        $token = $this->getLastEntity('token', true);

        $this->assertEquals('active', $subscription['status']);
        $this->assertEquals($token['id'], $subscription['token_id']);

        $this->assertEquals($subscription['id'], $payment['subscription_id']);
        $this->assertEquals('captured', $payment['status']);
        $this->assertEquals(null, $payment['amount_refunded']);
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
                'upfront_amount' => 1000,
                'start_at' => 1579631400, // 1-22-2020, 12:00:00 AM
                'total_count' => 3,
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('The amount does not match with the expected amount ' .
                'for the first transaction. It might have been tampered.', $ex->getMessage());

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
                'status' => 'authenticated',
            ]);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        try
        {
            $recurringPayment = $this->doAuthPayment($paymentRequest);
        }
        catch (BadRequestException $ex)
        {
            $this->assertEquals('Payment cannot be authorized since subscription is not authenticated', $ex->getMessage());

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

    protected function createFixturesForInvoice($subscription, $plan, $amount = null)
    {
        if ($amount === null)
        {
            $amount = $plan->getAmount();
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
            ]);

        $this->fixtures->create(
            'line_item',
            [
                'name' => $plan->getName(),
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
