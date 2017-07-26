<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Permission;

class WorkflowTest extends TestCase
{
    use WorkflowTrait;
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    protected $input = [];
    protected $authToken = null;
    protected $org = null;

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

        $workflowPermissions = $this->getPermissions('workflow');

        $this->workflowPermissionIds = $this->getPermissionsByIds('workflow');

        $this->input = [
            'org_id'      => $this->org->getId(),
            'permissions' => array_slice($workflowPermissions, 0, 2),
        ];
    }

    public function testCreateWorkflow()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $defaultAttributes = $this->getDefaultWorkflowArray();

        $attributes = array_merge($defaultAttributes, $this->input);

        $attributes['org_id'] = $this->org->getPublicId();

        $attributes['permissions'] = array_slice($this->workflowPermissionIds, 0, 2);

        $this->testData[__FUNCTION__]['request']['content'] = $attributes;

        $this->startTest();
    }

    public function testDeleteWorkflow()
    {
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

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
        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

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