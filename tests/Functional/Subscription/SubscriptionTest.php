<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionTest extends TestCase
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

    // TODO: Add test cases for total_count.
    // TODO: Add test cases for calculation of end_at.

    public function testCreatePlan()
    {
        $this->startTest();

        $plan = $this->getLastEntity('plan', true);
        $schedule = $this->getLastEntity('schedule', true);

        $this->assertEquals($schedule['id'], $plan['schedule_id']);
    }

    public function testCreateSubscription()
    {
        $plan = $this->fixtures->create('plan');

        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__, $plan->getPublicId());

        // $requestContent['request']['content']['start_at'] = 1485109800;
        // $requestContent['request']['content']['end_at'] = 1512153000;

        $this->startTest($requestContent);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals($subscription['plan_id'], $plan->getPublicId());
    }

    public function testCreateSubscription1()
    {
        $this->markTestSkipped();

        $plan = $this->fixtures->create('plan');

        $this->ba->publicAuth();

        $paymentRequest = $this->getDefaultRecurringPaymentArray();
        $recurringPayment = $this->doAuthAndCapturePayment($paymentRequest);

        $tokenId = $recurringPayment['token_id'];

        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__, $plan->getPublicId(), $tokenId);

        $requestContent['request']['url'] = '/plans/' . $plan->getPublicId() . '/subscriptions/';

        $this->ba->privateAuth();

        $this->startTest($requestContent);

        $subscription = $this->getLastEntity('subscription', true);

        $this->assertEquals($subscription['plan_id'], $plan->getPublicId());
        $this->assertEquals($subscription['customer_id'], 'cust_100000customer');
        // $this->assertEquals($subscription['token_id'], $tokenId);
        $this->assertEquals($subscription['start_at'], $subscription['charge_at']);

        // $tokenEntity = $this->getLastEntity('token', true);
        // $this->assertEquals(true, $tokenEntity['recurring']);
    }

    public function testSubscriptionAuthTransaction()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->createSubscription($plan);

        $paymentRequest = $this->getDefaultRecurringPaymentArray();
        $paymentRequest['subscription_id'] = $subscription['id'];
        $paymentRequest['amount'] = 500;

        $recurringPayment = $this->doAuthPayment($paymentRequest);
    }

    public function testSubscriptionCharge()
    {
        $plan = $this->fixtures->create('plan');

        $subscription = $this->createSubscription($plan);

        $futureNow = Carbon::createFromTimestamp(($subscription['charge_at'] + 100), 'Asia/Kolkata');

        Carbon::setTestNow($futureNow);

        $this->ba->appAuth();

        $this->startTest();

        //$payment = $this->getLastEntity('payment', true);
    }

    protected function createSubscription($plan)
    {
        $this->ba->publicAuth();

        $paymentRequest = $this->getDefaultRecurringPaymentArray();
        $recurringPayment = $this->doAuthAndCapturePayment($paymentRequest);

        $tokenId = $recurringPayment['token_id'];

        $requestContent = $this->getCreateSubscriptionRequestContent(
            'testCreateSubscription', $plan->getPublicId(), $tokenId);

        $requestContent['request']['url'] = '/plans/' . $plan->getPublicId() . '/subscriptions/';
        // $requestContent['request']['content']['token_id'] = $tokenId;

        $this->ba->privateAuth();

        $this->startTest($requestContent);

        $subscription = $this->getLastEntity('subscription', true);

        return $subscription;
    }

    protected function getCreateSubscriptionRequestContent($function, $planId, $tokenId = null)
    {
        $requestContent = $this->testData[$function];

        $requestContent['request']['url'] = '/plans/' . $planId . '/subscriptions/';
        // $requestContent['request']['content']['token_id'] = $tokenId;

        $startAt = time() + 100;
        $endAt = $startAt + 86000;

        $requestContent['request']['content']['start_at'] = $startAt;
        $requestContent['request']['content']['end_at'] = $endAt;

        return $requestContent;
    }
}
