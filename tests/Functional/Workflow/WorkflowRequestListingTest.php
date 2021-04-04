<?php

namespace RZP\Tests\Functional\Workflow;

use RZP\Services\EsClient;
use Illuminate\Support\Facades\DB;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Admin\Permission as AdminPermission;
use RZP\Models\Admin\Role\Repository as RoleRepository;
use RZP\Models\Admin\Permission\Name;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;

class WorkflowRequestListingTest extends TestCase
{
    use WorkflowTrait;
    use HeimdallTrait;
    use RequestResponseFlowTrait;

    protected $input = [];
    protected $authToken = null;
    protected $org = null;
    protected $workflowPermissionIds = [];

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WorkflowRequestListingTestData.php';

        parent::setUp();

        // we have all the required roles in the default org setup.
        //workflow actions need to be created.
        $this->fixtures->workflow_action->setUp();

        $makerRole = (new RoleRepository())->findByIdAndOrgId(Org::MAKER_ROLE, Org::RZP_ORG);
        $checkerRole = (new RoleRepository())->findByIdAndOrgId(Org::CHECKER_ROLE, Org::RZP_ORG);
        $permissions = (new AdminPermission\Repository)->retrieveIdsByNames([AdminPermission\Name::VIEW_WORKFLOW_REQUESTS]);
        $makerRole->permissions()->attach($permissions);
        $checkerRole->permissions()->attach($permissions);
    }

    /**
     * Default workflow action will have checker as checker role.
     * making checker admin auth request and checking.
     */
    public function testWorkflowCheckerRequests()
    {
        $this->ba->adminAuth('test', Org::CHECKER_ADMIN_TOKEN, 'org_' . Org::RZP_ORG);

        $this->addPermissionToBaAdmin(Name::VIEW_ALL_WORKFLOW);

        $this->startTest();
    }

    public function testWorkflowMakerRequests()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $this->startTest();
        $this->app['workflow']->setWorkflowMaker(null);

        $this->ba->adminAuth('test', Org::CHECKER_ADMIN_TOKEN, 'org_' . Org::RZP_ORG);

        $this->testData[__FUNCTION__]['response']['content'] = [
            "entity" => "collection",
            "count"  => 0,
            "items"  => [],
        ];

        $this->addPermissionToBaAdminForToken(Name::VIEW_ALL_WORKFLOW, Org::CHECKER_ADMIN_TOKEN);

        $this->startTest();
    }


    public function testWorkflowClosedRequests()
    {
        $this->ba->adminAuth('test', null, 'org_' . Org::RZP_ORG);

        $action = $this->fixtures->create('workflow_action', [
            'maker_id'      => Org::MAKER_ADMIN,
            'maker_type'    => 'admin',
            'state'         => \RZP\Models\State\Name::CLOSED,
        ]);

        $this->fixtures->create('state', [
            'action_id'     => $action->getId(),
            'admin_id'      => Org::SUPER_ADMIN,
            'name'         => \RZP\Models\State\Name::CLOSED,
        ]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $action->getPublicId();

        $this->addPermissionToBaAdmin(Name::VIEW_ALL_WORKFLOW);

        $this->startTest();
    }

    /**
     * Workflow will be created on editadmin cause of default workflow
     */
    public function testAdminCheckedRequests()
    {
        $workflow = $this->editAdmin(Org::RZP_ORG_SIGNED, Org::SUPER_ADMIN_SIGNED);

        $this->ba->adminAuth('test', null, Org::RZP_ORG_SIGNED);

        $this->addPermissionToBaAdminForToken(Name::EDIT_ACTION, Org::CHECKER_ADMIN_TOKEN);

        $this->addPermissionToBaAdminForToken(Name::VIEW_ALL_WORKFLOW, Org::CHECKER_ADMIN_TOKEN);

        $this->approveWorkflowAction($workflow['id']);

        $this->startTest();
    }


    /**
     * Asserts that if there is an ElasticSearch integration error, then failure should be propagated and no
     * new workflow_actions is created
     *
     * ref: https://razorpay.slack.com/archives/C2CP46QBW/p1610627632010000?thread_ts=1610603837.001900&cid=C2CP46QBW
     *
     */
    public function testWorkflowCreateElasticSearchErrorShouldFail()
    {
        $beforeCount = Db::table('workflow_actions')->count();

        $esMock = $this->getMockBuilder(EsClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['indexHeimdall'])
            ->getMock();

        $esMock->method('indexHeimdall')
            ->will($this->returnCallback(function (){
                throw new NoNodesAvailableException();
            }));

        $this->app['es'] = $esMock;

        $this->expectException(NoNodesAvailableException::class);

        $this->editAdmin(Org::RZP_ORG_SIGNED, Org::SUPER_ADMIN_SIGNED);

        $afterCount = Db::table('workflow_actions')->count();

        $this->assertEquals($beforeCount, $afterCount);
    }

    public function testWorkflowSuperAdminAllRequests()
    {
        $this->ba->adminAuth('test');

        $this->addPermissionToBaAdmin(Name::VIEW_ALL_WORKFLOW);

        $this->fixtures->create('workflow_action:closed_workflow_action');

        $this->startTest();
    }

    public function testWorkflowSuperAdminOpenRequests()
    {
        $this->ba->adminAuth('test');

        $this->addPermissionToBaAdmin(Name::VIEW_ALL_WORKFLOW);

        $this->fixtures->create('workflow_action:closed_workflow_action');

        $this->startTest();
    }

    public function testWorkflowSearchByMakerId()
    {
        $this->ba->adminAuth('test', Org::CHECKER_ADMIN_TOKEN, 'org_' . Org::RZP_ORG);

        $this->addPermissionToBaAdmin(Name::VIEW_ALL_WORKFLOW);

        $action = $this->fixtures->create('workflow_action', [
            'maker_id'   => '12345678',
            'maker_type' => 'admin',
            'state'      => \RZP\Models\State\Name::OPEN,
        ]);

        $this->fixtures->create('workflow_action', [
            'maker_id'   => '12345679',
            'maker_type' => 'admin',
            'state'      => \RZP\Models\State\Name::OPEN,
        ]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $action->getPublicId();

        $this->startTest();
    }
}
