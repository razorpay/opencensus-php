<?php

namespace RZP\Tests\Functional\Workflow;

use DB;
use Hash;
use Config;

use RZP\Models\Base\EsDao;
use RZP\Models\Workflow\Action;
use RZP\Models\Merchant\Account;
use Rzp\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use Rzp\Models\Admin\Admin\Token;
use RZP\Models\Workflow\Constants;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Admin\Permission as AdminPermission;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Models\Admin\Role\Repository as RoleRepository;
use RZP\Tests\Functional\Fixtures\Entity\WorkflowAction;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class WorkflowActionTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use EntityActionTrait;
    use HeimdallTrait;
    use WorkflowTrait;

    protected $esClient;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/WorkflowActionTestData.php';

        parent::setUp();

        $this->esClient = (new EsDao)->getEsClient()->getClient();

        $this->fixtures->workflow_action->setUp();

        $makerRole = (new RoleRepository())->findByIdAndOrgId(Org::MAKER_ROLE, Org::RZP_ORG);
        $checkerRole = (new RoleRepository())->findByIdAndOrgId(Org::CHECKER_ROLE, Org::RZP_ORG);

        $permissions = (new AdminPermission\Repository)->retrieveIdsByNames([AdminPermission\Name::EDIT_ADMIN]);

        $makerRole->permissions()->attach($permissions);

        $permissions = (new AdminPermission\Repository)->retrieveIdsByNames([AdminPermission\Name::VIEW_WORKFLOW_REQUESTS]);
        $makerRole->permissions()->attach($permissions);
        $checkerRole->permissions()->attach($permissions);
    }

    /**
     * Here we will edit admin action from makerAdmin
     * and check weather the workflow is created.
     * already a default workflow for edit admin permission is created in fixtures.
     */
    public function testCreateWorkflowAction()
    {
        // Editing checker admin as maker.
        $this->ba->adminAuth('test', Org::MAKER_ADMIN_TOKEN, Org::RZP_ORG_SIGNED);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, Org::CHECKER_ADMIN_SIGNED);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['org_id'] = Org::RZP_ORG_SIGNED;

        $this->testData[__FUNCTION__]['response']['entity_id'] = Org::CHECKER_ADMIN;

        $this->startTest();
    }

    public function testCreateWorkflowActionInprogress()
    {
        $actionId = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::CHECKER_ADMIN_SIGNED)['id'];

        $actionId = Action\Entity::verifyIdAndStripSign($actionId);

        $errorDescription = $this->testData[__FUNCTION__]['response']['content']['error']['description'];

        $errorDescription = sprintf($errorDescription, $actionId);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, Org::CHECKER_ADMIN_SIGNED);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = $errorDescription;

        $this->startTest();
    }

    /**
     * We will test the get workflow for our default workflow action.
     * Default workflow action is created in fixtures with Id wfActionId1000
     * Refer WorkflowTestAction Entity for more details on the response asserts.
     */
    public function testGetWorkflowActionDetails()
    {
        $this->setDefaultActionIdInUrl();

        $this->addPermissionToBaAdmin(AdminPermission\Name::VIEW_ALL_WORKFLOW);

        $this->testData[__FUNCTION__]['response']['content']['entity_id'] = Org::MAKER_ADMIN;

        $this->testData[__FUNCTION__]['response']['content']['entity_name'] = 'admin';

        $this->testData[__FUNCTION__]['response']['content']['org_id'] = Org::RZP_ORG_SIGNED;

        $this->startTest();
    }

    public function testGetActionsByMakerInternal()
    {
        $this->ba->careAppAuth();

        $this->startTest();
    }

    /**
     * Updating default workflow action(wfActionId1000) with title and description
     */
    public function testUpdateWorkflowAction()
    {
        $defaultWorkflowActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;

        $this->setDefaultActionIdInUrl();

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->testData[__FUNCTION__]['response']['content']['org_id'] = Org::RZP_ORG_SIGNED;

        $this->testData[__FUNCTION__]['response']['content']['id'] = $defaultWorkflowActionId;

        $this->startTest();
    }

    public function testUpdateWorkflowActionWithTags()
    {
        $defaultWorkflowActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;

        $this->setDefaultActionIdInUrl();

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->testData[__FUNCTION__]['request']['content']['workflow_tags'] = ['syncinstruments'];

        $this->testData[__FUNCTION__]['response']['content']['org_id'] = Org::RZP_ORG_SIGNED;

        $this->testData[__FUNCTION__]['response']['content']['id'] = $defaultWorkflowActionId;

        $this->startTest();

        $action = $this->fixtures->edit('workflow_action', WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID);

        $action->refresh();

        $tags = $action->tagNames();

        $this->assertEquals('syncinstruments', $tags['0']);
    }

    /**
     * Test edit admin workflow action diff.
     *
     */
    public function testWorkflowActionDiff()
    {
        // adding in relations too just to check diff correctly.
        $content = [
            "name"  => "Checker checker",
            "roles" => [
                Org::CHECKER_ROLE_SIGNED,
                Org::MAKER_ROLE_SIGNED,
            ],
            "groups" => [
                Org::DEFAULT_GRP_SIGNED,
            ],
        ];

        // we have a default workflow for edit admin so this will trigger wf action.
        $workflowAction = $this->editAdmin(
            Org::RZP_ORG_SIGNED,
            Org::CHECKER_ADMIN_SIGNED,
            $content);

        //After Indexing into ES the document is not available in Real Time so a sec delay.
        $this->esClient->indices()->refresh();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $workflowAction['id']);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['content'] = [
            "old" => [
                "name"   => "test admin",
                "roles"  => [],
                "groups" => [],
            ],
            "new" => [
                "name"  => "Checker checker",
                "roles" => [
                    [
                        "name" => "Maker"
                    ]
                ],
                "groups" => [
                    [
                        "name"        => "razorpay_group",
                        "description" => "This is a test group"
                    ],
                ],
            ],
        ];

        $this->addPermissionToBaAdmin(AdminPermission\Name::VIEW_ALL_WORKFLOW);

        $this->startTest();
    }

    public function testWorkflowActionApproveL1()
    {
        $defaultWorkflowActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;

        $this->setDefaultActionIdInUrl(Org::CHECKER_ADMIN_TOKEN);

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->testData[__FUNCTION__]['response']['content']['checkers'][0]['admin_id'] = Org::CHECKER_ADMIN_SIGNED;

        $this->testData[__FUNCTION__]['response']['content']['checkers'][0]['action_id'] = $defaultWorkflowActionId;

        $this->startTest();
    }

    public function testWorkflowActionApprovedWithComments()
    {
        $this->setDefaultActionIdInUrl(Org::CHECKER_ADMIN_TOKEN);

        $permissionId = (new AdminPermission\Repository)
                    ->retrieveIdsByNames([AdminPermission\Name::EDIT_ACTIVATE_MERCHANT])[0]->getId();

        $action = $this->fixtures->edit('workflow_action', WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID, ['permission_id' => $permissionId]);

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->startTest();

        $action->refresh();

        $tags = $action->tagNames();

        $this->assertEquals('Approved_with_feedback', $tags['0']);
    }

    public function testWorkflowClosedActionApproveOrRejectShouldFail()
    {
        $defaultWorkflowClosedActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_CLOSED_ACTION_ID;

        $this->fixtures->create('workflow_action:closed_workflow_action');

        $url = sprintf(
            $this->testData[__FUNCTION__]['request']['url'],
            $defaultWorkflowClosedActionId);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test');

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->startTest();
    }

    public function testWorkflowActionApproveDiffRole()
    {
        $this->setDefaultActionIdInUrl();

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->startTest();
    }

    private function setDefaultActionIdInUrl($adminToken = Org::MAKER_ADMIN_TOKEN)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $functionName = $trace[1]['function'];

        $defaultWorkflowActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;

        $this->ba->adminAuth('test', $adminToken, Org::RZP_ORG_SIGNED);

        $url = $this->testData[$functionName]['request']['url'];

        $url = sprintf($url, $defaultWorkflowActionId);

        // Assign url
        $this->testData[$functionName]['request']['url'] = $url;
    }

    public function testWorkflowActionRejection()
    {
        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->editAdmin('org_' . Org::RZP_ORG, Org::SUPER_ADMIN_SIGNED);

        //ES is not so Real Time, so need to refresh manually.
        $this->esClient->indices()->refresh();

        $this->ba->adminAuth('test', Org::CHECKER_ADMIN_TOKEN, Org::RZP_ORG_SIGNED);

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $workflow['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testWorkflowActionExecuteLastApproval()
    {
        $this->addPermissionEditActionToAdmins();

        $org = (new OrgRepository)->getRazorpayOrg();
        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $this->createWorkflow([
            'org_id'      => '100000razorpay',
            'name'        => 'some workflow',
            'permissions' => ['edit_admin'],
        ], 'live');

        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::SUPER_ADMIN_SIGNED, ['name' => 'akshay'], 'live');

        //ES is not so Real Time, so need to refresh manually.
        $this->esClient->indices()->refresh();

        $this->approveWorkflowAction($workflow['id'], 'live');

        $this->ba->adminAuth('live', Org::MAKER_ADMIN_TOKEN, Org::RZP_ORG_SIGNED);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $workflow['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $admin = $this->getAdmin(Org::RZP_ORG_SIGNED, Org::SUPER_ADMIN_SIGNED, null, 'live');

        $this->assertEquals('akshay', $admin['name']);
    }

    public function testWorkflowActionExecuteLastApprovalForCredits()
    {
        $this->addPermissionEditActionToAdmins();

        $permission = $this->getDbEntity('permission', ['name' => 'add_merchant_credits'], 'live');

        $balance = $this->getDbEntities('balance', ['merchant_id' => Account::TEST_ACCOUNT], 'live')->first();

        $this->assertEquals(0, $balance['credits']);

        $org = (new OrgRepository)->getRazorpayOrg();
        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $this->createWorkflow([
            'org_id'      => '100000razorpay',
            'name'        => 'some workflow',
            'permissions' => ['add_merchant_credits'],
        ], 'live');


        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->addCredits([], Account::TEST_ACCOUNT, 'live');

        //ES is not so Real Time, so need to refresh manually.
        $this->esClient->indices()->refresh();

        $this->approveWorkflowAction($workflow['id'], 'live');

        $this->ba->adminAuth('live', Org::MAKER_ADMIN_TOKEN, Org::RZP_ORG_SIGNED);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $workflow['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $balance = $this->getDbEntities('balance', ['merchant_id' => Account::TEST_ACCOUNT], 'live')->first()->toArray();

        $creditsLog = $this->getDbLastEntity('credits', 'live')->toArray();

        $this->assertEquals(25, $balance['credits']);

        $this->assertEquals($creditsLog['value'], 25);
    }

    protected function addPermissionEditActionToAdmins(): void
    {
        $perm = $this->fixtures->create('permission', ['name' => 'edit_action']);

        $admin = $this->ba->getAdmin(Org::CHECKER_ADMIN_TOKEN);

        $role = $admin->roles()->get()[0];

        $permId = $perm->getId();

        $perms = [Permission\Entity::verifyIdAndSilentlyStripSign($permId)];

        $unsignedOldPerms = $perms;

        $role->permissions()->sync($unsignedOldPerms);

        $admin = (new Token\Repository)->findOrFailToken(Org::MAKER_ADMIN_TOKEN)->admin;

        $role = $admin->roles()->get()[0];

        $permId = $perm->getId();

        $perms = [Permission\Entity::verifyIdAndSilentlyStripSign($permId)];

        $unsignedOldPerms = $perms;

        $role->permissions()->sync($unsignedOldPerms);
    }

    public function testWorkflowActionSuperAdminApprove()
    {
      $org = (new OrgRepository)->getRazorpayOrg();
        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $this->createWorkflow([
            'org_id'      => '100000razorpay',
            'name'        => 'some workflow',
            'permissions' => ['edit_admin'],
        ], 'live');

        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::CHECKER_ADMIN_SIGNED, [], 'live');

        //ES is not so Real Time, so need to refresh manually.
        $this->esClient->indices()->refresh();

        $this->ba->adminAuth('live', Org::DEFAULT_ADMIN_TOKEN, Org::RZP_ORG_SIGNED);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $workflow['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->startTest();
    }

    public function testWorkflowCloseAction()
    {
        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::CHECKER_ADMIN_SIGNED);

        $this->esClient->indices()->refresh();

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $workflow['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testWorkflowCloseActionWhenEsSyncFails()
    {
        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::CHECKER_ADMIN_SIGNED);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $workflow['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->startTest();
    }

    public function testWorkflowCanOnlyBeClosedByMaker()
    {
        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::CHECKER_ADMIN_SIGNED);

        $this->esClient->indices()->refresh();

        $this->app['workflow']->setWorkflowMaker(null);

        // Try to close as a different user
        $this->ba->adminAuth('test', Org::MAKER_ADMIN_TOKEN, Org::RZP_ORG_SIGNED);

        $url = sprintf($this->testData[__FUNCTION__]['request']['url'], $workflow['id']);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->addPermissionToBaAdmin(AdminPermission\Name::EDIT_ACTION);

        $this->startTest();
    }

    protected function createWorkflowActionForRiskAttributes($permission)
    {
        $content = [];
        // This will create a wf action in Mysql and ES, not using default workflow.
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::CHECKER_ADMIN_SIGNED,$content);

        $this->esClient->indices()->refresh();

        $this->addPermissionToBaAdmin($permission);

        return $workflow;
    }

    private function getWorkflowsForRiskAudit()
    {
        $workflows = [];

        for ($i = 0; $i < 5; $i++)
        {
            $workflowIds = Constants::getRiskAuditWorkflowIds();

            $workflow = $this->fixtures->create('workflow', ['id' => $workflowIds[$i]]);

            array_push($workflows, $workflow);
        }

        return $workflows;
    }

    private function createActionsForRiskWorkflows($workflows, $merchantId)
    {
        $workflowActions = [];

        $permission = $this->fixtures->create('permission', [
            'name' => Permission\Name::VIEW_ALL_WORKFLOW
        ]);

        foreach ($workflows as $workflow)
        {
            $workflowAction = $this->fixtures->create('workflow_action', [
                'entity_id'     => $merchantId,
                'entity_name'   => 'merchant',
                'approved'      => 1,
                'state'         => 'executed',
                'workflow_id'   => $workflow->getId(),
            ]);

            array_push($workflowActions, $workflowAction);
        }

        return $workflowActions;
    }

    private function createActionStatesForWfAction($workflowActions)
    {
        $actionStates = [];

        $admin = $this->fixtures->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        foreach ($workflowActions as $workflowAction)
        {
            $actionState = $this->fixtures->create('action_state', [
                  'action_id'   => $workflowAction->getId(),
                  'entity_id'   => $workflowAction->getId(),
                  'entity_type' => 'workflow_action',
                  'admin_id'    => $admin->getId(),
                  'name'        => 'approved'
              ]);

            array_push($actionStates, $actionState);
        }
        return $actionStates;
    }

    private function createTestDataForRiskAudit()
    {
        $this->ba->adminAuth();

        $merchantId = '10000000000000';

        $workflows = $this->getWorkflowsForRiskAudit();

        $workflowActions = $this->createActionsForRiskWorkflows($workflows, $merchantId);

        $workflowActionIds = array_map(
            function ($action)
            {
                $actionId = $action->getId();
                return sprintf('w_action_%s', $actionId);
            },
            $workflowActions
        );

        return $workflowActionIds;
    }

    public function testGetActionsForRiskAudit()
    {
        $workflowActionIds = $this->createTestDataForRiskAudit();

        $response = $this->startTest();

        $this->assertEquals(sizeof($response[Constants::WORKFLOW_ACTION_IDS]), 5);

        $this->assertEquals($response[Constants::WORKFLOW_ACTION_IDS], $workflowActionIds);
    }

    public function testGetActionsForRiskAuditWithTimeWithinRange()
    {
        $workflowActionIds = $this->createTestDataForRiskAudit();

        $response = $this->startTest();

        $this->assertEquals(sizeof($response[Constants::WORKFLOW_ACTION_IDS]), 5);

        $this->assertEquals($response[Constants::WORKFLOW_ACTION_IDS], $workflowActionIds);
    }

    public function testGetActionsForRiskAuditWithTimeOutsideRange()
    {
        $this->createTestDataForRiskAudit();

        $response = $this->startTest();

        $this->assertEquals(sizeof($response[Constants::WORKFLOW_ACTION_IDS]), 0);
    }
}
