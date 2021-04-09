<?php

namespace RZP\Tests\Functional\Order;

use Carbon\Carbon;
use RZP\Models\Order;
use RZP\Models\UpiMandate;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class UpiRecurringOrderTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/UpiRecurringOrderTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->fixtures->merchant->enableMethod('10000000000000', 'upi');

        $this->fixtures->merchant->addFeatures([Feature::CHARGE_AT_WILL]);

        $this->fixtures->create('terminal:shared_mindgate_recurring_terminal', ['merchant_id'=> '10000000000000']);
    }

    public function testCreateUpiRecurringOrder()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertEquals($order[Order\Entity::ID], $upiMandate[UpiMandate\Entity::ORDER_ID]);

        $this->assertEquals($order[Order\Entity::MERCHANT_ID], $upiMandate[UpiMandate\Entity::MERCHANT_ID]);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::CUSTOMER_ID]);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::FREQUENCY]);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::RECURRING_VALUE]);

        $frequency = $upiMandate[UpiMandate\Entity::FREQUENCY];
        $recurringValue = $upiMandate[UpiMandate\Entity::RECURRING_VALUE];

        $this->assertEquals(UpiMandate\Frequency::$frequencyToRecurringValueMap[$frequency], $recurringValue);

        $this->assertNotNull($upiMandate[UpiMandate\Entity::RECURRING_TYPE]);
    }

    public function testCreateOrderWithMaxAmountLesserThanMinLimit()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    public function testCreateOrderWithMaxAmountGreaterThanMaxLimit()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    public function testCreateOrderWithIncorrectFrequency()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    public function testCreateOrderWithStartTimeGreaterThanEndTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $this->assertNull($upiMandate);
    }

    public function testCreateOrderWithoutFrequency()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);
    }

    public function testCreateOrderWithDailyFrequency()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);

        $this->assertEquals(null, $upiMandate['recurring_value']);
    }

    public function testCreateOrderWithoutStartAndEndTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);

        $startTime = Carbon::createFromTimestamp($upiMandate['start_time']);
        $endTime = Carbon::createFromTimestamp($upiMandate['end_time']);

        $this->assertTrue($startTime->lessThan($endTime));

        $this->assertSame(10, $startTime->diffInYears($endTime));
    }

    public function testCreateOrderWithStartTimeAndNoEndTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $startTime = Carbon::createFromTimestamp($upiMandate['start_time']);
        $endTime = Carbon::createFromTimestamp($upiMandate['end_time']);

        $this->assertTrue($startTime->lessThan($endTime));
    }

    public function testCreateOrderWithEndTimeAndNoStartTime()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNotNull($upiMandate);

        $this->assertNotNull($order);

        $this->assertNotNull($upiMandate['start_time']);

        $this->assertNotNull($upiMandate['end_time']);

        $startTime = Carbon::createFromTimestamp($upiMandate['start_time']);
        $endTime = Carbon::createFromTimestamp($upiMandate['end_time']);

        $this->assertTrue($startTime->lessThan($endTime));
    }

    public function testCreateOrderAmountGreaterThanMaxAmount()
    {
        $this->startTest();

        $upiMandate = $this->getDbLastEntity('upi_mandate');

        $order = $this->getDbLastEntity('order');

        $this->assertNull($upiMandate);

        $this->assertNull($order);
    }

    public function testPreferencesForUpiRecurringOrder()
    {
        $this->testCreateUpiRecurringOrder();

        $order = $this->getDbLastEntity('order');

        $this->ba->publicAuth();

        $testData['request']['content'] = ['order_id' => $order->getPublicId()];

        $this->startTest($testData);
    }
}
