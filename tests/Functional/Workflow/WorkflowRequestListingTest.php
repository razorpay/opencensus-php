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
        $this->ba->adminAuth('test', Org::CHECKER_TOKEN, 'org_' . Org::RZP_ORG);

        $this->startTest();
    }
}