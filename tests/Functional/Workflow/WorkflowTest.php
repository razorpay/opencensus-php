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

    protected $input = [];

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/WorkflowTestData.php';

        parent::setUp();

        // Using default razorpay org because superadmin,maker,checker
        // are set already in org setup.

        $this->org = (new OrgRepository)->getRazorpayOrg();

        $this->addWorkflowPermissionsToOrg($this->org);

        $this->ba->adminAuth('test', null, $this->org->getPublicId());

        $permissions = $this->getWorkflowPermissions($this->org->getPublicId());

        $permissionIds = array_map(function ($permission)
        {
            return $permission['id'];
        }, $permissions['items']);

        $this->input = [
            'permissions' => array_slice($permissionIds, 0, 2),
        ];

    }

    public function testCreateWorkflow()
    {
        $response = $this->createWorkflow($this->input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testDeleteWorkflow()
    {
        $workflow = $this->createWorkflow($this->input);

        $response = $this->deleteWorkflow($workflow['id'], $this->org->getPublicId());

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }
}