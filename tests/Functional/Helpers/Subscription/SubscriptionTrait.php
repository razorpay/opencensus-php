<?php

namespace RZP\Tests\Functional\Helpers\Subscription;

use Mockery;
use Closure;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;

trait SubscriptionTrait
{
    public function getSubscriptionAuthTransactionRequest(
        $subscription, $authAmount = null, $token = null)
    {
        $paymentRequest = $this->getDefaultRecurringPaymentArray();

        // For subscription, we get the customer ID from the subscription entity itself.
        unset($paymentRequest['customer_id']);

        $paymentRequest['subscription_id'] = $subscription['id'];

        $paymentRequest['amount'] = 500;

        if ($authAmount !== null)
        {
            $paymentRequest['amount'] = $authAmount;
        }

        if ($token !== null)
        {
            $paymentRequest['token'] = $token;
        }

        return $paymentRequest;
    }

    public function makeSubscriptionChargeCronRequest()
    {
        $request = [
            'url'     => '/subscriptions/charge/invoices',
            'action'  => 'post',
            'content' => [],
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function makeSubscriptionCancelDueRequest()
    {
        $request = [
            'url'       => '/subscriptions/cancel/due',
            'action'    => 'post',
            'content'   => [],
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function makeSubscriptionInvoiceChargeManualRequest($invoiceId)
    {
        $request = [
            'url'       => "/invoices/$invoiceId/charge",
            'action'    => 'post',
            'content'   => []
        ];

        $this->ba->proxyAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function makeSubscriptionRetryCronRequest()
    {
        $request = [
            'url'     => '/subscriptions/retry',
            'action'  => 'post',
            'content' => [],
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    public function makeSubscriptionExpireCronRequest()
    {
        $request = [
            'url'     => '/subscriptions/expire',
            'action'  => 'post',
            'content' => [],
        ];

        $this->ba->cronAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function createSubscription(
        $startAt = false,
        $planAttributes = [],
        $subscriptionAttributes = [],
        $addons = false,
        $emptyResponseContent = false,
        $createCustomer = true)
    {
        $this->fixtures->create('customer');

        $this->fixtures->plan->create($planAttributes);

        if ($startAt === false)
        {
            $testFuncName = 'createSubscriptionForAuthTxn';
        }
        else
        {
            $testFuncName = 'createSubscriptionForAuthTxnWithStartAt';
        }

        $requestContent = $this->testData[$testFuncName];

        //
        // This needs to be before the merge block because we might
        // send customer_id in subscriptionAttributes, but don't want
        // it to be sent or created via this function. Basically, pre-created
        // customer. Don't use the standard customer_id (1000000customer)
        //
        if ($createCustomer === false)
        {
            $requestContent['request']['content']['customer_id'] = null;
            unset($requestContent['response']['content']['customer_id']);
        }

        if (empty($subscriptionAttributes) === false)
        {
            $requestContent['request']['content'] = array_merge(
                $requestContent['request']['content'], $subscriptionAttributes);
        }

        if ($addons === true)
        {
            $requestContent['request']['content']['addons'] = [
                [
                    'item' => [
                        'amount'   => 300,
                        'currency' => 'INR',
                        'name'     => 'Sample Upfront Amount',
                        'type'     => 'addon',
                    ]
                ]
            ];
        }

        if ($emptyResponseContent === true)
        {
            $requestContent['response']['content'] = [];
        }

        $subscriptionResponse = $this->startTest($requestContent);

        return $subscriptionResponse;
    }

    protected function doAuthTxnForNewSubscription(bool $startAt = true)
    {
        $subscription = $this->createSubscription($startAt);

        $authAmount = null;

        if ($startAt === false)
        {
            $plan = $this->getLastEntity('plan', true);
            $authAmount = $plan['item']['amount'];
        }

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, $authAmount);

        $recurringPayment = $this->doAuthPayment($paymentRequest);

        return [
            'subscription_id'   => $subscription['id'],
            'payment_id'        => $recurringPayment['razorpay_payment_id'],
        ];
    }

    protected function doAuthTxnForSubscriptionWithAddOn()
    {
        // Subscription is created with start_at and with add_on
        $subscription = $this->createSubscription(true, [], [], true);
        $this->assertEquals('created', $subscription['status']);

        $addon = $this->getLastEntity('addon', true);
        $this->assertEquals($subscription['id'], $addon['subscription_id']);
        $item = $this->getLastEntity('item', true);
        $this->assertEquals($item['id'], $addon['item_id']);

        // Charge amount is addon amount
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, $item['amount']);

        $recurringPayment = $this->doAuthPayment($paymentRequest);
    }

    protected function doAuthTxnForSubscriptionImmediateWithAddOn()
    {
        // Subscription is created without start_at and with add_on
        $subscription = $this->createSubscription(false, [], [], true);
        $this->assertEquals('created', $subscription['status']);

        $addon = $this->getLastEntity('addon', true);
        $this->assertEquals($subscription['id'], $addon['subscription_id']);
        $item = $this->getLastEntity('item', true);
        $this->assertEquals($item['id'], $addon['item_id']);

        $plan = $this->getLastEntity('plan', true);

        $firstChargeAmount = $item['amount'] + $plan['item']['amount'];

        // Charge amount is addon amount plus plan amount
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, $firstChargeAmount);

        $recurringPayment = $this->doAuthPayment($paymentRequest);
    }

    protected function doAuthTxnForSubscriptionImmediateWithoutAddOn()
    {
        // Subscription is created without start_at and with add_on
        $subscription = $this->createSubscription(false, [], [], false);
        $this->assertEquals('created', $subscription['status']);

        $plan = $this->getLastEntity('plan', true);

        // Charge amount is addon amount plus plan amount
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription, $plan['item']['amount']);

        $recurringPayment = $this->doAuthPayment($paymentRequest);
    }

    protected function failCharge()
    {
        $this->mockServerContentFunction(function($input, $action)
        {
            throw new \SoapFault('HTTP', 'Random SoapFault Exception');
        });
    }

    protected function failOnCapture()
    {
        $this->mockServerContentFunction(function($input, $action)
        {
            if ($action === 'validate_capture')
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid Capture');
            }
        });
    }

    protected function clearMock()
    {
        $this->mockServerContentFunction(function(&$input)
        {
        });
    }

    protected function mockInfernoFire(Closure $closure, $times = 1)
    {
        $class = \RZP\Models\Merchant\Webhook\Inferno::class;

        $inferno = Mockery::mock($class, [])->makePartial();

        $inferno->shouldReceive('fire')
                ->times($times)
                ->with(
                    Mockery::type('RZP\Jobs\WebHook'),
                    Mockery::on($closure));

        $this->app->instance('webhook.inferno', $inferno);
    }

    protected function mockAndTestWebhookData(string $event, $times = 1)
    {
        $testData = $this->testData['subscriptionWebhookData'];

        $this->mockInfernoFire(function ($data) use ($testData, $event)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);

            $this->assertArrayHasKey('webhook_id', $data);
            $this->assertArrayHasKey('created_at', $data['event']);
            $this->assertArrayHasKey('event', $data['event']);

            $this->assertEquals($event, $data['event']['event']);

            $payload = $data['event']['payload'];

            $this->assertArrayHasKey('subscription', $payload);
            $this->assertArrayHasKey('entity', $payload['subscription']);

            $this->assertArrayHasKey('entity', $payload['subscription']['entity']);

            $subscription = $payload['subscription']['entity'];

            $this->assertEquals('subscription', $subscription['entity']);

            $this->assertArrayHasKey('id'              , $subscription);
            $this->assertArrayHasKey('entity'          , $subscription);
            $this->assertArrayHasKey('plan_id'         , $subscription);
            $this->assertArrayHasKey('customer_id'     , $subscription);
            $this->assertArrayHasKey('status'          , $subscription);
            $this->assertArrayHasKey('current_start'   , $subscription);
            $this->assertArrayHasKey('current_end'     , $subscription);
            $this->assertArrayHasKey('ended_at'        , $subscription);
            $this->assertArrayHasKey('quantity'        , $subscription);
            $this->assertArrayHasKey('notes'           , $subscription);
            $this->assertArrayHasKey('charge_at'       , $subscription);
            $this->assertArrayHasKey('start_at'        , $subscription);
            $this->assertArrayHasKey('end_at'          , $subscription);
            $this->assertArrayHasKey('auth_attempts'   , $subscription);
            $this->assertArrayHasKey('total_count'     , $subscription);
            $this->assertArrayHasKey('paid_count'      , $subscription);
            $this->assertArrayHasKey('customer_notify' , $subscription);

            return true;
        },
        $times);
    }

    protected function mockAndTestWebhookDataCustom(string $event, string $testDataKey)
    {
        $testData = $this->testData[$testDataKey];

        $this->mockInfernoFire(function ($data) use ($testData, $event)
        {
            $data['event'] = json_decode($data['event'], true);

            $this->assertArraySelectiveEquals($testData, $data);

            $subscriptionPayload = $data['event']['payload']['subscription']['entity'];

            $this->assertArrayNotHasKey('token_id', $subscriptionPayload);

            $this->assertNotNull('webhook_id', $data);

            return true;
        });
    }

    protected function assertInvoiceCount($count, $subscriptionId)
    {
        $invoices = $this->getEntities('invoice', ['subscription_id' => $subscriptionId], true);

        $this->assertEquals($count, $invoices['count']);
    }

    protected function chargeSubscriptionsViaCron($timestamp = null)
    {
        if ($timestamp !== null)
        {
            $chargeAt = Carbon::createFromTimestamp($timestamp + 1, Timezone::IST);

            Carbon::setTestNow($chargeAt);
        }

        return $this->makeSubscriptionChargeCronRequest();
    }

    protected function retrySubscriptionsViaCron($timestamp = null)
    {
        if ($timestamp !== null)
        {
            $chargeAt = Carbon::createFromTimestamp($timestamp, Timezone::IST)
                                ->addDay(1)
                                ->addMinute(1);

            Carbon::setTestNow($chargeAt);
        }

        return $this->makeSubscriptionRetryCronRequest();
    }

    protected function chargeSubscriptionManuallyTestMode($subscriptionId, $success)
    {
        $request = [
            'url'     => "/subscriptions/$subscriptionId/charge",
            'action'  => 'post',
            'content' => [
                'success' => $success ? 1 : 0,
            ],
        ];

        $this->ba->proxyAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function chargeSubscriptionInvoiceManually($invoice)
    {
        return $this->makeSubscriptionInvoiceChargeManualRequest($invoice['id']);
    }

    protected function createSubscriptionPreRequisiteEntities(array $planAttributes = [])
    {
        $response = $this->fixtures->create('customer');

        $response = $this->fixtures->plan->create($planAttributes);
    }

    protected function failSubscriptionFirstCharge()
    {
        $this->doAuthTxnForNewSubscription();
        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('authenticated', $subscription['status']);
        $oldSubcription = $subscription;

        $this->failCharge();

        $result = $this->chargeSubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['invoices_created']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertEquals(1, $subscription['auth_attempts']);
        // Billing period was updated even though charge failed
        $this->assertEquals($oldSubcription['charge_at'], $subscription['current_start']);
        $this->assertEquals($oldSubcription['charge_at']+(24*60*60), $subscription['charge_at']);
        $oldSubcription = $subscription;

        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertNull($invoice['subscription_status']);
        $this->assertEquals($subscription['current_start'], $invoice['billing_start']);
        $this->assertEquals($subscription['current_end'], $invoice['billing_end']);
        $this->assertInvoiceCount(1, $subscription['id']);

        $this->clearMock();

        return $subscription;
    }

    protected function failSubscriptionTillHalted()
    {
        $subscription = $this->failSubscriptionFirstCharge();
        $oldSubcription = $subscription;

        $invoice = $this->getLastEntity('invoice', true);
        $oldInvoice = $invoice;

        $this->failCharge();

        $result = $this->retrySubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['queued']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertEquals(2, $subscription['auth_attempts']);
        $this->assertEquals($oldSubcription['current_start'], $subscription['current_start']);
        $this->assertEquals($oldSubcription['charge_at']+(24*60*60), $subscription['charge_at']);
        $oldSubcription = $subscription;

        // No new invoices created
        $this->assertInvoiceCount(1, $subscription['id']);
        // Existing invoice not updated
        $this->assertArraySelectiveEquals($oldInvoice, $invoice);

        $result = $this->retrySubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['queued']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('pending', $subscription['status']);
        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertEquals(3, $subscription['auth_attempts']);
        $this->assertEquals($oldSubcription['current_start'], $subscription['current_start']);
        $this->assertEquals($oldSubcription['charge_at']+(24*60*60), $subscription['charge_at']);
        $oldSubcription = $subscription;

        $this->assertInvoiceCount(1, $subscription['id']);

        $result = $this->retrySubscriptionsViaCron($subscription['charge_at']);
        $this->assertEquals(1, $result['queued']);

        $subscription = $this->getLastEntity('subscription', true);
        $this->assertEquals('halted', $subscription['status']);
        $this->assertEquals(0, $subscription['paid_count']);
        $this->assertEquals(4, $subscription['auth_attempts']);
        $this->assertEquals($oldSubcription['current_start'], $subscription['current_start']);
        // Charge at has moved to next plan period
        $this->assertEquals($oldSubcription['current_end'], $subscription['charge_at']);
        $oldSubcription = $subscription;

        $this->assertInvoiceCount(1, $subscription['id']);
        $invoice = $this->getLastEntity('invoice', true);
        $this->assertEquals('halted', $invoice['subscription_status']);

        $result = $this->retrySubscriptionsViaCron($subscription['charge_at']);
        // Nothing happens
        $this->assertEquals(0, $result['queued']);

        $this->clearMock();

        return $subscription;
    }

    protected function makeCancelRequest(string $subscriptionId, $futureCancellation = null)
    {
        $testData = $this->testData['testSubscriptionCancel'];

        if ($futureCancellation !== null)
        {
            $testData = $this->testData['testSubscriptionCancelFuture'];
            $testData['request']['content']['cancel_at_cycle_end'] = $futureCancellation;
        }

        $testData['request']['url'] = '/subscriptions/' . $subscriptionId . '/cancel';

        $this->ba->privateAuth();

        return $this->startTest($testData);
    }
}
