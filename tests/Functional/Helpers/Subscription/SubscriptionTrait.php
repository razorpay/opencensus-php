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

    protected function doAuthTxnForNewSubscription()
    {
        $subscription = $this->createSubscription(true);

        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

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

    protected function chargeSubscriptionsViaCron($timestamp = null)
    {
        if ($timestamp !== null)
        {
            $chargeAt = Carbon::createFromTimestamp($timestamp + 1, Timezone::IST);

            Carbon::setTestNow($chargeAt);
        }

        return $this->makeSubscriptionChargeCronRequest();
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
}
