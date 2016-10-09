<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;

class SubscriptionTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->editFeatures('recurring');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_terminal');

        $this->fixtures->create('terminal:shared_cybersource_hdfc_recurring_terminals');

        $this->mockTokenex();
    }

    public function testCreatePlan()
    {
        $this->startTest();
    }

    public function testCreateSubscription()
    {
        $plan = $this->fixtures->create('plan');

        $this->ba->publicAuth();

        $paymentRequest = $this->getDefaultRecurringPaymentArray();
        $recurringPayment = $this->doAuthAndCapturePayment($paymentRequest);

        $tokenId = $recurringPayment['token_id'];

        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__, $plan->getPublicId(), $tokenId);

        $requestContent['request']['url'] = '/plans/' . $plan->getPublicId() . '/subscriptions/';
        $requestContent['request']['content']['token_id'] = $tokenId;

        $this->ba->privateAuth();

        $this->startTest($requestContent);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals($subscription['plan_id'], $plan->getPublicId());
        $this->assertEquals($subscription['customer_id'], 'cust_100000customer');
        $this->assertEquals($subscription['token_id'], $tokenId);
        $this->assertEquals($subscription['start_at'], $subscription['charge_at']);

        $tokenEntity = $this->getLastEntity('token', true);
        $this->assertEquals(true, $tokenEntity['recurring']);
    }

    protected function getCreateSubscriptionRequestContent($function, $planId, $tokenId)
    {
        $requestContent = $this->testData[$function];

        $requestContent['request']['url'] = '/plans/' . $planId . '/subscriptions/';
        $requestContent['request']['content']['token_id'] = $tokenId;

        $startAt = time() + 100;
        $endAt = $startAt + 86000;

        $requestContent['request']['content']['start_at'] = $startAt;
        $requestContent['request']['content']['end_at'] = $endAt;

        return $requestContent;
    }
}