<?php

namespace RZP\Tests\Functional\Schedule;

use Carbon\Carbon;
use RZP\Models\Schedule\Anchor;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;

class ScheduleTest extends TestCase
{
    use ScheduleTrait;
    use SubscriptionTrait;
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ScheduleTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testFetchSettlementSchedules()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateSchedule()
    {
        $schedule = $this->createSchedule();

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($data, $schedule);
    }

    public function testEditSchedule()
    {
        $schedule = $this->createSchedule();

        $request = $this->testData[__FUNCTION__];

        $request['url'] = $request['url'] . $schedule['id'];

        $response = $this->makeRequestAndGetContent($request);
    }

    public function testScheduleDefaultAnchor()
    {
        $input = $this->getDefaultScheduleArray();

        unset($input['anchor']);

        $response = $this->createSchedule($input);

        $this->assertEquals(Anchor::MONTHLY_WEEK_DAY, $response['anchor']);
    }

    public function testScheduleInvalidPeriod()
    {
        $input = $this->getDefaultScheduleArray();

        $input['period'] = 'invalidPeriod';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($input)
        {
            $this->createSchedule($input);
        });
    }

    public function testScheduleInvalidType()
    {
        $this->markTestSkipped('No type in schedules now');

        $input = $this->getDefaultScheduleArray();

        $input['type'] = 'not_settlement';

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($input) {
            $this->createSchedule($input);
        });
    }

    public function testScheduleInvalidHour()
    {
        $input = $this->testData['timedScheduleBody'];

        $input['period'] = 'hourly';
        $input['delay'] = 0;

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($input)
        {
            $this->createSchedule($input);
        });
    }

    public function testSettledAtTimestampForTimedMerchant()
    {
        $input = $this->testData['timedScheduleBody'];

        // Create and assign timed schedule having hour set to 5
        $response = $this->createAndAssignSchedule($input);

        $data = ['amount' => 100];

        $payment = $this->fixtures->create('payment:captured', $data);

        $txn = $this->getLastTransaction(true);

        $time = Carbon::createFromTimestamp($txn['settled_at'], Timezone::IST);

        // Check if time is set to hour value in schedule
        $this->assertEquals(12, $time->hour);
    }

    public function testGetSchedule()
    {
        $schedule = $this->createSchedule();

        $this->ba->appAuth();

        $response = $this->fetchSchedule($schedule['id']);

        $this->ba->adminAuth();

        $this->assertArraySelectiveEquals($schedule, $response);
    }

    public function testDeleteSchedule()
    {
        $schedule = $this->createSchedule();

        $this->deleteSchedule($schedule['id']);
    }

    public function testDeleteScheduleInUse()
    {
        $schedule = $this->createAndAssignSchedule();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($schedule)
        {
            $this->ba->adminAuth();
            $this->deleteSchedule($schedule['id']);
        });

        // Assign a new schedule so the original one becomes unused
        $this->createAndAssignSchedule();

        $this->deleteSchedule($schedule['id']);
    }

    public function testAssignScheduleById()
    {
        $this->createAndAssignSchedule();
    }

    public function testUpdateNextRunAt()
    {
        // Basic T2 assigned to merchant
        $task = $this->fixtures->create('merchant:schedule_task',
                [
                    'merchant_id' => '10000000000000',
                    'schedule'    => [
                        'interval'          => 1,
                        'delay'             => 2,
                        'hour'              => 0,
                    ],
                ]);

        // next_run_at for task is right now set to something that isn't 12
        $time = Carbon::createFromTimestamp($task->getNextRunAt(), Timezone::IST);
        $this->assertNotEquals(12, $time->hour);

        // Update schedule hour to 12
        $schedule = $this->getLastEntity('schedule', true);
        $this->ba->adminAuth();
        $res = $this->editSchedule($schedule['id'], ['hour' => 12]);

        // Update all next_run_at values
        $this->ba->appAuth();
        $request = $this->testData[__FUNCTION__];
        $res = $this->makeRequestAndGetContent($request);

        // Task is updated
        $this->assertContains($task->getId(), $res['ids']);

        // next_run_at for task is now set to 12
        $task = $this->getEntityById('schedule_task', $task->getId(), true);
        $time = Carbon::createFromTimestamp($task['next_run_at'], Timezone::IST);
        $this->assertEquals(12, $time->hour);
    }

    protected function createSubscriptionToSync()
    {
        $this->fixtures->base->connection('test');

        $this->createSubscriptionPreRequisiteEntities();

        $this->fixtures->merchant->addFeatures(['subscriptions']);

        $request = $this->testData[__FUNCTION__];

        $customer = $this->getLastEntity('customer');

        $request['content']['customer_id'] = $customer['id'];

        $this->ba->privateAuth();

        return $this->makeRequestAndGetContent($request);
    }

    public function testExpireCredits()
    {
        $this->ba->appAuth();

       $promotionAttributes = [
            'credit_amount' => '1000',
        ];

        $promotion = $this->fixtures->create('promotion:recurring', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion['id'],
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->fixtures->merchant->activate('10000000000000');

        $this->applyCouponOnMerchant($coupon['code']);

        $request = $this->testData[__FUNCTION__];

        $time = Carbon::now(Timezone::IST);

        $time->addDay(32);

        Carbon::setTestNow($time);

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -1000);

        Carbon::setTestNow();
    }

    public function testExpireAndAssignCredits()
    {
        $this->ba->appAuth();

        $promotionAttributes = [
            'credit_amount' => '1000',
            'iterations'    => 2,
        ];

        $promotion = $this->fixtures->create('promotion:recurring', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion['id'],
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->fixtures->merchant->activate('10000000000000');

        $this->applyCouponOnMerchant($coupon['code']);

        $request = $this->testData['testExpireCredits'];

        $time = Carbon::now(Timezone::IST);

        $time->addDay(32);

        Carbon::setTestNow($time);

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getEntities('credits', [], true);

        $this->assertEquals($credits['items'][1]['value'], -1000);

        $this->assertEquals($credits['items'][2]['value'], 1000);

        $this->assertEquals(count($credits['items']), 3);

        $time->addDay(32);

        Carbon::setTestNow($time);

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getEntities('credits', [], true);

        $this->assertEquals(count($credits['items']), 4);

        Carbon::setTestNow();
    }

    public function testExpireUsedCredits()
    {
        $this->ba->appAuth();

        $promotionAttributes = [
            'credit_amount' => '1000',
        ];

        $promotion1 = $this->fixtures->create('promotion:recurring', $promotionAttributes);

        $promotion2 = $this->fixtures->create('promotion:recurring', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion1['id'],
            'entity_type' => 'promotion',
            'code'        => 'RANDOM1',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon1 = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion2['id'],
            'entity_type' => 'promotion',
            'code'        => 'RANDOM2',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon2 = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $this->fixtures->merchant->activate('10000000000000');

        $this->applyCouponOnMerchant($coupon1['code']);

        $this->applyCouponOnMerchant($coupon2['code']);

        $payment = $this->doAuthAndCapturePayment();

        $this->ba->appAuth();

        $request = $this->testData['testExpireCredits'];

        $time = Carbon::now(Timezone::IST);

        $time->addDay(32);

        Carbon::setTestNow($time);

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getEntities('credits', [], true);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -1000);

        Carbon::setTestNow();
    }

    public function testExpireCreditsAfterActivation()
    {
        $this->ba->appAuth();

        $merchantSignupRequest = [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com',
                'coupon_code' => 'RANDOM-123',
            ],
            'url'    => '/merchants',
            'method' => 'POST',
        ];

        $response = $this->makeRequestAndGetContent($merchantSignupRequest);

        $promotionAttributes = [
            'credit_amount' => '1000',
        ];

        $promotion = $this->fixtures->create('promotion:recurring', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion['id'],
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
        ];

        $coupon = $this->fixtures->create('coupon:coupon', $couponAttributes);

        $merchantId = '1X4hRFHFx4UiXt';

        $this->applyCouponOnMerchant($coupon['code'], $merchantId);

        $time = Carbon::now(Timezone::IST);

        $time->addDay(32);

        Carbon::setTestNow($time);

        $merchantAttributes = [
            'website' => 'abc.com',
            'category' => 1100,
            'billing_label' => 'labore',
            'transaction_report_email' => 'test@razorpay.com',
        ];

        $this->fixtures->edit('merchant', $merchantId, $merchantAttributes);

        $activationRequest = [
            'url' => '/merchants/' . $merchantId .  '/activate',
            'method' => 'post',
        ];

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchantId);

        $response = $this->makeRequestAndGetContent($activationRequest);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], 1000);

        $request = $this->testData['testExpireCredits'];

        $time->addDay(32);

        Carbon::setTestNow($time);

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -1000);

    }


    protected function applyCouponOnMerchant(string $code, string $merchantId = '10000000000000')
    {
        $request = $this->testData[__FUNCTION__];

        $request['content']['code'] = $code;

        $request['content']['merchant_id'] = $merchantId;

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    protected function merchantAssignPricingPlan($planId, $id = '10000000000000')
    {
        $request = array(
            'url' => '/merchants/'.$id.'/pricing',
            'method' => 'POST',
            'content' => ['pricing_plan_id' => $planId]);

        return $this->makeRequestAndGetContent($request);
    }
}
