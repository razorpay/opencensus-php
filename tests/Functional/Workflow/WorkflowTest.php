<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Tests\Functional\Fixtures\Entity\Workflow;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\Fixtures\Entity\Permission as PermissionEntity;

class WorkflowTest extends TestCase
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
}