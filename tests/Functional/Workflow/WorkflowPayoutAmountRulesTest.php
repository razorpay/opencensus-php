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
    protected $customMid = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WorkflowPayoutAmountRulesTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        $customMerchant = $this->fixtures->create('merchant');
        $this->customMid = $customMerchant->getId();

        $permissionId = DB::table('permissions')->where('name','=','create_payout')->value('id');

        // Creating three workflows other than default workflow and storing its ids in $this->workflowIds
        for ($index = 0; $index < 3; $index++)
        {
            $workflow = $this->fixtures->create('workflow',
                [
                    'org_id' => $this->org->getId(),
                    'name'   => 'Test Workflow '.$index
                ]
            );

            $this->workflowIds[$index] = $workflow->getId();

            DB::table('workflow_permissions')->insert(
                [
                    'workflow_id'      => $this->workflowIds[$index],
                    'permission_id'    => $permissionId
                ]
            );
        }

        // Creating two more workflows with merchant id different from the default one
        for ($index = 4; $index < 5; $index++)
        {
            $workflow = $this->fixtures->create('workflow',
                [
                    'org_id' => $this->org->getId(),
                    'name'   => 'Test Workflow '.$index,
                    'merchant_id' => $this->customMid
                ]
            );

            $this->workflowIds[$index] = $workflow->getId();

            DB::table('workflow_permissions')->insert(
                [
                    'workflow_id'      => $this->workflowIds[$index],
                    'permission_id'    => $permissionId
                ]
            );
        }

        DB::table('admins')->update(['allow_all_merchants' => 1]);
    }

    public function testCreateRulesWithOverlappingRanges()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testCreateRulesWithRangesLeavingGaps()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testCreateRulesWithWrongWorkflowId()
    {
        $this->ba->adminProxyAuth();

        $this->startTest();
    }

    public function testCreateRulesWithDuplicateWorkflowIds()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[0];
        }

        $this->startTest();
    }

    public function testCreateWorkflowPayoutAmountRules()
    {
        $this->ba->adminProxyAuth();

        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[$index];
            $this->testData[__FUNCTION__]['response']['content']['items'][$index]['workflow_id'] = $this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testEditWorkflowPayoutAmountRules()
    {
        $this->ba->adminProxyAuth();

        $this->fixtures->create('workflow_payout_amount_rules',[
            'workflow_id' => $this->workflowIds[0],
            'min_amount'  => 0,
            'max_amount'  => null
        ]);

        $this->testData[__FUNCTION__]['request']['content']['rules'][0]['workflow_id'] = 'workflow_'.$this->workflowIds[0];

        $this->startTest();
    }

    public function testGetAllPayoutAmountRules()
    {
        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        // These entries have to be made and inserted here and not setup() because otherwise the create workflow rules
        // test above will fail, stating that the workflow payout rules have already been created.
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
            ],
            [
                'id'          => 3,
                'min_amount'  => 0,
                'max_amount'  => null,
                'merchant_id' => $this->customMid
            ]
        ];

        for ($index = 0; $index < 2; $index++)
        {
            $this->testData[__FUNCTION__]['response']['content']['items'][0]['rules'][$index]['workflow_id']
                = $this->workflowIds[$index];
        }

        $this->testData[__FUNCTION__]['response']['content']['items'][1]['merchant_id'] = $this->customMid;

        // Assigning custom merchant id to third workflow rule
        $this->testData[__FUNCTION__]['response']['content']['items'][1]['rules'] = [
            [
                'min_amount'  => 0,
                'max_amount'  => null,
                'merchant_id' => $this->customMid,
                'workflow_id' => $this->workflowIds[$index]
            ]
        ];

        // Inserting workflow payout amount rules
        $index = 0;
        foreach($entries as $entry)
        {
            $entry['workflow_id'] = $this->workflowIds[$index++];
            $this->fixtures->create('workflow_payout_amount_rules', $entry);
        }

        $this->startTest();
    }

    public function testGetMerchantWorkflowPayoutAmountRules()
    {
        $this->ba->adminAuth();

        // These entries have to be made and inserted here and not setup() because otherwise the create workflow rules
        // test above will fail, stating that the workflow payout rules have already been created.
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

        for ($index = 0; $index < 2; $index++)
        {
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

    public function testCreateWorkflowRulesWithWrongPermission()
    {
        $this->ba->adminProxyAuth();

        $permissionId = DB::table('permissions')->where('name','=','edit_admin')->value('id');

        $workflow = $this->fixtures->create('workflow',
            [
                'org_id'        => $this->org->getId(),
                'name'          => 'Test Workflow Z'
            ]
        );

        // Insert rule with edit_admin permission instead of create_payout permission
        DB::table('workflow_permissions')->insert(
            [
                'workflow_id'      => $workflow->getId(),
                'permission_id'    => $permissionId
            ]
        );

        $this->testData[__FUNCTION__]['request']['content']['rules'][0]['workflow_id'] = 'workflow_'.$workflow->getId();

        $this->startTest();
    }
}
