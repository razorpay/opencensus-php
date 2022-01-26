<?php

namespace RZP\Tests\Functional\Risk;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Admin\Permission\Name as PermissionName;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use Mockery;

class ShieldTest extends TestCase
{
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testShieldCreateRuleWorkflow()
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => PermissionName::CREATE_SHIELD_RULE]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        //setting up the workflow
        $this->setupWorkflow('edit_merchant_international', PermissionName::CREATE_SHIELD_RULE, "test");

        //creating the workflowAction using route "risk-actions/create"
        $request = [
             'content' => [
                   'Expression'  => "[contact] == '9999999999'",
                   'IsActive'    => true,
                   'ruleset'     => 'ruleset1',
                   'action'      => 'review',
                   'type'        => 'action',
                   'merchant_id' => '10000orgRazropay',
                   'description' => "Test Description",
             ],
             'url'     => '/shield/merchants/10000000000000/rules',
             'method'  => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        //approving the workflowAction created in previous step
        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    public function testShieldCreateThresholdWorkflow()
    {
        //setting up the workflow
        $this->initialSetupForShieldWorkflow(PermissionName::CREATE_MERCHANT_RISK_THRESHOLD);

        //creating the workflowAction using route "risk-actions/create"
        $request = [
            'content' => [
                "threshold"   => 50,
                "vendor"      => "sift",
                "merchant_id" => "HssAuxDaJqME9J",
            ],
            'url'     => '/shield/merchant/risk/thresholds',
            'method'  => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    public function testShieldUpdateThresholdWorkflow()
    {
        //setting up the workflow
        $this->initialSetupForShieldWorkflow(PermissionName::UPDATE_MERCHANT_RISK_THRESHOLD);

        $request = [
            'content' => [
                "threshold"   => 50,
                "vendor"      => "sift",
                "merchant_id" => "HssAuxDaJqME9J",
            ],
            'url'     => '/shield/merchant/risk/thresholds/47',
            'method'  => 'put',
        ];

        $this->mockShieldRequest();

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    public function testShieldDeleteThresholdWorkflow()
    {
        //setting up the workflow
        $this->initialSetupForShieldWorkflow(PermissionName::DELETE_MERCHANT_RISK_THRESHOLD);

        $request = [
            'url'     => '/shield/merchant/risk/thresholds/47',
            'method'  => 'delete',
        ];

        $this->mockShieldRequest();

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    public function testShieldBulkThresholdUpdateWorkflow()
    {
        //setting up the workflow
        $this->initialSetupForShieldWorkflow(PermissionName::BULK_UPDATE_MERCHANT_RISK_THRESHOLD);

        $request = [
            'content' => [
                "operation"    => "upsert",
                "threshold"    => 20,
                "merchant_ids" => ["merchantId1", "merchantId2"],
                "vendor"       => "sift"
            ],
            'url'     => '/shield/merchant/risk/threshold/bulk',
            'method'  => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    public function testShieldCreateThresholdConfigWorkflow()
    {
        //setting up the workflow
        $this->initialSetupForShieldWorkflow(PermissionName::CREATE_RISK_THRESHOLD_CONFIG);

        //creating the workflowAction using route "risk-actions/create"
        $request = [
            'content' => [
                "threshold"      => 50,
                "vendor"         => "sift",
                "category"       => "merchant_category",
                "category_value" => "it_and_software",
            ],
            'url'     => '/shield/risk/threshold/configs',
            'method'  => 'post',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    public function testShieldUpdateThresholdConfigWorkflow()
    {
        //setting up the workflow
        $this->initialSetupForShieldWorkflow(PermissionName::UPDATE_RISK_THRESHOLD_CONFIG);

        //creating the workflowAction using route "risk-actions/create"
        $request = [
            'content' => [
                "threshold"      => 50,
                "vendor"         => "sift",
                "category"       => "merchant_category",
                "category_value" => "it_and_software",
            ],
            'url'     => '/shield/risk/threshold/configs/49',
            'method'  => 'put',
        ];

        $this->mockShieldRequest();

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    public function testShieldDeleteThresholdConfigWorkflow()
    {
        //setting up the workflow
        $this->initialSetupForShieldWorkflow(PermissionName::DELETE_RISK_THRESHOLD_CONFIG);

        //creating the workflowAction using route "risk-actions/create"
        $request = [
            'url'     => '/shield/risk/threshold/configs/49',
            'method'  => 'delete',
        ];

        $this->mockShieldRequest();

        $response = $this->makeRequestAndGetContent($request);

        $workflowActionId = $response['id'];

        $this->assertWorkflowActionFailsWithShieldFailure($workflowActionId);

        $this->assertWorkflowExecutesIfShieldPasses($workflowActionId);
    }

    private function initialSetupForShieldWorkflow($permissionName)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $perm = $this->fixtures->create('permission', ['name' => $permissionName]);

        $role->permissions()->attach($perm->getId());

        $this->ba->adminAuth();

        $this->setupWorkflow('Shield Workflow', $permissionName, "test");
    }

    protected function assertWorkflowActionFailsWithShieldFailure($workflowActionId)
    {
        //need to unset the mock so that 'sendRequestV2ForWorkflow' can throw error on it's own
        unset($this->app['shield']);

        try
        {
            $this->performWorkflowAction($workflowActionId, true);
        }
        catch (\Throwable $e){}

        $this->assertNotNull($e);

        $workflowAction = $this->getDbEntityById('workflow_action', $workflowActionId);

        //if error occur then workflowAction will not be executed.
        $this->assertEquals('open', $workflowAction['state']);
    }

    protected function assertWorkflowExecutesIfShieldPasses($workflowActionId)
    {
        $this->mockShieldRequest();

        $this->performWorkflowAction($workflowActionId, true);

        $workflowAction = $this->getDbEntityById('workflow_action', $workflowActionId);

        $this->assertEquals('executed', $workflowAction['state']);
    }

    protected function mockShieldRequest($expectedResponse = [])
    {
        $this->shieldMock = Mockery::mock('RZP\Services\ShieldClient', $this->app)->makePartial();

        $this->shieldMock->shouldAllowMockingProtectedMethods();

        $this->app['shield'] = $this->shieldMock;

        $this->shieldMock->shouldReceive('sendRequestV2ForWorkflow')->times(1)->andReturn($expectedResponse);
    }

}
