<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Org\Repository as OrgRepository;

class WorkflowTest extends TestCase
{
    use WorkflowTrait;
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WorkflowTestData.php';

        parent::setUp();

        // Using default razorpay org because superadmin,maker,checker
        // are set already in org setup.

        $this->org = (new OrgRepository)->getRazorpayOrg();

        $this->addWorkflowPermissionsToOrg($this->org);

        $this->ba->adminAuth();
    }

    public function testCreateWorkflow()
    {
        $permissions = $this->getWorkflowPermissions($this->org->getPublicId());

        $permissionIds = array_map(function ($permission)
        {
            return $permission['id'];
        }, $permissions['items']);

        $input = [
            'permissions' => array_slice($permissionIds, 0, 2),
        ];

        $response = $this->createWorkflow($input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }
}