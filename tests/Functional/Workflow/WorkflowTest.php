<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;

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

        $this->org = $this->fixtures->create('org');

        $this->addWorkflowPermissionsToOrg($this->org);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->ba->setOrganisation($this->org->getPublicId());

        $workflowPermissionIds = $this->getPermissionsByIds('workflow');

        $this->input = [
            'org_id'      => $this->org->getPublicId(),
            'permissions' => array_slice($workflowPermissionIds, 0, 2),
        ];
    }

    public function testCreateWorkflow()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($defaultAttributes, $this->input);

        $this->testData[__FUNCTION__]['request']['content'] = $attributes;

        $this->startTest();
    }

    public function testDeleteWorkflow()
    {
        $workflow = $this->createWorkflow($this->input);

        $response = $this->deleteWorkflow($workflow['id'], $this->org->getPublicId());

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    /**
     * Test create workflow with permissions already have worklow.
     *
     */
    public function testCreateWorkflowWithPermissionWorkflow()
    {
        $workflow = $this->createWorkflow($this->input);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() {
            $this->createWorkflow($this->input);
        });
    }

    /**
     * Delete workflow which is in progress.
     * will create workflow for edit admin and tests to delete it.
     *
     */
    public function testDeleteWorkflowProgress()
    {
        $workflow = $this->createEditAdminWorkflow();

        $this->editAdmin($this->org->getPublicId(), 'admin_' . Org::SUPER_ADMIN);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use($workflow)
        {
            $this->deleteWorkflow($workflow['id'], $this->org->getPublicId());
        });
    }

    public function testEditWorkflow()
    {
        $workflow = $this->createWorkflow($this->input);

        $input = array_merge($this->input, ['name' => 'editing workflow']);

        $response = $this->editWorkflow($workflow['id'], $this->org->getPublicId(), $input);

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function testEditWorkflowInProgress()
    {
        $workflow = $this->createEditAdminWorkflow();

        $this->editAdmin($this->org->getPublicId(), 'admin_' . Org::SUPER_ADMIN);

        $input = array_merge($this->input, ['name' => 'editing workflow']);

        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function() use($workflow, $input)
        {
            $response = $this->editWorkflow($workflow['id'], $this->org->getPublicId(), $input);
        });
    }

    public function testGetWorkflow()
    {
        $workflow = $this->createWorkflow($this->input);

        $response = $this->getWorkflow($workflow['id'], $this->org->getPublicId());

        $expectedResponse = $this->testData[__FUNCTION__];

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }
}