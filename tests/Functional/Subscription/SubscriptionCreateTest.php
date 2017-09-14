<?php

namespace RZP\Tests\Functional\Subscription;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;
use Carbon\Carbon;

class SubscriptionCreateTest extends TestCase
{
    use PaymentTrait;
    use SubscriptionTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/SubscriptionTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->addFeatures(['subscriptions']);
    }

    // TODO: Add test cases for total_count and end_at generation logic.

    // ------------------ PLAN TESTS ------------------

    public function testCreatePlanWithItemId()
    {
        $this->fixtures->item->createPlanType();

        $this->startTest();
    }

    public function testCreatePlanWithItemIdButWrongItemType()
    {
        $this->fixtures->create('item');

        $this->startTest();
    }

    public function testCreatePlanWithoutAnyItem()
    {
        $this->startTest();
    }

    public function testCreatePlanWithoutItemId()
    {
        $this->startTest();
    }

    public function testCreatePlanWithBadMonthlyIntervalPeriod()
    {
        $this->fixtures->item->createPlanType();

        $this->startTest();
    }

    public function testCreatePlanWithBadYearlyIntervalPeriod()
    {
        $this->fixtures->item->createPlanType();

        $this->startTest();
    }

    public function testFetchPlan()
    {
        $this->fixtures->plan->create();

        $this->startTest();
    }

    public function testFetchMultiplePlan()
    {
        $this->fixtures->plan->create();

        $this->fixtures->plan->create(
            [
                'id' => '1000000001plan'
            ],
            [
                'id'   => '1000000001item',
                'name' => 'Plan #2',
            ]);

        $this->startTest();
    }

    // ------------------ END PLAN TESTS ------------------

    public function testCreateSubscriptionWithoutCustomerId()
    {
        $this->fixtures->plan->create();

        $this->startTest();
    }

    public function testCreateSubscriptionWithoutPlanId()
    {
        $this->startTest();
    }

    public function testCreateSubscriptionWithNoStartAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);

        $schedule = $this->getLastEntity('schedule', true);
        $addon = $this->getLastEntity('addon', true);
        $lineItems = $this->getEntities('line_item', [], true);
        $scheduleTask = $this->getLastEntity('schedule_task', true);
        $subscription = $this->getLastEntity('subscription', true);
        $plan = $this->getLastEntity('plan', true);
        $planItem = $this->getLastEntity('item', true);

        $this->assertNull($addon);

        $this->assertEquals(1, $lineItems['count']);
        $this->assertNull($lineItems['items'][0]['ref_id']);
        $this->assertNull($lineItems['items'][0]['ref_type']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);

        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2000, $invoice['amount']);

        $this->assertEquals($schedule['id'], $subscription['schedule_id']);

        $this->assertEquals('2/monthly', $schedule['name']);
        $this->assertEquals($plan['period'], $schedule['period']);
        $this->assertEquals($plan['interval'], $schedule['interval']);
        // Since there is no start_at, we don't set any anchor.
        // The default is set to 1.
        $this->assertEquals(1, $schedule['anchor']);
        $this->assertEquals(0, $schedule['delay']);

        $this->assertEquals($subscription['id'], 'sub_' . $scheduleTask['entity_id']);
        $this->assertEquals('subscription', $scheduleTask['entity_type']);
        $this->assertEquals($schedule['id'], $scheduleTask['schedule_id']);
        $this->assertEquals('subscription', $scheduleTask['type']);
        // By default, it gets set to start of the day (midnight)
        $this->assertLessThan(time(), $scheduleTask['next_run_at']);
    }

    public function testCreateSubscriptionWithStartAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $subscription = $this->getLastEntity('subscription', true);
        $plan = $this->getLastEntity('plan', true);
        $planItem = $this->getLastEntity('item', true);
        $schedule = $this->getLastEntity('schedule', true);
        $scheduleTask = $this->getLastEntity('schedule_task', true);
        $invoice = $this->getLastEntity('invoice', true);
        $lineItem = $this->getLastEntity('line_item', true);
        $addon = $this->getLastEntity('addon', true);

        $this->assertNull($invoice);
        $this->assertNull($lineItem);
        $this->assertNull($addon);

        $this->assertEquals($schedule['id'], $subscription['schedule_id']);

        $this->assertEquals('2/monthly/20', $schedule['name']);
        $this->assertEquals($plan['period'], $schedule['period']);
        $this->assertEquals($plan['interval'], $schedule['interval']);
        // The subscription start is on 20th Jan
        $this->assertEquals(20, $schedule['anchor']);
        $this->assertEquals(0, $schedule['delay']);

        $this->assertEquals($subscription['id'], 'sub_' . $scheduleTask['entity_id']);
        $this->assertEquals('subscription', $scheduleTask['entity_type']);
        $this->assertEquals($schedule['id'], $scheduleTask['schedule_id']);
        $this->assertEquals('subscription', $scheduleTask['type']);
        // By default, it gets set to start of the day (midnight)
        $this->assertLessThanOrEqual($subscription['start_at'], $scheduleTask['next_run_at']);
    }

    public function testCreateSubscriptionWeeklyIntervalWithStartAt()
    {
        $this->createSubscriptionPreRequisiteEntities(['period' => 'weekly']);

        $this->startTest();

        $subscription = $this->getLastEntity('subscription', true);
        $plan = $this->getLastEntity('plan', true);
        $planItem = $this->getLastEntity('item', true);
        $schedule = $this->getLastEntity('schedule', true);
        $scheduleTask = $this->getLastEntity('schedule_task', true);

        $this->assertEquals($schedule['id'], $subscription['schedule_id']);

        $this->assertEquals('2/weekly/6', $schedule['name']);
        $this->assertEquals($plan['period'], $schedule['period']);
        $this->assertEquals($plan['interval'], $schedule['interval']);
        // The subscription start is on 20th Jan
        $this->assertEquals(6, $schedule['anchor']);
        $this->assertEquals(0, $schedule['delay']);

        $this->assertEquals($subscription['id'], 'sub_' . $scheduleTask['entity_id']);
        $this->assertEquals('subscription', $scheduleTask['entity_type']);
        $this->assertEquals($schedule['id'], $scheduleTask['schedule_id']);
        $this->assertEquals('subscription', $scheduleTask['type']);
        // By default, it gets set to start of the day (midnight)
        $this->assertLessThanOrEqual($subscription['start_at'], $scheduleTask['next_run_at']);
    }

    public function testCreateSubscriptionWithNoStartAtAndWithAddon()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $items = $this->getEntities('item', [], true);
        $addon = $this->getLastEntity('addon', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(2, $items['count']);

        $addonItem = $items['items'][0];
        $this->assertEquals('addon', $addonItem['type']);
        $this->assertEquals('Sample Upfront Amount', $addonItem['name']);
        $this->assertEquals(300, $addonItem['amount']);

        // This is just created via fixtures
        $planItem = $items['items'][1];
        $this->assertEquals('plan', $planItem['type']);

        $this->assertEquals($subscription['id'], $addon['subscription_id']);
        $this->assertEquals($addonItem['id'], $addon['item_id']);
        $this->assertEquals($invoice['id'], $addon['invoice_id']);

        $this->assertEquals(2, $lineItems['count']);

        $addonLi = $lineItems['items'][0];
        $this->assertEquals(300, $addonLi['amount']);
        $this->assertEquals(300, $addonLi['gross_amount']);
        $this->assertEquals(1, $addonLi['quantity']);
        $this->assertEquals('Sample Upfront Amount', $addonLi['name']);
        $this->assertEquals($addon['id'], $addonLi['ref_id']);
        $this->assertEquals('addon', $addonLi['ref_type']);
        $this->assertEquals($addonItem['id'], $addonLi['item_id']);
        // This is not visible to the public. Hence, unsigned. Also, polymorphic.
        $this->assertEquals($invoice['id'], 'inv_' . $addonLi['entity_id']);

        $mainLi = $lineItems['items'][1];
        $this->assertEquals($planItem['id'], $mainLi['item_id']);
        $this->assertNull($mainLi['ref_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $mainLi['entity_id']);
        $this->assertEquals(2000, $mainLi['amount']);

        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2300, $invoice['amount']);
    }

    public function testCreateSubscriptionWithNoStartAtAndWithAddonItemId()
    {

    }

    public function testCreateSubscriptionWithMultipleQuantityAddon()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);
        $items = $this->getEntities('item', [], true);
        $addon = $this->getLastEntity('addon', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(2, $items['count']);

        $addonItem = $items['items'][0];
        $this->assertEquals('addon', $addonItem['type']);
        $this->assertEquals('Sample Upfront Amount', $addonItem['name']);
        $this->assertEquals(300, $addonItem['amount']);

        $this->assertEquals(2, $lineItems['count']);

        $addonLi = $lineItems['items'][0];
        $this->assertEquals(300, $addonLi['amount']);
        $this->assertEquals(1200, $addonLi['gross_amount']);
        $this->assertEquals(4, $addonLi['quantity']);
        $this->assertEquals($addon['id'], $addonLi['ref_id']);
        $this->assertEquals($addonItem['id'], $addonLi['item_id']);

        // (4 * 300)[addon amount] + 2000 [plan amount]
        $this->assertEquals(3200, $invoice['amount']);
    }

    public function testCreateSubscriptionWithStartAtAndAddon()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $items = $this->getEntities('item', [], true);
        $addon = $this->getLastEntity('addon', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(2, $items['count']);
        $item = $items['items'][0];
        $this->assertEquals('addon', $item['type']);
        $this->assertEquals('Sample Upfront Amount', $item['name']);
        $this->assertEquals(300, $item['amount']);

        // This is just created via fixtures
        $planItem = $items['items'][1];
        $this->assertEquals('plan', $planItem['type']);

        $this->assertEquals($subscription['id'], $addon['subscription_id']);
        $this->assertEquals($item['id'], $addon['item_id']);
        $this->assertEquals($invoice['id'], $addon['invoice_id']);

        $this->assertEquals(1, $lineItems['count']);

        $addonLi = $lineItems['items'][0];
        $this->assertEquals(300, $addonLi['amount']);
        $this->assertEquals('Sample Upfront Amount', $addonLi['name']);
        $this->assertEquals($addon['id'], $addonLi['ref_id']);
        $this->assertEquals('addon', $addonLi['ref_type']);
        $this->assertEquals($item['id'], $addonLi['item_id']);
        // This is not visible to the public. Hence, unsigned. Also, polymorphic.
        $this->assertEquals($invoice['id'], 'inv_' . $addonLi['entity_id']);

        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(300, $invoice['amount']);
    }

    public function testCreateSubscriptionWithOneYearLateStartAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();
    }

    public function testCreateSubscriptionWithPastTime()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();
    }

    public function testCreateSubscriptionWithTotalCount()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $invoice = $this->getLastEntity('invoice', true);

        $this->assertNull($invoice);
    }

    public function testCreateSubscriptionWithEndAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();
    }

    public function testCreateSubscriptionWithBothTotalCountAndEndAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();
    }

    public function testCreateSubscriptionWithEndAtLesserThanStartAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();
    }

    public function testCreateSubscriptionWithVeryFarEndAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();
    }

    public function testCreateSubscriptionWithoutTotalCountAndEndAt()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();
    }

    public function testFetchSubscription()
    {
        $this->testCreateSubscriptionWithNoStartAt();

        $subscription = $this->getLastEntity('subscription', true);

        $this->ba->privateAuth();

        $subscriptionId = $subscription['id'];

        $this->testData[__FUNCTION__]['request']['url'] = "/subscriptions/$subscriptionId";

        $this->testData[__FUNCTION__]['response']['content']['id'] = $subscriptionId;

        $this->startTest();
    }

    public function testFetchMultipleSubscription()
    {
        $this->testCreateSubscriptionWithNoStartAt();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchMultipleSubscriptionWithEmailFilter()
    {
        $this->testCreateSubscriptionWithNoStartAt();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testFetchMultipleSubscriptionWithEmailFilterNegative()
    {
        $this->testCreateSubscriptionWithNoStartAt();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetInvoicesForSubscription()
    {
        $this->testCreateSubscriptionWithNoStartAt();

        $subscription = $this->getLastEntity('subscription', true);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['subscription_id'] = $subscription['id'];

        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals($subscription['id'], $response['items'][0]['subscription_id']);

        $this->ba->privateAuth();
    }

    // Type should be exposed only when merchant is accessing the subscription from dashboard
    public function testSubscriptionTypeExposure()
    {
        // This uses private auth
        $subscription = $this->createSubscription();

        $this->assertArrayNotHasKey('type', $subscription);

        // This uses proxy auth
        $subscription = $this->getEntityById('subscription', $subscription['id']);

        $this->assertArrayHasKey('type', $subscription);
    }

    protected function getCreateSubscriptionRequestContent($function, $planId = null)
    {
        $requestContent = $this->testData[$function];

        if ($planId === null)
        {
            $plan = $this->fixtures->plan->create();

            $planId = $plan->getPublicId();
        }

        $requestContent['response']['content']['plan_id'] = $planId;
        $requestContent['response']['content']['plan_id'] = $planId;

        return $requestContent;
    }
}
