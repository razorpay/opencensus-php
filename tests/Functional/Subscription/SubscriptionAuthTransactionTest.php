<?php

namespace RZP\Tests\Functional\Subscription;

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

    public function testSubscriptionAuthTransaction()
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

        $recurringPayment = $this->doAuthPayment($paymentRequest);
    }

    protected function getSubscriptionAuthTransactionRequest($subscription)
    {
        $paymentRequest = $this->getDefaultRecurringPaymentArray();

        $paymentRequest['subscription_id'] = $subscription['public_id'];
        $paymentRequest['amount'] = 500;

        return $paymentRequest;
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
