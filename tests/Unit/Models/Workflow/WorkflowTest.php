<?php

namespace Tests\Unit\Models\Workflow;

use Mockery;
use Tests\Unit\TestCase;
use RZP\Exception\BadRequestException;

use RZP\Models\Payout\Service as PayoutService;

class WorkflowTest extends TestCase
{
    protected $org;

    protected $payoutService;

    protected $payoutCore;

    protected $workflowService;

    protected $contactCore;

    protected $fundAccountService;

    protected $testData = array();

    protected $merchantRepoMock;

    protected $workflowRepoMock;

    protected $workflowConfigService;

    protected $permissionRepoMock;

    protected $workflowMigration;

    protected $merchantEntityMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['rzp.mode'] = 'live';

        $this->basicAuthMock->shouldReceive('getUser')->andReturn([]);

        $this->basicAuthMock->shouldReceive('getDevice')->andReturn([]);

        $this->basicAuthMock->shouldReceive('getProduct')->andReturn('banking');

        //wip, can be plugged out and run only once for all the tests.
        $this->createTestDependencyMocks();

        $this->payoutService = new PayoutService();

        $this->setPrivateProperty($this->payoutService, 'core', $this->payoutCore);

        $this->setPrivateProperty($this->payoutService, 'workflowConfigService', $this->workflowConfigService);

        $this->setPrivateProperty($this->payoutService, 'workflowMigration', $this->workflowMigration);

        $this->setPrivateProperty($this->payoutService, 'contactCore', $this->contactCore);

        $this->testData = require(__DIR__ . '/WorkflowTestData.php');
    }

    public function testMigrationRouteForSimpleWorkflow()
    {
        $amountRules = $this->testData[__FUNCTION__]['amount_rules'];

        $obj = $this->testData[__FUNCTION__]['new_config'];

        $this->workflowRepoMock->shouldReceive('fetchBankingWorkflowSummaryForPermissionId')->andReturn($amountRules);

        $collection = collect(['1', '2', '3']);

        $this->permissionRepoMock->shouldReceive('retrieveIdsByNamesAndOrg')->andReturn($collection);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $this->merchantEntityMock->shouldReceive('isCACEnabled')->andReturn(false);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn($this->merchantEntityMock);

        $this->workflowConfigService->shouldReceive('create')->with($obj)->andReturn(['id' => '123456']);

        $response = $this->payoutService->migrateOldConfigToNewOnes(['merchant_ids' => ['10000000000000']]);

        $this->assertSame(['failed' => [], 'success' => ['10000000000000 - 123456']], $response);

    }

    public function testMigrationRouteForComplexWorkflow()
    {
        $amountRules = $this->testData[__FUNCTION__]['amount_rules'];

        $obj = $this->testData[__FUNCTION__]['new_config'];

        $this->workflowRepoMock->shouldReceive('fetchBankingWorkflowSummaryForPermissionId')->andReturn($amountRules);

        $collection = collect(['1', '2', '3']);

        $this->permissionRepoMock->shouldReceive('retrieveIdsByNamesAndOrg')->andReturn($collection);

        $this->merchantEntityMock->shouldReceive('getId')->andReturn('10000000000000');

        $this->merchantEntityMock->shouldReceive('isCACEnabled')->andReturn(false);

        $this->merchantRepoMock->shouldReceive('findOrFailPublic')->andReturn($this->merchantEntityMock);

        $this->workflowConfigService->shouldReceive('create')->with($obj)->andReturn(['id' => '123456']);

        $response = $this->payoutService->migrateOldConfigToNewOnes(['merchant_ids' => ['10000000000000']]);

        $this->assertSame(['failed' => [], 'success' => ['10000000000000 - 123456']], $response);
    }

    public function createTestDependencyMocks()
    {
        // Core Mocking Partial
        $this->payoutCore = Mockery::mock('RZP\Models\Payout\Core')->makePartial()->shouldAllowMockingProtectedMethods();

        // Contact Core Mocking
        $this->contactCore = Mockery::mock('RZP\Models\Contact\Core');

        // RepositoryManager Mocking
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();

        // Merchant Repo mocking
        $this->merchantRepoMock = Mockery::mock('RZP\Models\Merchant\Repository');

        $this->merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');

        $this->org = Mockery::mock('RZP\Models\Admin\Org\Entity');

        $this->merchantEntityMock->shouldReceive('getAttribute')->andReturn($this->org);

        $this->org->shouldReceive('getPublicId')->andReturn('100000razorpay');

        $this->org->shouldReceive('getType')->andReturn(NULL);

        $this->workflowRepoMock = Mockery::mock('RZP\Models\Workflow\PayoutAmountRules\Repository');

        $this->permissionRepoMock = Mockery::mock('RZP\Models\Admin\Permission\Repository');

        $this->repoMock->shouldReceive('transactionOnLiveAndTest')->andReturn([]);

        $this->app->instance('repo', $this->repoMock);

        $this->repoMock->shouldReceive('driver')->with('merchant')->andReturn($this->merchantRepoMock);

        $this->repoMock->shouldReceive('driver')->with('permission')->andReturn($this->permissionRepoMock);

        $this->repoMock->shouldReceive('driver')->with('workflow_payout_amount_rules')->andReturn($this->workflowRepoMock);

        $this->fundAccountService = Mockery::mock('RZP\Models\FundAccount\Service')->makePartial();

        $this->workflowConfigService = Mockery::mock('RZP\Models\Workflow\Service\Config\Service');

        $this->workflowMigration = Mockery::mock('RZP\Models\Payout\WorkflowMigration');

        $this->setPrivateProperty($this->workflowMigration, 'payoutCore', $this->payoutCore);
    }
}
