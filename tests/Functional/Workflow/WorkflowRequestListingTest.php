<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;

class WorkflowRequestListingTest extends TestCase
{
    use WorkflowTrait;
    use HeimdallTrait;
    use RequestResponseFlowTrait;

    protected $input = [];
    protected $authToken = null;
    protected $org = null;
    protected $workflowPermissionIds = [];

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WorkflowRequestListingTestData.php';

        parent::setUp();

        // we have all the required roles in the default org setup.
        //workflow actions need to be created.
        $this->fixtures->workflow_action->setUp();
    }

    /**
     * Default workflow action will have checker as checker role.
     * making checker admin auth request and checking.
     */
    public function testWorkflowCheckerRequests()
    {
        $this->ba->adminAuth('test', Org::CHECKER_ADMIN_TOKEN, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }

    public function testWorkflowMakerRequests()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();

        $this->ba->adminAuth('test', Org::CHECKER_ADMIN_TOKEN, 'org_' . Org::RZP_ORG);

        $this->testData[__FUNCTION__]['response']['content'] = [
            "entity" => "collection",
            "count"  => 0,
            "items"  => [],
        ];

        $this->startTest();
    }


    public function testWorkflowClosedRequests()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $action = $this->fixtures->create('workflow_action', [
            'admin_id'      => Org::MAKER_ADMIN,
            'state'         => \RZP\Models\Workflow\Action\State\Entity::CLOSED,
        ]);

        $this->fixtures->create('action_state', [
            'action_id'     => $action->getId(),
            'admin_id'      => Org::SUPER_ADMIN,
            'name'         => \RZP\Models\Workflow\Action\State\Entity::CLOSED,
        ]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $action->getPublicId();

        $this->startTest();
    }

    /**
     * Workflow will be created on editadmin cause of default workflow
     */
    public function testAdminCheckedRequests()
    {
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::SUPER_ADMIN_SIGNED);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->approveWorkflowAction($workflow['id']);

        $this->startTest();
    }

    public function testWorkflowSuperAdminAllRequests()
    {
        $this->ba->adminAuth('test');

        $this->fixtures->create('workflow_action:closed_workflow_action');

        $this->startTest();
    }

    public function testWorkflowSuperAdminOpenRequests()
    {
        $this->ba->adminAuth('test');

        $this->fixtures->create('workflow_action:closed_workflow_action');

        $this->startTest();
    }
}
