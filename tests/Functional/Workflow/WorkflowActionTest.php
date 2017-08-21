<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Models\Admin\Permission as AdminPermission;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Role\Repository as RoleRepository;
use RZP\Tests\Functional\Fixtures\Entity\WorkflowAction;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class WorkflowActionTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WorkflowActionTestData.php';

        parent::setUp();

        $this->fixtures->workflow_action->setUp();

        $makerRole = (new RoleRepository())->findByIdAndOrgId(Org::MAKER_ROLE, Org::RZP_ORG);

        $permissions = (new AdminPermission\Repository)->retrieveIdsByNames([AdminPermission\Name::EDIT_ADMIN]);

        $makerRole->permissions()->attach($permissions);
    }

    /**
     * Here we will edit admin action from makerAdmin
     * and check weather the workflow is created.
     * already a default workflow for edit admin permission is created in fixtures.
     */
    public function testCreateWorkflowAction()
    {
        // Editing checker admin as maker.
        $this->ba->adminAuth('test', Org::MAKER_TOKEN, 'org_' . Org::RZP_ORG);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'org_' . Org::RZP_ORG, 'admin_' . Org::CHECKER_ADMIN);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->testData[__FUNCTION__]['response']['org_id'] = 'org_' . Org::RZP_ORG;

        $this->testData[__FUNCTION__]['response']['entity_id'] = Org::CHECKER_ADMIN;

        $this->startTest();
    }

    public function testCreateWorkflowActionInprogress()
    {
        $this->editAdmin('org_' . Org::RZP_ORG, 'admin_' . Org::CHECKER_ADMIN);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'org_' . Org::RZP_ORG, 'admin_' . Org::CHECKER_ADMIN);

        // Assign url
        $this->testData[__FUNCTION__]['request']['url'] = $url;

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

        $this->testData[__FUNCTION__]['response']['content']['entity_id'] = Org::MAKER_ADMIN;

        $this->testData[__FUNCTION__]['response']['content']['entity_name'] = 'admin';

        $this->testData[__FUNCTION__]['response']['content']['org_id'] = 'org_' . Org::RZP_ORG;

        $this->startTest();
    }

    /**
     * Updating default workflow action(wfActionId1000) with title and description
     */
    public function testUpdateWorkflowAction()
    {
        $defaultWorkflowActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;

        $this->setDefaultActionIdInUrl();

        $this->testData[__FUNCTION__]['response']['content']['org_id'] = 'org_' . Org::RZP_ORG;

        $this->testData[__FUNCTION__]['response']['content']['id'] = $defaultWorkflowActionId;

        $this->startTest();
    }

    /**
     * Test edit admin workflow action diff.
     *
     */
    public function testWorkflowActionDiff()
    {
        //adding in relations too just to check diff correctly.
        $content = [
            "name"  => "Checker checker",
            "roles" => [
                "role_" . Org::CHECKER_ROLE,
                "role_" . Org::MAKER_ROLE,
            ],
            "groups" => [
                "grp_" . Org::DEFAULT_GRP,
            ],
        ];

        // we have a default workflow for edit admin so this will trigger wf action.
        $workflowAction = $this->editAdmin('org_' . Org::RZP_ORG,
            'admin_' . Org::CHECKER_ADMIN,
            $content);

        //After Indexing into ES the document is not available in Real Time so a sec delay.
        sleep(1);

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

        $this->startTest();
    }

    public function testWorkflowActionApproveL1()
    {
        $defaultWorkflowActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;

        $this->setDefaultActionIdInUrl(Org::DEFAULT_TOKEN);

        $this->testData[__FUNCTION__]['response']['content']['checkers'][0]['admin_id'] = 'admin_' . Org::SUPER_ADMIN;

        $this->testData[__FUNCTION__]['response']['content']['checkers'][0]['action_id'] = $defaultWorkflowActionId;

        $this->startTest();
    }

    public function testWorkflowActionApproveDiffRole()
    {
        $this->setDefaultActionIdInUrl();

        $this->startTest();
    }

    private function setDefaultActionIdInUrl($adminToken = Org::MAKER_TOKEN)
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $functionName = $trace[1]['function'];

        $defaultWorkflowActionId = 'w_action_' . WorkflowAction::DEFAULT_WORKFLOW_ACTION_ID;

        $this->ba->adminAuth('test', $adminToken, 'org_' . Org::RZP_ORG);

        $url = $this->testData[$functionName]['request']['url'];

        $url = sprintf($url, $defaultWorkflowActionId);

        // Assign url
        $this->testData[$functionName]['request']['url'] = $url;
    }
}