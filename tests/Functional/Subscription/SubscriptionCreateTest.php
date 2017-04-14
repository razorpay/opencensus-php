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

        $response = $this->startTest($requestContent);

        $invoice = $this->getLastEntity('invoice', true);

        $addOn = $this->getLastEntity('add_on', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertNull($addOn);

        $this->assertEquals(1, $lineItems['count']);
        $this->assertNull($lineItems['items'][0]['add_on_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);

        $this->assertEquals($response['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2000, $invoice['amount']);
    }

    public function testCreateSubscriptionWithNoStartAtAndWithAddOn()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $response = $this->startTest($requestContent);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $items = $this->getEntities('item', [], true);
        $addOn = $this->getLastEntity('add_on', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(1, $items['count']);
        $item = $items['items'][0];
        $this->assertEquals('add_on', $item['type']);
        $this->assertEquals('Sample Upfront Amount', $item['name']);
        $this->assertEquals(300, $item['amount']);

        $this->assertEquals($subscription['id'], $addOn['subscription_id']);
        $this->assertEquals($item['id'], $addOn['item_id']);
        $this->assertEquals($invoice['id'], $addOn['invoice_id']);

        $this->assertEquals(2, $lineItems['count']);

        $addOnLi = $lineItems['items'][0];
        $this->assertEquals(300, $addOnLi['amount']);
        $this->assertEquals('Sample Upfront Amount', $addOnLi['name']);
        $this->assertEquals($addOn['id'], $addOnLi['add_on_id']);
        $this->assertEquals($item['id'], $addOnLi['item_id']);
        // This is not visible to the public. Hence, unsigned. Also, polymorphic.
        $this->assertEquals($invoice['id'], 'inv_' . $addOnLi['entity_id']);

        $mainLi = $lineItems['items'][1];
        $this->assertNull($mainLi['item_id']);
        $this->assertNull($mainLi['add_on_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $mainLi['entity_id']);
        $this->assertEquals(2000, $mainLi['amount']);

        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2300, $invoice['amount']);
    }

    public function testCreateSubscriptionWithStartAtAndAddOn()
    {
        $requestContent = $this->getCreateSubscriptionRequestContent(__FUNCTION__);

        $this->startTest($requestContent);

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $items = $this->getEntities('item', [], true);
        $addOn = $this->getLastEntity('add_on', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(1, $items['count']);
        $item = $items['items'][0];
        $this->assertEquals('add_on', $item['type']);
        $this->assertEquals('Sample Upfront Amount', $item['name']);
        $this->assertEquals(300, $item['amount']);

        $this->assertEquals($subscription['id'], $addOn['subscription_id']);
        $this->assertEquals($item['id'], $addOn['item_id']);
        $this->assertEquals($invoice['id'], $addOn['invoice_id']);

        $this->assertEquals(1, $lineItems['count']);

        $addOnLi = $lineItems['items'][0];
        $this->assertEquals(300, $addOnLi['amount']);
        $this->assertEquals('Sample Upfront Amount', $addOnLi['name']);
        $this->assertEquals($addOn['id'], $addOnLi['add_on_id']);
        $this->assertEquals($item['id'], $addOnLi['item_id']);
        // This is not visible to the public. Hence, unsigned. Also, polymorphic.
        $this->assertEquals($invoice['id'], 'inv_' . $addOnLi['entity_id']);

        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(300, $invoice['amount']);
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

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertNull($invoice);
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
