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

    public function testShieldWorkflow()
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
        $request = [
             'method' => 'POST',
             'url' => '/w-actions/' . $workflowActionId . '/checkers',
             'content' => [
                  'approved' => true,
             ],
        ];

        try
        {
             $this->refreshEsIndices();
             $this->makeRequestAndGetContent($request);
        }
        catch (\Throwable $e){}

        $this->assertNotNull($e);

        $workflowAction = $this->getDbEntityById('workflow_action', $workflowActionId);

        //if error occur then workflowAction will not be executed.
        $this->assertEquals($workflowAction['state'], 'open');

        $this->mockShieldRequest();

        $this->refreshEsIndices();
        $this->makeRequestAndGetContent($request);

        $workflowAction = $this->getDbEntityById('workflow_action', $workflowActionId);

        $this->assertEquals($workflowAction['state'], 'executed');
    }

    protected function mockShieldRequest()
    {
        $this->shieldMock = Mockery::mock('RZP\Services\ShieldClient', $this->app)->makePartial();

        $this->shieldMock->shouldAllowMockingProtectedMethods();

        $this->app['shield'] = $this->shieldMock;

        $this->shieldMock->shouldReceive('sendRequestV2ForWorkflowApproval')->times(1)->andReturn([]);

    }

}
