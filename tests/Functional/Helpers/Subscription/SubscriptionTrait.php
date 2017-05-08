<?php

namespace RZP\Tests\Functional\Helpers\Subscription;

use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

trait SubscriptionTrait
{
    use PaymentTrait;

    public function getSubscriptionAuthTransactionRequest($subscription, $authAmount = null)
    {
        $paymentRequest = $this->getDefaultRecurringPaymentArray();

        // For subscription, we get the customer ID from the subscription entity itself.
        unset($paymentRequest['customer_id']);

        $paymentRequest['subscription_id'] = $subscription['id'];

        if ($authAmount === null)
        {
            $paymentRequest['amount'] = 500;
        }
        else
        {
            $paymentRequest['amount'] = $authAmount;
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

    public function makeSubscriptionRetryCronRequest()
    {
        $request = [
            'url'     => '/subscriptions/retry/auth',
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
        $emptyResponseContent = false)
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
                        'name'     => 'Sample Upfront Amount'
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
        $this->mockServerContentFunction(function(&$content)
        {
            $content['ApprovalCode']      = 'N:-10503:Poor excude for an error message';
            $content['TransactionResult'] = 'FAILED';
        });
    }

    protected function passCharge()
    {
        $this->mockServerContentFunction(function(&$content)
            {
                // Do nothing
            });
    }

    protected function chargeSubscriptionsViaCron(string $timestamp = null)
    {
        if ($timestamp !== null)
        {
            $chargeAt = Carbon::createFromTimestamp($timestamp+1);

            Carbon::setTestNow($chargeAt, 'Asia/Kolkata');
        }

        return $this->makeSubscriptionChargeCronRequest();
    }
}
