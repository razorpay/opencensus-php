<?php

namespace RZP\Tests\Functional\Schedule;

use DB;
use Carbon\Carbon;
use RZP\Models\Base\EsDao;
use RZP\Models\Schedule\Anchor;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Workflow\Action\Differ\Entity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Schedule\ScheduleTrait;
use RZP\Tests\Functional\Helpers\Freshdesk\FreshdeskTrait;
use RZP\Models\Workflow\Observer\ScheduleSettlementObserver;
use RZP\Tests\Functional\Helpers\Subscription\SubscriptionTrait;
use RZP\Models\Workflow\Observer\Constants as ObserverConstants;

class ScheduleTest extends TestCase
{
    protected $esDao;

    protected $esClient;

    use ScheduleTrait;
    use SubscriptionTrait;
    use PaymentTrait;
    use WorkflowTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use FreshdeskTrait;

    const EXPECTED_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE  = 'EXPECTED_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE';

    const EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER               = 'EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/ScheduleTestData.php';

        parent::setUp();

        $this->ba->adminAuth();

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();

        $this->setUpFreshdeskClientMock();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);
    }

    public function testFetchSettlementSchedules()
    {
        $this->createSchedule([
            'name'       => 'Basic T60',
            'period'     => 'daily',
            'interval'   => 1,
            'delay'      => 60,
            'org_id'     => 'org_100000razorpay',
        ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateSchedule()
    {
        $schedule = $this->createSchedule();

        $data = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($data, $schedule);
    }

    public function testCreateScheduleWithoutType()
    {
        $schedule = $this->createSchedule([
            'name'       => 'Every Wednesday',
            'period'     => 'weekly',
            'interval'   => 1,
            'delay'      => 1,
            'anchor'     => 3,
        ]);

        $data = $this->testData['testCreateSchedule'];

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
        //$this->markTestSkipped('No type in schedules now');

        $input = $this->getDefaultScheduleArray();

        $input['type'] = 'invalidType';

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
        $response = $this->createAndAssignScheduleAndAssertId($input);

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

        $this->ba->adminAuth();

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
        $schedule = $this->createAndAssignScheduleAndAssertId();

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use ($schedule)
        {
            $this->ba->adminAuth();
            $this->deleteSchedule($schedule['id']);
        });

        // Assign a new schedule so the original one becomes unused
        $this->createAndAssignScheduleAndAssertId();

        $this->deleteSchedule($schedule['id']);
    }

    public function testAssignScheduleById()
    {
        $this->createAndAssignScheduleAndAssertId();
    }

    public function testAssignScheduleWithLinkedAccounts()
    {
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($actionId, $feature, $mode)
                {
                    return 'on';

                }) );

        $schedule = $this->createSchedule();

        $request = $this->testData['testAssignScheduleById'];

        $request['content']['schedule_id'] = $schedule['id'];

        $this->ba->adminAuth();

        $this->fixtures->create('merchant:marketplace_account');

        $this->makeRequestAndGetContent($request);

        $merchantIds = array('10000000000000');

        $merchants = $this->getDbEntity('merchant', ['parent_id' => '10000000000000'] );

        foreach($merchants as $merchant)
        {
            $merchantIds[] = $merchant['id'];
        }

        $updatedSchedules = $this->getDbEntities('schedule_task', array(['merchant_id', 'in', $merchantIds]));

        foreach($updatedSchedules as $updatedSchedule)
        {
            $this->assertEquals($schedule['id'], $updatedSchedule['schedule_id']);
        }

        return $schedule;
    }

    public function testCreateAssignScheduleWorkflowWithObserverData()
    {
        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($actionId, $feature, $mode)
                {
                    return 'on';

                }) );

        $scheduleArray = $this->getDefaultScheduleArray();

        $schedule     = $this->fixtures->create('schedule', $scheduleArray);

        $this->setupWorkflow("Assign Schedule", "schedule_assign");

        $response = $this->assignSchedule($schedule["id"], $this->testData['testCreateAssignScheduleWorkflowWithObserverData']);

        $expectedWorkflow  = $this->getExpectedDataArray(self::EXPECTED_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE);

        $this->assertArrayHasKey("id", $response );

        $this->assertStringStartsWith('w_action_', $response['id']);

        $this->assertArraySelectiveEquals($expectedWorkflow, $response);

        $this->esClient->indices()->refresh();

        $this->updateObserverData($response['id'],  [
            'ticket_id'     => 123,
            'fd_instance'   => 'rzpind'
        ]);

        $this->esClient->indices()->refresh();

        $workflowData = $this->getWorkflowData();

        $expectedWorkFlowData  = $this->getExpectedDataArray(self::EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER);

        $this->fixtures->create('merchant_freshdesk_tickets', $this->getDefaultFreshdeskArray());

        $this->assertArraySelectiveEquals($expectedWorkFlowData, $workflowData);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123/reply', 'post',
            [
                'body' => implode("<br><br>",(new ScheduleSettlementObserver([
                                                Entity::ENTITY_ID => '10000000000000',
                                                Entity::PAYLOAD=>[
                                                    "schedule_id" => $schedule["id"]

                    ]]))->getTicketReplyContent(ObserverConstants::APPROVE,'10000000000000')),
            ],
            [

            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123?include=requester', 'GET',
            [],
            [
                'id'        => '123',
                'tags'      => ['xyz']
            ]);

        $this->expectFreshdeskRequestAndRespondWith('tickets/123', 'PUT',
            [
                'status'    => 4,
                'tags'      => ['xyz','automated_workflow_response']
            ],
            [
                'id'        => '123',
            ]);

        $this->performWorkflowAction($workflowAction['id'], true);

        $request = [
            'url' => '/settlements/schedules',
            'method' => 'get',
            'content' => []
        ];

        $res = $this->makeRequestAndGetContent($request);

        $isScheduleAssigned = false;

        foreach ($res['items'] as $schedule1)
        {
            if ($schedule1["id"] === $schedule['id'])
            {
                $isScheduleAssigned = true;
                break;
            }
        }

        $this->assertTrue($isScheduleAssigned);
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
        $this->ba->adminAuth();
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

    public function testExpireCreditsDaily()
    {
        $this->ba->adminAuth();

        $promotionAttributes = [
            'credit_amount' => '1000',
        ];

        $promotion = $this->fixtures->create('promotion:onetime_daily', $promotionAttributes);

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

        $time->addDay(1);

        Carbon::setTestNow($time);

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -1000);

        Carbon::setTestNow();
    }

    public function testExpireCredits()
    {
        $this->ba->adminAuth();

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

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -1000);

        Carbon::setTestNow();
    }

    public function testExpireAndAssignCredits()
    {
        $this->ba->adminAuth();

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

        $this->ba->cronAuth();

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

        $this->ba->cronAuth();

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getEntities('credits', [], true);

        $this->assertEquals(count($credits['items']), 4);

        Carbon::setTestNow();
    }

    public function testExpireUsedCredits()
    {
        $this->ba->adminAuth();

        $promotionAttributes = [
            'credit_amount' => '1000',
            'credit_type'   => 'fee',
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

        $this->ba->cronAuth();

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
        $this->ba->adminAuth();

        $merchantSignupRequest = [
            'content' => [
                'id'          => '1X4hRFHFx4UiXt',
                'name'        => 'Tester',
                'email'       => 'test@localhost.com',
                'coupon_code' => 'RANDOM-123',
            ],
            'url'     => '/merchants',
            'method'  => 'POST',
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

        $this->fixtures->on('live')->edit('merchant_detail', $merchantId, [
            'submitted'           => true,
            'bank_branch_ifsc'    => 'CBIN0281697',
            'bank_account_number' => '0002020000304030434',
            'bank_account_name'   => 'random name',
            'contact_mobile'      => '9999999999',
            'business_category'   => 'financial_services',
            'business_subcategory'=> 'accounting',
        ]);

        $this->fixtures->on('test')->edit('merchant_detail', $merchantId, [
            'submitted'           => true,
            'bank_branch_ifsc'    => 'CBIN0281697',
            'bank_account_number' => '0002020000304030434',
            'bank_account_name'   => 'random name',
            'contact_mobile'      => '9999999999',
            'business_category'   => 'financial_services',
            'business_subcategory'=> 'accounting',
        ]);

        $activationRequest = [
            'url'     => '/merchant/activation/' . $merchantId . '/activation_status',
            'method'  => 'patch',
            'content' => [
                'activation_status' => 'activated',
            ],
        ];

        $this->fixtures->create('merchant_website', [
            'merchant_id'              => $merchantId,
        ]);

        $this->ba->adminAuth();

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchantId);

        $response = $this->makeRequestAndGetContent($activationRequest);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], 1000);

        $this->ba->cronAuth();

        $request = $this->testData['testExpireCredits'];

        $time->addDay(32);

        Carbon::setTestNow($time);

        $response = $this->makeRequestAndGetContent($request);

        $credits = $this->getLastEntity('credits', true);

        $this->assertEquals($credits['value'], -1000);

        Carbon::setTestNow();
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

    protected function getExpectedDataArray($arrayType) : array
    {
        if ($arrayType === self::EXPECTED_WORKFLOW_CREATE_WITH_OBSERVER_DATA_RESPONSE)
        {
            return [
                "entity_id" =>  "10000000000000",
                "entity_name" =>  "schedule_task",
                "workflow" =>  [
                    "name" => "Assign Schedule",
                    "merchant_id" => "10000000000000"
                ],
                "permission" => [
                    "name" => "schedule_assign",
                ],
                "state" => "open",
                "maker_type" => "admin",
                "maker" => [
                    "email" => "superadmin@razorpay.com",
                    "name" => "test admin",
                ],
                "org_id" => "org_100000razorpay",
                "approved" =>  FALSE,
            ];
        }

        if ($arrayType === self::EXPECTED_WORKFLOW_ES_DATA_WITH_OBSERVER)
        {
            return [
                'url' => "https://api.razorpay.com/v1/merchants/10000000000000/schedules",
                'method' => "POST",
                'workflow_observer_data' =>  [
                    'ticket_id' => '123',
                    'fd_instance' => "rzpind"
                ],
                'state' => "open",
                'route' => "schedule_assign"
            ];
        }
    }

}
