<?php

namespace RZP\Tests\Functional\Helpers\Subscription;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Timezone;
use RZP\Exception\RuntimeException;

trait SubscriptionTrait
{
    public function getSubscriptionAuthTransactionRequest(
        $subscription, $authAmount = null, $token = null)
    {
        $paymentRequest = $this->getDefaultRecurringPaymentArray();

        // This is not required since we add it implicitly.
        unset($paymentRequest['recurring']);

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

    public function getSubscriptionCardChangeRequest($subscription)
    {
        $paymentRequest = $this->getSubscriptionAuthTransactionRequest($subscription);

        $paymentRequest['subscription_card_change'] = true;

        // If subscription is pending, preferences route will ask checkout to make a payment
        // corresponding to the amount of the latest invoice. Just mocked here via plan.
        //
        // TODO This function won't work if you're trying to change card on a subscription
        // that went to pending state on the very first charge, which was immediate+addon.
        // Because here we're only using plan amount and not other addons.
        if ($subscription['status'] === 'pending')
        {
            $plan = $this->getLastEntity('plan', true);

            $paymentRequest['amount'] = $plan['item']['amount'];
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

    public function makeSubscriptionInvoiceChargeManualRequestOld($invoiceId)
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

    public function makeSubscriptionInvoiceChargeManualRequest($subscriptionId, $invoiceId)
    {
        $request = [
            'url'       => "/subscriptions/$subscriptionId/invoices/$invoiceId/charge",
            'action'    => 'post',
            'content'   => []
        ];

        $this->ba->adminProxyAuth();

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
        $createCustomer = true,
        $customerNotify = true)
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

        if ($customerNotify === false)
        {
            $requestContent['request']['content']['customer_notify'] = 0;
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

    protected function doAuthTxnForNewSubscription(bool $startAt = true, $planAttributes = [])
    {
        $subscription = $this->createSubscription($startAt, $planAttributes);

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
            throw new RuntimeException('Error occured while sending request to Gateway');
        });
    }

    protected function failOnCapture()
    {
        $this->mockServerContentFunction(function($input, $action)
        {
            if ($action === 'capture')
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

    protected function chargeSubscriptionInvoiceManually($invoice, $subscription = null)
    {
        if ($subscription == null)
        {
            return $this->makeSubscriptionInvoiceChargeManualRequestOld($invoice['id']);
        }
        else
        {
            return $this->makeSubscriptionInvoiceChargeManualRequest($subscription['id'], $invoice['id']);
        }
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

    protected function cancelSubscription($subscription, $future = null)
    {
        $request = [
            'url'     => '/subscriptions/'.$subscription['id'].'/cancel',
            'action'  => 'post',
            'content' => [],
        ];

        if ($future !== null)
        {
            $request['content']['cancel_at_cycle_end'] = $future;
        }

        $this->ba->privateAuth();

        $response = $this->sendRequest($request);

        return json_decode($response->getContent(), true);
    }

    protected function mockSession($appToken = 'capp_1000000custapp')
    {
        $data = [ 'test_app_token' => $appToken ];

        $this->session($data);
    }

    protected function callViewUrlAndMakeAssertions(
        string $id,
        int $code = 200,
        string $errorMessage = null,
        string $additionalCheck = null)
    {
        $this->ba->publicAuth();

        $response = $this->call('GET', "/v1/t/subscriptions/$id", ['key_id' => $this->ba->getKey()]);

        $response->assertStatus($code);

        if (empty($errorMessage) === false)
        {
            $this->assertContains($errorMessage, $response->getContent());
        }
        else
        {
            $this->assertNotContains('<h2>Error</h2>', $response->getContent());

            $this->assertContains($id, $response->getContent());
        }

        if ($additionalCheck !== null)
        {
            $this->assertContains($additionalCheck, $response->getContent());
        }
    }
}
