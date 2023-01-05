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

class WebsiteCheckerTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WebsiteCheckerTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testLive()
    {
        $this->markTestSkipped();

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testNotLive()
    {
        $this->markTestSkipped();

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testManualReview()
    {
        $this->markTestSkipped();

        $this->ba->batchAppAuth();

        $this->startTest();
    }

    public function testMilestoneCron()
    {
        $this->markTestSkipped();

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'business_website' => 'https://razorpay.com'
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

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::MILESTONE_CHECKER_EVENT, Constants::WEBSITE_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNull($redisKey);
    }

    public function testMilestoneCronNotLive()
    {
        $this->markTestSkipped();

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'business_website' => 'https://razorpay2.com'
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

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::MILESTONE_CHECKER_EVENT, Constants::WEBSITE_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNotNull($redisKey);
    }

    public function testPeriodicCron()
    {
        $this->markTestSkipped();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'hold_funds' => false,
            'activated'  => true,
            'activated_at' => now()->timestamp - 24*60*60,
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'business_website' => 'https://razorpay.com'
        ]);

        $this->fixtures->create('payment', [
            'merchant_id' => $merchant->getId(),
        ]);

        $this->startTest();

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::WEBSITE_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNull($redisKey);
    }

    public function testPeriodicCronNotLive()
    {
        $this->markTestSkipped();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant', [
            'hold_funds' => false,
            'activated'  => true,
            'activated_at' => now()->timestamp - 24*60*60,
        ]);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'business_website' => 'https://razorpay2.com'
        ]);

        $this->fixtures->create('payment', [
            'merchant_id' => $merchant->getId(),
        ]);

        $this->startTest();

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::WEBSITE_CHECKER);

        $redisKey = $this->app['cache']->connection()->hget($redisMap, $merchant->getId());

        $this->assertNotNull($redisKey);
    }

    public function testRetryCron()
    {
        $this->markTestSkipped();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant');

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::WEBSITE_CHECKER);

        $this->app['cache']->connection()->hset($redisMap, $merchant->getId(), now()->timestamp - Constants::RETRY_WAIT_SECONDS);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'business_website' => 'https://razorpay.com'
        ]);

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(RiskHealthChecker::class);
    }

    public function testRetryCronNotLive()
    {
        $this->markTestSkipped();

        $this->ba->cronAuth();

        $merchant = $this->fixtures->create('merchant');

        $redisMap = Constants::eventAndCheckerTypeRedisMap(Constants::PERIODIC_CHECKER_EVENT, Constants::WEBSITE_CHECKER);

        $this->app['cache']->connection()->hset($redisMap, $merchant->getId(), now()->timestamp - Constants::RETRY_WAIT_SECONDS);

        $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
            'business_website' => 'https://razorpay2.com'
        ]);

        // todo: mock ras call

        Queue::fake();

        $this->startTest();

        Queue::assertPushed(RiskHealthChecker::class);
    }

    public function testFreshdeskWebhook()
    {
        $this->markTestSkipped();

        $this->ba->freshdeskWebhookAuth();

        $merchant = $this->fixtures->create('merchant');

        /** @var WorkflowActionEntity $workflowAction */
        $workflowAction = $this->fixtures->create('workflow_action');
        $workflowAction->tag(sprintf(Constants::FD_TICKET_ID_TAG_FMT[Constants::WEBSITE_CHECKER], "test_fd_123"));

        $this->app['cache']->connection()->hset(Constants::REDIS_REMINDER_MAP_NAME[Constants::WEBSITE_CHECKER], $merchant->getId(), now()->timestamp - Constants::REMINDER_WAIT_SECONDS);

        $this->startTest();

        /** @var WorkflowActionEntity $workflowActionAfterRequest */
        $workflowActionAfterRequest = (new WorkflowActionRepository())->find($workflowAction->getId());

        $this->assertContains(ucfirst(Constants::MERCHANT_REPLIED_TAG), $workflowActionAfterRequest->tagNames());
    }
}
