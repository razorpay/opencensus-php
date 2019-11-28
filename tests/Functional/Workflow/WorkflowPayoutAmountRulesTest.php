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
    protected $workflowIds = [];

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WorkflowPayoutAmountRulesTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        // Creating five workflows other than default workflow and storing its ids in $this->workflowIds
        for ($index = 0; $index < 5; $index++) {
            $workflow = $this->fixtures->create('workflow',
                [
                    'org_id' => $this->org->getId(),
                    'name'   => 'Test Workflow '.$index
                ]
            );
            $this->workflowIds[$index] = $workflow->getId();
        }

        DB::table('admins')->update(['allow_all_merchants' => 1]);
    }

    public function testCreateRulesWithOverlappingRanges()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = $this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testCreateRulesWithRangesLeavingGaps()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = $this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testCreateRulesWithWrongWorkflowId()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testDuplicateWorkflowIds()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = $this->workflowIds[0];
        }

        $this->startTest();
    }

    public function testCreateWorkflowPayoutAmountRules()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = $this->workflowIds[$index];
            $this->testData[__FUNCTION__]['response']['content']['items'][$index]['workflow_id'] = $this->workflowIds[$index];
        }

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

    public function testGetAllPayoutAmountRulesWithPaginationLinks()
    {
        $this->ba->adminAuth();

        $entries = [
            [
                'id'          => 1,
                'min_amount'  => 0,
                'max_amount'  => 100
            ],
            [
                'id'          => 2,
                'min_amount'  => 101,
                'max_amount'  => 1000
            ],
            [
                'id'          => 3,
                'min_amount'  => 1001,
                'max_amount'  => null
            ],
            [
                'id'          => 4,
                'min_amount'  => 0,
                'max_amount'  => 100
            ],
            [
                'id'          => 5,
                'min_amount'  => 101,
                'max_amount'  => null
            ]
        ];
        $skip = 2;
        $count = 2;
        for ($index = 0; $index < $count; $index++) {
            $this->testData[__FUNCTION__]['response']['content']['items'][$index]['workflow_id'] = $this->workflowIds[$index+$skip];
        }

        $index = 0;
        foreach($entries as $entry)
        {
            $entry['workflow_id'] = $this->workflowIds[$index++];
            $this->fixtures->create('workflow_payout_amount_rules', $entry);
        }

        $responseContent = $this->startTest();
        $this->assertEquals($responseContent['links'][0]['href'],getenv('APP_URL').'/v1/workflows/rules/payout_amount/all?count=2&skip=2');
        $this->assertEquals($responseContent['links'][1]['href'],getenv('APP_URL').'/v1/workflows/rules/payout_amount/all?count=2&skip=0');
        $this->assertEquals($responseContent['links'][2]['href'],getenv('APP_URL').'/v1/workflows/rules/payout_amount/all?count=2&skip=0');
        $this->assertEquals($responseContent['links'][3]['href'],getenv('APP_URL').'/v1/workflows/rules/payout_amount/all?count=2&skip=4');
        $this->assertEquals($responseContent['links'][4]['href'],getenv('APP_URL').'/v1/workflows/rules/payout_amount/all?count=2&skip=4');
    }

    public function testGetMerchantWorkflowPayoutAmountRules()
    {
        $this->ba->adminAuth();

        $entries = [
            [
                'id'          => 1,
                'min_amount'  => 0,
                'max_amount'  => 100
            ],
            [
                'id'          => 2,
                'min_amount'  => 101,
                'max_amount'  => null
            ]
        ];
        for ($index = 0; $index < 2; $index++) {
            $this->testData[__FUNCTION__]['response']['content']['items'][$index]['workflow_id'] = $this->workflowIds[$index];
        }
        $index = 0;
        foreach($entries as $entry)
        {
            $entry['workflow_id'] = $this->workflowIds[$index++];
            $this->fixtures->create('workflow_payout_amount_rules', $entry);
        }
        $this->startTest();
    }
}
