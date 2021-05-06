<?php

namespace RZP\Tests\Functional\Workflow;

use Hash;

use Illuminate\Support\Facades\DB;

use RZP\Error\ErrorCode;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
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
//        $this->markTestSkipped();
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

        $role->permissions()->attach($permission3->getId());
        $role->permissions()->attach($permission4->getId());

        $admin->roles()->attach($role);

        return $admin;
    }
}
