<?php


namespace Functional\Merchant;

use RZP\Models\Workflow\Action\Repository as WorkflowActionRepository;
use RZP\Models\Workflow\Action\Entity as WorkflowActionEntity;
use RZP\Services\Mock\DruidService as MockDruidService;
use RZP\Models\Merchant\Fraud\HealthChecker\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Services\FreshdeskTicketClient;
use RZP\Tests\Functional\TestCase;
use RZP\Jobs\RiskHealthChecker;
use Queue;

class AppCheckerTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/AppCheckerTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testLive()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testNotLive()
    {
        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testMilestoneCronLive()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
            ]
        ]);

        $druidService = $this->getMockBuilder(MockDruidService::class)
                             ->setConstructorArgs([$this->app])
                             ->onlyMethods([ 'getDataFromDruid'])
                             ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = ['merchants_id' => $merchant->getId()];

        $druidService->method( 'getDataFromDruid')
                     ->willReturn([null, [$dataFromDruid]]);

        $this->ba->cronAuth();

        $this->startTest();

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::MILESTONE_CHECKER_EVENT, Constants::APP_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNull($redisKey);
    }

    public function testMilestoneCronNotLive()
    {
        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp.dummy',
            ]
        ]);

        $druidService = $this->getMockBuilder(MockDruidService::class)
                             ->setConstructorArgs([$this->app])
                             ->onlyMethods([ 'getDataFromDruid'])
                             ->getMock();

        $this->app->instance('druid.service', $druidService);

        $dataFromDruid = ['merchants_id' => $merchant->getId()];

        $druidService->method( 'getDataFromDruid')
                     ->willReturn([null, [$dataFromDruid]]);

        $this->ba->cronAuth();

        $this->startTest();

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::MILESTONE_CHECKER_EVENT, Constants::APP_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNotNull($redisKey);
    }

    public function testPeriodicCronLive()
    {
        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'hold_funds' => false,
            'activated'  => true,
            'activated_at' => now()->timestamp - 24*60*60,
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
            ]
        ]);

        $this->fixtures->create('payment', [
            'merchant_id' => $merchant->getId(),
        ]);

        $this->startTest();

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::APP_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNull($redisKey);
    }

    public function testPeriodicCronLiveWithTxnUrls()
    {
        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'hold_funds' => false,
            'activated'  => true,
            'activated_at' => now()->timestamp - 24*60*60,
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
                'txn_playstore_urls' => [
                    'https://play.google.com/store/apps/details?id=com.whatsapp',
                ],
            ]
        ]);

        $this->fixtures->create('payment', [
            'merchant_id' => $merchant->getId(),
        ]);

        $this->startTest();

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::APP_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNull($redisKey);
    }

    public function testPeriodicCronNotLive()
    {
        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'hold_funds' => false,
            'activated'  => true,
            'activated_at' => now()->timestamp - 24*60*60,
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp.dummy',
                'applestoreurl' => 'https://play.google.com/store/apps/details?id=com.dummy123123',
            ]
        ]);

        $this->fixtures->create('payment', [
            'merchant_id' => $merchant->getId(),
        ]);

        $this->startTest();

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::APP_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNotNull($redisKey);
    }

    public function testRetryCronLive()
    {
        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant');

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::APP_CHECKER);

        $this->app['cache']->connection()->hset($redisMap, $merchant->getId(), now()->timestamp - Constants::RETRY_WAIT_SECONDS);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
            ]
        ]);

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(RiskHealthChecker::class);
    }

    public function testRetryCronNotLive()
    {
        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant');

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::APP_CHECKER);

        $this->app['cache']->connection()->hset($redisMap, $merchant->getId(), now()->timestamp - Constants::RETRY_WAIT_SECONDS);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp.dummy',
            ]
        ]);

        // todo: mock ras call

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(RiskHealthChecker::class);
    }

    // test reminder cron
    // test workflow action endpoints ==> handleManualFOH and handleAutoFOH

    public function testFreshdeskWebhook()
    {
        $this->ba->freshdeskWebhookAuth();

        $merchant = $this->fixtures->create('merchant');

        /** @var WorkflowActionEntity $workflowAction */
        $workflowAction = $this->fixtures->create('workflow_action');
        $workflowAction->tag(sprintf(Constants::FD_TICKET_ID_TAG_FMT[Constants::APP_CHECKER], "test_fd_124"));

        $this->app['cache']->connection()->hset(Constants::REDIS_REMINDER_MAP_NAME[Constants::APP_CHECKER], $merchant->getId(), now()->timestamp - Constants::REMINDER_WAIT_SECONDS);

        $this->startTest();

        /** @var WorkflowActionEntity $workflowActionAfterRequest */
        $workflowActionAfterRequest = (new WorkflowActionRepository())->find($workflowAction->getId());

        $this->assertContains(ucfirst(Constants::MERCHANT_REPLIED_TAG), $workflowActionAfterRequest->tagNames());
    }
}
