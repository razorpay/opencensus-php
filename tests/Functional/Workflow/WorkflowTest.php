<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;

class WorkflowTest extends TestCase
{
    use WorkflowTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WorkflowTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateWorkflow()
    {
        $permissions = $this->getWorkflowPermissions('org_' . Org::RZP_ORG);

        $input = [
            'permissions' => array_slice($permissions, 0, 2),
        ];

        $expectedResponse = $this->createWorkflow($input);

        sd($expectedResponse);
    }
}