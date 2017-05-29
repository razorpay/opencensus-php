<?php

namespace RZP\Tests\Functional\Schedule;

use Carbon\Carbon;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;

class ScheduleTest extends TestCase
{
    use ScheduleTrait;
    use SubscriptionTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/ScheduleTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
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

        $this->assertEquals(Carbon::MONDAY, $response['anchor']);
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

        $time = Carbon::createFromTimestamp($txn['settled_at'], 'Asia/Kolkata');

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

    public function testScheduleSyncLiveAndTest()
    {
        $this->ba->adminAuth('test');

        // Created schedule has default type settlement
        $testSchedule = $this->createSchedule();

        $this->ba->appAuthLive();

        // Settlement schedules are synced in test and live
        $liveSchedule = $this->fetchSchedule($testSchedule['id']);

        $this->assertArraySelectiveEquals($testSchedule, $liveSchedule);

        $this->ba->adminAuth('test');

        $updateTestData = ['name' => 'New name'];

        $this->editSchedule($testSchedule['id'], $updateTestData);

        $this->ba->appAuthLive();

        $liveSchedule = $this->fetchSchedule($testSchedule['id']);

        // Changes made in test mode are synced in live db
        $this->assertArraySelectiveEquals($updateTestData, $liveSchedule);

        $response = $this->createSubscriptionToSync();

        $testSchedule = $this->getLastEntity('schedule', true);

        $testScheduleTask = $this->getLastEntity('schedule_task', true);

        $this->ba->appAuthLive();

        // Schedule was created in test mode, but is still synced to live db
        $liveSchedule = $this->fetchSchedule($testSchedule['id']);

        // Schedule tasks aren't synced for subscription type, so this throws an error
        $this->runRequestResponseFlow(
            $this->testData[__FUNCTION__],
            function() use ($testScheduleTask)
            {
                $this->getEntityById(
                    'schedule_task',
                    $testScheduleTask['id'],
                    true,
                    'live');
            }
        );

        // Schedule task does exist in test db, so fetch works
        $testScheduleTask = $this->getEntityById(
                                'schedule_task',
                                $testScheduleTask['id'],
                                true,
                                'test');

        $this->assertEquals('subscription', $testScheduleTask['type']);
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
}
