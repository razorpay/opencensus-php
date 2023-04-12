<?php

namespace RZP\Tests\Functional\Workflow;

use Hash;
use Mockery;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use RZP\Http\RequestHeader;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Admin\Permission;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use Razorpay\Edge\Passport\Passport;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Workflow;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payout\PayoutTrait;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Fixtures\Entity\Permission as PermissionEntity;

class WorkflowTest extends TestCase
{
    use WorkflowTrait;
    use PayoutTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;
    use TestsBusinessBanking;

    protected $input = [];
    protected $authToken = null;
    protected $org = null;
    protected $workflowPermissionIds = [];
    private $ownerRoleUser;
    protected $merchant = null;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/WorkflowTestData.php';

        parent::setUp();
        // Using default razorpay org because superadmin,maker,checker
        // are set already in org setup.

        $this->org = $this->fixtures->create('org');

        $permissions = (new PermissionEntity)->getAllPermissions();

        $this->org->permissions()->attach($permissions);

        $this->addWorkflowPermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $workflowPermissions = $this->getPermissions('workflow');

        $this->workflowPermissionIds = $this->getPermissionsByIds('workflow');

        $this->input = [
            'org_id'      => $this->org->getId(),
            'permissions' => array_slice($workflowPermissions, 0, 2),
        ];
    }

    protected function setupOrgForLiveMode()
    {
        $this->org = $this->fixtures->on('live')->create('org');

        $permissions = (new PermissionEntity)->getAllPermissions();

        $this->org->permissions()->attach($permissions);

        $this->addWorkflowPermissionsToOrg($this->org);
    }

    public function testCreateWorkflow()
    {
        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($defaultAttributes, $this->input);

        $attributes['org_id'] = $this->org->getPublicId();

        $attributes['permissions'] = array_slice($this->workflowPermissionIds, 0, 2);

        $this->testData[__FUNCTION__]['request']['content'] = $attributes;

        $this->startTest();
    }

    public function testDeleteWorkflow()
    {
        $workflow = $this->createWorkflow($this->input);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $workflow->getPublicId());

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $content = $this->testData[__FUNCTION__]['response']['content'];

        $expectedResponse = [
            'id' => $workflow->getPublicId(),
            'name' => $workflow->getName(),
        ];

        $this->testData[__FUNCTION__]['response']['content'] = array_merge($expectedResponse, $content);

        $this->startTest();
    }

    /**
     * Test create workflow with permissions already have worklow.
     *
     */
    public function testCreateWorkflowWithPermissionWorkflow()
    {
        $this->createWorkflow($this->input);

        // To recreate the same workflow using request to test.
        $permissions = (new Permission\Repository)->retrieveIdsByNames($this->input['permissions']);

        $permissionIds = [];
        // Get public ids for the permissions.
        foreach ($permissions as $permission)
        {
            $permissionIds[] = $permission->getPublicId();
        }

        $data = $this->testData[__FUNCTION__]['request']['content'];

        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($defaultAttributes, $this->input);

        $attributes['org_id'] = $this->org->getPublicId();

        $attributes['permissions'] = $permissionIds;

        $this->testData[__FUNCTION__]['request']['content'] = array_merge($attributes, $data);

        $this->startTest();
    }

    /**
     * Delete workflow which is in progress.
     * Using default workflow which was created in entity
     *
     */
    public function testDeleteWorkflowProgress()
    {
        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        // Default workflow has edit admin permission and editing default org user.
        $this->editAdmin('org_' . Org::RZP_ORG, 'admin_' . Org::SUPER_ADMIN);

        $url = sprintf($url, 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testEditWorkflow()
    {
        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($defaultAttributes, $this->input);

        $attributes['org_id'] = $this->org->getPublicId();

        $attributes['permissions'] = array_slice($this->workflowPermissionIds, 0, 2);

        $attributes['name'] = 'just changing name';

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['request']['content'] = $attributes;

        $this->testData[__FUNCTION__]['response']['content']['name'] = $attributes['name'];

        $this->startTest();
    }

    /**
     * Testing Edit Workflow which is in progress.
     */
    public function testEditWorkflowInProgress()
    {
        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        // Default workflow has edit admin permission and editing default org user.
        $this->editAdmin('org_' . Org::RZP_ORG, 'admin_' . Org::SUPER_ADMIN);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID);

        $attributes['name'] = 'name change';

        $this->testData[__FUNCTION__]['request']['content'] = $attributes;

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetWorkflow()
    {
        $workflowId = 'workflow_' . Workflow::DEFAULT_WORKFLOW_ID;

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $workflowId);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['content']['id'] = $workflowId;

        $this->startTest();
    }

    public function testWorkflowGetMultiple()
    {
        $this->ba->adminAuth('test');

        $this->startTest();
    }

    public function testCreateWorkflowWithCreatePayoutPermissionWithoutMerchantId()
    {
        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($defaultAttributes, $this->input);

        $attributes['org_id'] = $this->org->getPublicId();

        $permissionId = DB::table('permissions')->where('name','=','create_payout')->value('id');
        $attributes['permissions'] = [
            'perm_'.$permissionId
        ];

        $this->testData[__FUNCTION__]['request']['content'] = $attributes;

        $this->startTest();
    }

    public function testWorkflowStateCallbackFromNWFS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'workflow_id'     => 'FSYpen1s24sSbs',
                'entity_id'       => 'Exag5ZpN5MWuBW',
                'entity_type'     => 'payout',
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        // Approve with Owner role user
        $this->ba->workflowsAppAuth('live');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/state/callback';

        $this->startTest();
    }

    public function testWorkflowStateCallbackFromPayouts()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'workflow_id'     => 'FSYpen1s24sSbs',
                'entity_id'       => 'Exag5ZpN5MWuBW',
                'entity_type'     => 'payout',
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        // Approve with Owner role user
        $this->ba->payoutInternalAppAuth('live');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/state/callback';

        $this->startTest();

        $workflowStateMap = $this->getDbLastEntity('workflow_state_map', 'live');

        $this->assertNotEmpty($workflowStateMap);
        $this->assertEquals('created',$workflowStateMap->getStatus());
    }

    public function testWorkflowStateUpdateCallbackFromPayouts()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'workflow_id'     => 'FSYpen1s24sSbs',
                'entity_id'       => 'Exag5ZpN5MWuBW',
                'entity_type'     => 'payout',
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $this->fixtures->on('live')->create(
            'workflow_state_map',
            [
                "workflow_id"       => "FSYpen1s24sSbs",
                "merchant_id"       => "10000000000000",
                "org_id"            => "100000razorpay",
                "actor_type_key"    => "role",
                "actor_type_value"  => "owner",
                "state_id"          => "FSYqHROoUij6TF",
                "state_name"        => "Owner_Approval",
                "status"            => "created",
                "group_name"        => "ABC",
                "type"              => "checker"
            ]);

        // Approve with Owner role user
        $this->ba->payoutInternalAppAuth('live');

        $this->startTest();

        $workflowStateMap = $this->getDbLastEntity('workflow_state_map', 'live');

        $this->assertNotEmpty($workflowStateMap);
        $this->assertEquals('processed',$workflowStateMap->getStatus());
    }

    // Forwarding call to PS which is mocked if payout service flag is enabled
    public function testWorkflowStateCallbackFromNWFSToPS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $balance = $this->getDbLastEntity('balance', 'live');

        $payoutData = [
            'id'                =>  'Exag5ZpN5MWuBW',
            'status'            => 'created',
            'balance_id'        =>  $balance->getId(),
            'merchant_id'       =>  '10000000000000',
            'amount'            =>  1,
            'created_at'        => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'        => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ];

        $payout = $this->fixtures->on('live')->create('payout' , $payoutData);

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'workflow_id'     => 'FSYpen1s24sSbs',
                'entity_id'       =>  $payout->getId(),
                'entity_type'     => 'payout',
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $success = false;

        $this->mockNonTerminalExperiment($success);

        $this -> mockPayoutServiceWorkflow('created', $success);

        $payout = $this->fixtures->on('live')->edit('payout' , $payout->getId(), [
            'is_payout_service' => 1,
        ]);

        // Approve with Owner role user
        $this->ba->workflowsAppAuth('live');

        $this->createPsPayout();

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/state/callback';

        $this->startTest();
    }


    // Forwarding call to PS which is mocked if payout service flag is enabled
    public function testWorkflowStateUpdateCallbackFromNWFSToPS()
    {
        $this->liveSetUp();

        $this->setUpExperimentForNWFS();

        $this->createPayoutWorkflowWithBankingUsersLiveMode();

        $balance = $this->getDbLastEntity('balance', 'live');

        $payoutData = [
            'id'                    =>  'Exag5ZpN5MWuBW',
            'status'                => 'created',
            'balance_id'            =>  $balance->getId(),
            'merchant_id'           =>  '10000000000000',
            'amount'                =>  1,
            'created_at'            => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
            'updated_at'            => Carbon::now(Timezone::IST)->subHours(2)->getTimestamp(),
        ];

        $payout = $this->fixtures->on('live')->create('payout' , $payoutData);

        $this->fixtures->on('live')->create(
            'workflow_entity_map',
            [
                'workflow_id'     => 'FSYpen1s24sSbs',
                'entity_id'       =>  $payout->getId(),
                'entity_type'     => 'payout',
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $this->fixtures->on('live')->create(
            'workflow_state_map',
            [
                "workflow_id"       => "FSYpen1s24sSbs",
                "merchant_id"       => "10000000000000",
                "org_id"            => "100000razorpay",
                "actor_type_key"    => "role",
                "actor_type_value"  => "owner",
                "state_id"          => "FSYqHROoUij6TF",
                "state_name"        => "Owner_Approval",
                "status"            => "created",
                "group_name"        => "ABC",
                "type"              => "checker"
            ]);

        $success = false;

        $this->mockNonTerminalExperiment($success);

        $this -> mockPayoutServiceWorkflow('processed', $success);

        $payout = $this->fixtures->on('live')->edit('payout' , $payout->getId(), [
            'is_payout_service' => 1,
        ]);

        // Approve with Owner role user
        $this->ba->workflowsAppAuth('live');

        $this->createPsPayout();

        $this->startTest();
    }

    public function testCreateWorkflowConfigWithAccountNumber()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');

        $this->assertEquals(true, $workflowConfig['enabled']);

        $this->assertEquals('FQE6Xw4ZpoM21X', $workflowConfig['config_id']);

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);
    }

    public function testCreateWorkflowConfig()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');

        $this->assertEquals(true, $workflowConfig['enabled']);

        $this->assertEquals('FQE6Xw4ZpoM21X', $workflowConfig['config_id']);

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);
    }

    public function testCreateWorkflowConfigViaInternalRoute()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id' => $user->getId(),
                                                             'product' => 'banking',
                                                             'role' => 'owner',
                                                         ]);

        $this->ba->capitalCardsAuth();

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');

        $this->assertEquals(true, $workflowConfig['enabled']);

        $this->assertEquals('FQE6Xw4ZpoM21X', $workflowConfig['config_id']);

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);
    }

    public function testCreateWorkflowConfigViaPrivateRoute()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id' => $user->getId(),
                                                             'product' => 'banking',
                                                             'role' => 'owner',
                                                         ]);

        $this->ba->privateAuth();

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);

        $this->startTest();

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);
    }

    public function testCreateWorkflowConfigViaInternalRouteWithFeatureAlreadyEnabled()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id' => $user->getId(),
                                                             'product' => 'banking',
                                                             'role' => 'owner',
                                                         ]);

        $this->ba->capitalCardsAuth();

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');

        $this->assertEquals(true, $workflowConfig['enabled']);

        $this->assertEquals('FQE6Xw4ZpoM21X', $workflowConfig['config_id']);

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);
    }

    public function testCreateICICIWorkflowConfigInAdminAuth()
    {
        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $this->ba->addAccountAuth('10000000000000');

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');

        $this->assertEquals(true, $workflowConfig['enabled']);

        $this->assertEquals('icici-payouts-approval', $workflowConfig['config_type']);

        $this->assertEquals('FQE6Xw4ZpoM21X', $workflowConfig['config_id']);

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);
    }

    public function testBulkCreateWorkflowConfigInAdminAuthWithFeatureAlreadyEnabled()
    {
        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $this->ba->addAccountAuth('10000000000000');

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->startTest();
    }

    public function testBulkCreateWorkflowConfigInAdminAuthWithInvalidInput()
    {
        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $this->ba->addAccountAuth('10000000000000');

        $this->startTest();
    }

    public function testUpdateWorkflowConfigWithAccountNumber()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->startTest();
    }

    public function testUpdateWorkflowConfigWithPendingPayoutLinks()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([
                                                                                    'entity' => 'collection',
                                                                                    'count' => 2,
                                                                                    'items' => [
                                                                                        [
                                                                                            'id' => 'poutlk_id1',
                                                                                            'amount' => '1000',
                                                                                        ],
                                                                                        [
                                                                                            'id' => 'poutlk_id2',
                                                                                            'amount' => '2000',
                                                                                        ]
                                                                                    ]
                                                                                ]);

        $this->app->instance('payout-links', $plMock);

        $this->expectExceptionMessage(ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUT_LINKS);

        $this->startTest();
    }

    public function testUpdateWorkflowConfig()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $this->startTest();
    }

    public function testUpdateICICIWorkflowConfigInAdminAuth()
    {
        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->adminAuth('test', $token);

        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $this->ba->addAccountAuth('10000000000000');

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');

        $this->assertEquals(true, $workflowConfig['enabled']);

        $this->assertEquals('icici-payouts-approval', $workflowConfig['config_type']);

        $this->assertEquals('FQE6Xw4ZpoM21X', $workflowConfig['config_id']);
    }

    public function testDeleteWorkflowConfigWithAccountNumber()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);

        $this->startTest();

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);
    }

    public function testDeleteWorkflowConfigWithPendingPayoutLinks()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([
                                                                                    'entity' => 'collection',
                                                                                    'count' => 2,
                                                                                    'items' => [
                                                                                        [
                                                                                            'id' => 'poutlk_id1',
                                                                                            'amount' => '1000',
                                                                                        ],
                                                                                        [
                                                                                            'id' => 'poutlk_id2',
                                                                                            'amount' => '2000',
                                                                                        ]
                                                                                    ]
                                                                                ]);

        $this->app->instance('payout-links', $plMock);

        $this->expectExceptionMessage(ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUT_LINKS);

        $this->startTest();
    }

    public function testDeleteWorkflowConfig()
    {
        $user = $this->fixtures->create('user');

        $this->fixtures->user->createUserMerchantMapping([
                                                             'merchant_id' => '10000000000000',
                                                             'user_id'     => $user->getId(),
                                                             'product'     => 'banking',
                                                             'role'        => 'owner',
                                                         ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::PAYOUT_WORKFLOWS]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->proxyAuth('rzp_test_10000000000000', $user->getId());

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(true, $isPayoutWorkflowFeatureEnabled);

        $this->startTest();

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);
    }

    public function testDeleteICICIWorkflowConfigInAdminAuth()
    {
        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPayoutLinksSummaryForMerchant')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->ba->adminAuth('test', $token);

        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $this->ba->addAccountAuth('10000000000000');

        $this->startTest();

        $isPayoutWorkflowFeatureEnabled = $this->fixtures->merchant->isFeatureEnabled([Feature\Constants::PAYOUT_WORKFLOWS]);

        $this->assertEquals(false, $isPayoutWorkflowFeatureEnabled);
    }

    public function testCreateWorkflowConfigNWFS()
    {
        $this->setUpExperimentForNWFS();

        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/configs/';

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPendingPayoutLinks')->andReturn([
                                                                         'entity' => 'collection',
                                                                         'count' => 0,
                                                                         'items' => []
                                                                     ]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');
        $this->assertEquals(true, $workflowConfig['enabled']);
    }

    public function testCreateWorkflowConfigWithPendingPayoutNWFS()
    {
        $this->setUpExperimentForNWFS();

        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/configs/';

        $payout = $this->fixtures->create('payout' , [
            'status'            =>      'pending',
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
        ]);

        $this->expectExceptionMessage(ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUTS);

        $this->startTest();

    }

    public function testCreateWorkflowConfigWithPendingPayoutLinks()
    {
        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPendingPayoutLinks')->andReturn([
                                                                         'entity' => 'collection',
                                                                         'count' => 2,
                                                                         'items' => [
                                                                             [
                                                                                 'id' => 'poutlk_id1',
                                                                                 'amount' => '1000',
                                                                             ],
                                                                             [
                                                                                 'id' => 'poutlk_id2',
                                                                                 'amount' => '2000',
                                                                             ]
                                                                         ]
                                                                     ]);

        $this->app->instance('payout-links', $plMock);

        $this->expectExceptionMessage(ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUT_LINKS);

        $this->startTest();
    }

    public function testUpdateWorkflowConfigNWFS()
    {
        $this->setUpExperimentForNWFS();

        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/configs/';

        $this->fixtures->on('test')->create(
            'workflow_config',
            [
                'id'              => 'FQfRKbJwE4aWbp',
                'config_id'       => 'FQE6Xw4ZpoM21X',
                'config_type'     => 'payout-approval',
                'enabled'         => true,
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('fetchPendingPayoutLinks')->andReturn(['count' => 0]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');
        $this->assertEquals(false, $workflowConfig['enabled']);
    }

    public function testUpdateWorkflowConfigWithPendingPayoutsNWFS()
    {
        $this->setUpExperimentForNWFS();

        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/configs/';

        $this->fixtures->on('test')->create(
            'workflow_config',
            [
                'id'              => 'FQfRKbJwE4aWbp',
                'config_id'       => 'FQE6Xw4ZpoM21X',
                'config_type'     => 'payout-approval',
                'enabled'         => true,
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $payout = $this->fixtures->create('payout' , [
            'status'            =>      'pending',
            'pricing_rule_id'   =>      '1nvp2XPMmaRLxb',
        ]);

        $this->expectExceptionMessage(ErrorCode::BAD_REQUEST_WORKFLOW_MERCHANT_WITH_PENDING_PAYOUTS);

        $this->startTest();

    }

    public function testGetWorkflowConfigWFSFromAdminDashWithPermission()
    {
        $this->setUpExperimentForNWFS();

        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $this->fixtures->on('test')->create(
            'workflow_config',
            [
                'id'              => 'FQfRKbJwE4aWbp',
                'config_id'       => 'FQE6Xw4ZpoM21X',
                'config_type'     => 'payout-approval',
                'enabled'         => false,
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $workflowConfig = $this->getDbLastEntity('workflow_config');

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service-admin/configs/' . $workflowConfig['config_id'];

        $this->startTest();
    }

    public function testGetWorkflowConfigWFSFromAdminDashWithoutPermission()
    {
        $this->setUpExperimentForNWFS();

        $admin = $this->prepareAdminForPayoutWorkflow('test');

        $role = $this->getDbLastEntity('role');

        $permission = $this->getDbEntities('permission', ['name' => 'wfs_config_create']);

        $role->permissions()->detach($permission[0]['id']);

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('test', $token);

        $this->fixtures->on('test')->create(
            'workflow_config',
            [
                'id'              => 'FQfRKbJwE4aWbp',
                'config_id'       => 'FQE6Xw4ZpoM21X',
                'config_type'     => 'payout-approval',
                'enabled'         => false,
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $workflowConfig = $this->getDbLastEntity('workflow_config');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service-admin/configs/' . $workflowConfig['config_id'];

        $this->startTest();

        $workflowConfig = $this->getDbLastEntity('workflow_config', 'test');
        $this->assertEquals(false, $workflowConfig['enabled']);
    }

    public function testGetWorkflowConfigWFSFromXDashboardWithPermission()
    {
        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->setUpExperimentForNWFS();

        $this->fixtures->on('test')->create(
            'workflow_config',
            [
                'id'              => 'FQfRKbJwE4aWbp',
                'config_id'       => 'FQE6Xw4ZpoM21X',
                'config_type'     => 'payout-approval',
                'enabled'         => false,
                'merchant_id'     => '10000000000000',
                'org_id'          => '100000razorpay',
            ]);

        $workflowConfig = $this->getDbLastEntity('workflow_config');

        $testData = & $this->testData[__FUNCTION__];
        $testData['request']['url'] = '/wf-service/configs/' . $workflowConfig['config_id'];

        $this->ba->proxyAuth();

        $this->startTest();
    }

    private function setUpExperimentForNWFS()
    {
        $this->mockRazorxTreatment(
            'yesbank',
            'off',
            'off',
            'off',
            'off',
            'on',
            'on',
            'off',
            'on',
            'on',
            'on' // just set this on, leave everything as default
        );
    }

    public function prepareAdminForPayoutWorkflow($mode)
    {
        $admin = $this->fixtures->on($mode)->create('admin', [
            'id' => 'poutRejtAdmnId',
            'org_id' => Org::RZP_ORG,
            'name' => 'Payout Rejecting Admin'
        ]);

        $role = $this->fixtures->on($mode)->create('role', [
            'id'     => 'wfsAdmin000001',
            'org_id' => '100000razorpay',
            'name'   => 'Workflow Service Admin',
        ]);

        $permission3 = $this->fixtures->on($mode)->create('permission',[
            'name'   => 'wfs_config_create'
        ]);

        $permission4 = $this->fixtures->on($mode)->create('permission',[
            'name'   => 'wfs_config_update'
        ]);

        $permission5 = $this->fixtures->on($mode)->create('permission',[
            'name'   => 'create_workflow'
        ]);

        $permission6 = $this->fixtures->on($mode)->create('permission',[
            'name'   => 'edit_workflow'
        ]);

        $permission7 = $this->fixtures->on($mode)->create('permission',[
            'name'   => 'self_serve_workflow_config'
        ]);

        $role->permissions()->attach($permission3->getId());
        $role->permissions()->attach($permission4->getId());
        $role->permissions()->attach($permission5->getId());
        $role->permissions()->attach($permission6->getId());
        $role->permissions()->attach($permission7->getId());

        $admin->roles()->attach($role);

        return $admin;
    }

    public function testWorkflowSyncFlowForConfigCreation()
    {
        $this->org = $this->getDbEntity('org', ['id' => '100000razorpay'], 'live');

        $this->input = [
            'org_id'      => $this->org->getId(),
            'permissions' => ['create_payout'],
        ];

        $workflowIds = [];

        $template = $this->testData['workflowSyncFlowForConfigCreation']['template_level_1'];
        $workflow1 = $this->createWorkflow($this->input,'live', $template);
        $workflowIds [] = $workflow1['id'];

        $template = $this->testData['workflowSyncFlowForConfigCreation']['template_level_2'];
        $workflow2 = $this->createWorkflow($this->input,'live',  $template);
        $workflowIds [] = $workflow2['id'];


        for ($index = 0; $index < 2; $index++) {
            $this->testData['createWorkflowPayoutAmountRules']['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$workflowIds[$index];
            $this->testData['createWorkflowPayoutAmountRules']['response']['content']['items'][$index]['workflow_id'] = $workflowIds[$index];
        }

        $this->setUpExperimentForNWFS();

        // Need to do this for test as well because in testing env,
        // admin authentication is done on test mode, even if live creds
        // have been passed.
        $adminForTest = $this->prepareAdminForPayoutWorkflow('test');
        $adminForLive = $this->prepareAdminForPayoutWorkflow('live');

        $this->app['config']->set('database.default', 'live');

        $adminToken = $this->fixtures->on('test')->create('admin_token', [
            'admin_id'   => $adminForTest->getId(),
            'token'      => Hash::make('ThisIsATokenForTest'),
        ]);

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $this->ba->adminAuth('live', $token);

        $testData = $this->testData['createWorkflowPayoutAmountRules'];

        DB::table('admins')->update(['allow_all_merchants' => 1]);
        $this->ba->addAccountAuth('10000000000000');

        $workflowServiceClientMock = Mockery::mock('RZP\Services\WorkflowService');

        $this->app->instance('workflow_service', $workflowServiceClientMock);

        $expectedConfig = $this->testData['workflowSyncFlowForConfigCreation']['expected_config'];

        $workflowServiceClientMock->shouldReceive('createConfig')->with($expectedConfig);

        $this->runRequestResponseFlow($testData);

        return $expectedConfig;
    }

    public function testWorkflowSyncFlowForConfigEdit()
    {
        $config = $this->testWorkflowSyncFlowForConfigCreation();

        $permission = $this->getDbEntity('permission', ['name' => 'create_payout'], 'live');

        DB::table('permission_map')->updateOrInsert(
            [
                'entity_id'         => Org::RZP_ORG,
                'entity_type'       => 'org',
                'permission_id'     => $permission->getId()
            ],
            [
                'entity_id'         => Org::RZP_ORG,
                'entity_type'       => 'org',
                'permission_id'     => $permission->getId(),
                'enable_workflow'   => 1
            ]);

        $makerRole = $this->fixtures->on('live')->create('role', [
            'id'     => 'RzpMakerRoleId',
            'org_id' => ORG::RZP_ORG,
            'name'   => 'Maker',
        ]);

        $this->testData[__FUNCTION__]['request']['content']['workflows'][0]['permissions'][0] = "perm_" . $permission->getId();
        $this->testData[__FUNCTION__]['request']['content']['workflows'][0]['levels'][0]['steps'][0]['role_id'] = "role_" . $makerRole->getId();

        $workflowServiceClientMock = Mockery::mock('RZP\Services\WorkflowService');

        $this->app->instance('workflow_service', $workflowServiceClientMock);

        $expectedConfig = $this->testData['workflowSyncFlowForConfigEdit']['expected_config'];

        $workflowServiceClientMock->shouldReceive('createConfig')->with($expectedConfig);

        $this->startTest();

        return $config;
    }

    /**
     * @return void
     */
    private function createPsPayout(): void
    {
        $payoutData = [
            'id'                   => "Exag5ZpN5MWuBW",
            'merchant_id'          => "10000000000000",
            'fund_account_id'      => "100000000000fa",
            'method'               => "fund_transfer",
            'reference_id'         => null,
            'balance_id'           => "KHTaUGgTXc0dhH",
            'user_id'              => "random_user123",
            'batch_id'             => null,
            'idempotency_key'      => "random_key",
            'purpose'              => "refund",
            'narration'            => "Batman",
            'purpose_type'         => "refund",
            'amount'               => 2000000,
            'currency'             => "INR",
            'notes'                => "{}",
            'fees'                 => 10,
            'tax'                  => 33,
            'status'               => "processed",
            'fts_transfer_id'      => 60,
            'transaction_id'       => "KHTaWqqBKwrVTM",
            'channel'              => "yesbank",
            'utr'                  => "933815383814",
            'failure_reason'       => null,
            'remarks'              => "Check the status by calling getStatus API.",
            'pricing_rule_id'      => "Bbg7cl6t6I3XA9",
            'scheduled_at'         => null,
            'queued_at'            => null,
            'mode'                 => "IMPS",
            'fee_type'             => "free_payout",
            'workflow_feature'     => null,
            'origin'               => 1,
            'status_code'          => null,
            'cancellation_user_id' => null,
            'registered_name'      => "SUSANTA BHUYAN",
            'queued_reason'        => "beneficiary_bank_down",
            'on_hold_at'           => 1663092113,
            'created_at'           => 1000000000,
            'updated_at'           => 1000000002,
        ];

        \DB::connection('test')->table('ps_payouts')->insert($payoutData);
    }

    public function mockPayoutServiceWorkflow($status,&$success, $request = [])
    {
        // Not mocking this method like mockPayoutServiceStatus because we need to assert for the request headers that
        // are going to be sent to payout service.
        $payoutServiceWorkflowMock = Mockery::mock('RZP\Services\PayoutService\Workflow',
                                                   [$this->app])->makePartial();

        $payoutServiceWorkflowMock->shouldReceive('sendRequest')
                                  ->withArgs(
                                      function($arg) use ($request, $status, &$success) {
                                          try
                                          {
                                              $this->assertNotEmpty($arg['headers'][Passport::PASSPORT_JWT_V1 ]);
                                              $this->assertNotEmpty($arg['headers'][RequestHeader::X_Creator_Id]);
                                              $this->assertNotEmpty($arg['headers'][RequestHeader::X_RAZORPAY_ACCOUNT]);

                                              $success =  true;

                                              return true;
                                          }
                                          catch (\Throwable $e)
                                          {
                                              $success =  false;

                                              return false;
                                          }
                                      }
                                  )
                                  ->andReturn(
                                      $this->createResponseForPayoutServiceWorkflowMock($status)
                                  );

        $this->app->instance(\RZP\Services\PayoutService\Workflow::PAYOUT_SERVICE_WORKFLOW, $payoutServiceWorkflowMock);
    }

    public function createResponseForPayoutServiceWorkflowMock($status)
    {
        $response = new \WpOrg\Requests\Response();

        $content = [
            "workflow_id"       => "FSYpen1s24sSbs",
            "merchant_id"       => "10000000000000",
            "org_id"            => "100000razorpay",
            "actor_type_key"    => "role",
            "actor_type_value"  => "owner",
            "state_id"          => "FSYqHROoUij6TF",
            "state_name"        => "Owner_Approval",
            "status"            => $status,
            "group_name"        => "ABC",
            "type"              => "checker"
        ];

        $response->body = json_encode($content);
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }

    public function mockNonTerminalExperiment(&$success)
    {

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                              function ($mid, $feature, $mode,&$success)
                              {
                                  if ($feature === RazorxTreatment::NON_TERMINAL_MIGRATION_HANDLING)
                                  {
                                      $success = true;
                                      return 'on';
                                  }
                                  else
                                  {
                                      $success = false;
                                      return 'off';
                                  }

                              }) );
    }
}
