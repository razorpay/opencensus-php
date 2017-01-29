<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionCreateTest extends TestCase
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

    // TODO: Add test cases for total_count and end_at generation logic.

    public function testCreatePlan()
    {
        $this->startTest();

        $plan = $this->getLastEntity('plan', true);
        $schedule = $this->getLastEntity('schedule', true);

        $this->assertEquals($schedule['id'], $plan['schedule_id']);
    }

    public function testCreatePlanWithBadMonthlyIntervalPeriod()
    {
        $this->startTest();
    }

    public function testCreatePlanWithBadYearlyIntervalPeriod()
    {
        $this->startTest();
    }

    public function testCreateSubscriptionWithNoStartAt()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
    }

    public function testCreateSubscriptionWithOneYearLateStartAt()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
    }

    public function testCreateSubscriptionWithPastTime()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
    }

    public function testCreateSubscriptionWithTotalCount()
    {
        $plan = $this->fixtures->create('plan');

        $planId = $plan->getPublicId();

        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__, $planId);

        $response = $this->startTest($requestContent);

        $this->assertEquals($planId, $response['plan_id']);
    }

    public function testCreateSubscriptionWithEndAt()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
    }

    public function testCreateSubscriptionWithBothTotalCountAndEndAt()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
    }

    public function testCreateSubscriptionWithEndAtLesserThanStartAt()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
    }

    public function testCreateSubscriptionWithVeryFarEndAt()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
    }

    public function testCreateSubscriptionWithoutTotalCountAndEndAt()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);
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

    protected function getCreateSubscriptionRequestContent($function, $planId = null)
    {
        $requestContent = $this->testData[$function];

        if ($planId === null)
        {
            $plan = $this->fixtures->create('plan');

            $planId = $plan->getPublicId();
        }

        $requestContent['request']['url'] = '/plans/' . $planId . '/subscriptions/';

        return $requestContent;
    }
}
