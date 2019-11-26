<?php

namespace RZP\Tests\Functional\Workflow;

use DB;
use RZP\Constants\Table;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\Permission as PermissionEntity;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;


class WorkflowPayoutAmountRulesTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use HeimdallTrait;
    use WorkflowTrait;

    protected $org = null;
    protected $input = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WorkflowPayoutAmountRulesTestData.php';

        parent::setUp();

        DB::table('admins')->update(['allow_all_merchants' => 1]);

    }

    public function testCreateRulesWithOverlappingRanges()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testCreateRulesWithRangesLeavingGaps()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testCreateRulesWithWrongWorkflowId()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testCreateWorkflowPayoutAmountRules()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testEditWorkflowPayoutAmountRules()
    {
        $this->ba->adminProxyAuth();

        $this->fixtures->create('workflow_payout_amount_rules',[
            'workflow_id' => 'workflowId1000',
            'min_amount'  => 0,
            'max_amount'  => null
        ]);
        $this->startTest();
    }

    public function testGetAllPayoutAmountRules()
    {
//        sd(DB::table('merchants')->select('id')->get());

//        $workflowPermissions = $this->getPermissions('workflow');
//
//        $this->input = [
//            'org_id'      => $this->org->getId(),
//            'permissions' => array_slice($workflowPermissions, 0, 2),
//        ];
//
//        $this->createWorkflow($this->input);
        $this->ba->adminAuth();

        $entries = [
            [
                'workflow_id' => 'workflowId1000',
                'min_amount'  => 0,
                'max_amount'  => 100
            ],
            [
                'workflow_id' => 'workflowId1000',
                'min_amount'  => 101,
                'max_amount'  => 1000
            ],
            [
                'workflow_id' => 'workflowId1000',
                'min_amount'  => 1001,
                'max_amount'  => null
            ],
            [
                'workflow_id' => 'workflowId1001',
                'min_amount'  => 0,
                'max_amount'  => 100
            ],
            [
                'workflow_id' => 'workflowId1001',
                'min_amount'  => 101,
                'max_amount'  => null
            ]
        ];
        $index = 1;
        foreach($entries as $entry)
        {
            $entry['id'] = $index++;
            $this->fixtures->create('workflow_payout_amount_rules', $entry);
        }
        $this->startTest();
    }

    public function testGetMerchantWorkflowPayoutAmountRules()
    {
        $this->ba->adminAuth();

        $entries = [
            [
                'workflow_id' => 'workflowId1000',
                'min_amount'  => 0,
                'max_amount'  => 100
            ],
            [
                'workflow_id' => 'workflowId1000',
                'min_amount'  => 101,
                'max_amount'  => null
            ]
        ];
        $index = 1;
        foreach($entries as $entry)
        {
            $entry['id'] = $index++;
            $this->fixtures->create('workflow_payout_amount_rules', $entry);
        }
        $this->startTest();
    }
}
