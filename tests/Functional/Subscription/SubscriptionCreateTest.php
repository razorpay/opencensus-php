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

    // ------------------ PLAN TESTS ------------------

    public function testCreatePlanWithItemId()
    {
        $this->fixtures->create('item', ['type' => 'plan']);

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
        $this->fixtures->create('item', ['type' => 'plan']);

        $this->startTest();
    }

    public function testCreatePlanWithBadYearlyIntervalPeriod()
    {
        $this->fixtures->create('item', ['type' => 'plan']);

        $this->startTest();
    }

    // ------------------ END PLAN TESTS ------------------

    public function testCreateSubscriptionWithoutCustomerId()
    {
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
        $addOn = $this->getLastEntity('add_on', true);
        $lineItems = $this->getEntities('line_item', [], true);
        $scheduleTask = $this->getLastEntity('schedule_task', true);
        $subscription = $this->getLastEntity('subscription', true);
        $plan = $this->getLastEntity('plan', true);
        $planItem = $this->getLastEntity('item', true);

        $this->assertNull($addOn);

        $this->assertEquals(1, $lineItems['count']);
        $this->assertNull($lineItems['items'][0]['ref_id']);
        $this->assertNull($lineItems['items'][0]['ref_type']);
        $this->assertEquals($invoice['id'], 'inv_' . $lineItems['items'][0]['entity_id']);

        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2000, $invoice['amount']);

        $this->assertEquals($schedule['id'], $subscription['schedule_id']);

        $this->assertEquals($planItem['name'], $schedule['name']);
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
        $addOn = $this->getLastEntity('add_on', true);

        $this->assertNull($invoice);
        $this->assertNull($lineItem);
        $this->assertNull($addOn);

        $this->assertEquals($schedule['id'], $subscription['schedule_id']);

        $this->assertEquals($planItem['name'], $schedule['name']);
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

        $this->assertEquals($planItem['name'], $schedule['name']);
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

    public function testCreateSubscriptionWithNoStartAtAndWithAddOn()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $items = $this->getEntities('item', [], true);
        $addOn = $this->getLastEntity('add_on', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(2, $items['count']);

        $addOnItem = $items['items'][0];
        $this->assertEquals('add_on', $addOnItem['type']);
        $this->assertEquals('Sample Upfront Amount', $addOnItem['name']);
        $this->assertEquals(300, $addOnItem['amount']);

        // This is just created via fixtures
        $planItem = $items['items'][1];
        $this->assertEquals('plan', $planItem['type']);

        $this->assertEquals($subscription['id'], $addOn['subscription_id']);
        $this->assertEquals($addOnItem['id'], $addOn['item_id']);
        $this->assertEquals($invoice['id'], $addOn['invoice_id']);

        $this->assertEquals(2, $lineItems['count']);

        $addOnLi = $lineItems['items'][0];
        $this->assertEquals(300, $addOnLi['amount']);
        $this->assertEquals('Sample Upfront Amount', $addOnLi['name']);
        $this->assertEquals($addOn['id'], $addOnLi['ref_id']);
        $this->assertEquals('add_on', $addOnLi['ref_type']);
        $this->assertEquals($addOnItem['id'], $addOnLi['item_id']);
        // This is not visible to the public. Hence, unsigned. Also, polymorphic.
        $this->assertEquals($invoice['id'], 'inv_' . $addOnLi['entity_id']);

        $mainLi = $lineItems['items'][1];
        $this->assertNull($mainLi['item_id']);
        $this->assertNull($mainLi['ref_id']);
        $this->assertEquals($invoice['id'], 'inv_' . $mainLi['entity_id']);
        $this->assertEquals(2000, $mainLi['amount']);

        $this->assertEquals($subscription['id'], $invoice['subscription_id']);
        $this->assertEquals('issued', $invoice['status']);
        $this->assertEquals(2300, $invoice['amount']);
    }

    public function testCreateSubscriptionWithNoStartAtAndWithAddOnItemId()
    {

    }

    public function testCreateSubscriptionWithStartAtAndAddOn()
    {
        $this->createSubscriptionPreRequisiteEntities();

        $this->startTest();

        $subscription = $this->getLastEntity('subscription', true);
        $invoice = $this->getLastEntity('invoice', true);
        $items = $this->getEntities('item', [], true);
        $addOn = $this->getLastEntity('add_on', true);
        $lineItems = $this->getEntities('line_item', [], true);

        $this->assertEquals(2, $items['count']);
        $item = $items['items'][0];
        $this->assertEquals('add_on', $item['type']);
        $this->assertEquals('Sample Upfront Amount', $item['name']);
        $this->assertEquals(300, $item['amount']);

        // This is just created via fixtures
        $planItem = $items['items'][1];
        $this->assertEquals('plan', $planItem['type']);

        $this->assertEquals($subscription['id'], $addOn['subscription_id']);
        $this->assertEquals($item['id'], $addOn['item_id']);
        $this->assertEquals($invoice['id'], $addOn['invoice_id']);

        $this->assertEquals(1, $lineItems['count']);

        $addOnLi = $lineItems['items'][0];
        $this->assertEquals(300, $addOnLi['amount']);
        $this->assertEquals('Sample Upfront Amount', $addOnLi['name']);
        $this->assertEquals($addOn['id'], $addOnLi['ref_id']);
        $this->assertEquals('add_on', $addOnLi['ref_type']);
        $this->assertEquals($item['id'], $addOnLi['item_id']);
        // This is not visible to the public. Hence, unsigned. Also, polymorphic.
        $this->assertEquals($invoice['id'], 'inv_' . $addOnLi['entity_id']);

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

    protected function createSubscriptionPreRequisiteEntities(array $planAttributes = [])
    {
        $customer = $this->fixtures->create('customer');

        $planItem = $this->fixtures->create('item', [
            'name' => 'test plan',
            'amount' => 2000,
            'currency' => 'INR',
            'type' => 'plan',
        ]);

        $plan = $this->fixtures->create('plan', $planAttributes);

        return [
            'plan' => $plan,
            'customer' => $customer,
            'plan_item' => $planItem
        ];
    }

    protected function getCreateSubscriptionRequestContent($function, $planId = null)
    {
        $requestContent = $this->testData[$function];

        if ($planId === null)
        {
            $plan = $this->fixtures->create('plan');

            $planId = $plan->getPublicId();
        }

        $requestContent['response']['content']['plan_id'] = $planId;
        $requestContent['response']['content']['plan_id'] = $planId;

        return $requestContent;
    }
}
