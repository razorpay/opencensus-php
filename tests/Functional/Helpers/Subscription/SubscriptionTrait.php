<?php

namespace RZP\Tests\Functional\Helpers\Subscription;

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

    protected function createSubscription(
        $startAt = false,
        $planAttributes = [],
        $subscriptionAttributes = [],
        $addons = false,
        $emptyResponseContent = false)
    {
        $this->fixtures->create('customer');

        $this->fixtures->create('plan', $planAttributes);

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
}
