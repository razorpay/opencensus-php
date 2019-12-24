<?php

namespace RZP\Tests\Functional\Workflow;

use DB;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;


class WorkflowPayoutAmountRulesTest extends TestCase
{
    use HeimdallTrait;
    use WorkflowTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected $org = null;
    protected $input = null;
    protected $workflowIds = [];
    protected $customMid = null;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/WorkflowPayoutAmountRulesTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org');

        // Create a merchant different from that provided from setup to test features involving multiple merchants
        $customMerchant = $this->fixtures->create('merchant');

        // Storing the merchant id of the one created above and store it a global variable for later use
        $this->customMid = $customMerchant->getId();

        // Fetch permissionId for 'create_payout' permission which will be useful later
        $permissionId = DB::table('permissions')->where('name','=','create_payout')->value('id');

        // Creating three workflows other than default workflow and storing its ids in $this->workflowIds
        // These three workflows will belong to the merchant with merchant id '10000000000000'
        for ($index = 0; $index < 3; $index++)
        {
            // Creating workflow
            $workflow = $this->fixtures->create('workflow',
                [
                    'org_id' => $this->org->getId(),
                    'name'   => 'Test Workflow '.$index
                ]
            );

            // Fetch the created workflow's id
            $this->workflowIds[$index] = $workflow->getId();

            // Attaching create_payout permission to the workflow
            DB::table('workflow_permissions')->insert(
                [
                    'workflow_id'      => $this->workflowIds[$index],
                    'permission_id'    => $permissionId
                ]
            );
        }

        // Creating two more workflows with merchant id different from the default '10000000000000'
        for ($index = 3; $index < 5; $index++)
        {
            // Creating workflow
            $workflow = $this->fixtures->create('workflow',
                [
                    'org_id' => $this->org->getId(),
                    'name'   => 'Test Workflow '.$index,
                    'merchant_id' => $this->customMid
                ]
            );

            // Fetch the created workflow's id
            $this->workflowIds[$index] = $workflow->getId();

            // Attaching create_payout permission to the workflow
            DB::table('workflow_permissions')->insert(
                [
                    'workflow_id'      => $this->workflowIds[$index],
                    'permission_id'    => $permissionId
                ]
            );
        }

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);
    }

    public function testCreateRulesWithOverlappingRanges()
    {
        $this->ba->adminProxyAuth();

        // Attach rules to first three workflows with merchant id '1000000000000' with overlapping ranges
        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testCreateRulesWithRangesLeavingGaps()
    {
        $this->ba->adminAuth();

        // Attach rules to first three workflows with merchant id '1000000000000' with ranges leaving gaps
        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testCreateRulesWithExtraRanges()
    {
        $this->ba->adminAuth();

        // Attach rules to first three workflows with merchant id '1000000000000' with ranges leaving gaps
        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testCreateRulesWithWrongWorkflowId()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateRulesWithDuplicateWorkflowIds()
    {
        $this->ba->adminAuth();

        // Attach three rules to the first workflow hence repeating the first workflow id three times
        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[0];
        }

        $this->startTest();
    }

    public function testCreateWorkflowPayoutAmountRules()
    {
        $this->ba->adminAuth();

        // Attach rules to first three workflows with merchant id '1000000000000'
        // This should attach the rules by passing all validations
        for ($index = 0; $index < 3; $index++) {
            $this->testData[__FUNCTION__]['request']['content']['rules'][$index]['workflow_id'] = 'workflow_'.$this->workflowIds[$index];
            $this->testData[__FUNCTION__]['response']['content']['items'][$index]['workflow_id'] = $this->workflowIds[$index];
        }

        $this->startTest();
    }

    public function testEditWorkflowPayoutAmountRules()
    {
        $this->ba->adminAuth();

        // Attach a rule to the first workflow which we have created
        $this->fixtures->create('workflow_payout_amount_rules',[
            'workflow_id' => $this->workflowIds[0],
            'min_amount'  => 0,
            'max_amount'  => null
        ]);

        // Place its workflow id in request body
        $this->testData[__FUNCTION__]['request']['content']['rules'][0]['workflow_id'] = 'workflow_'.$this->workflowIds[0];

        $this->startTest();
    }

    public function testGetMerchantIdsWithPermission()
    {
        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        // We have created one custom merchant and one through the parent setup function both of which have workflows
        // with 'create_payout' permission and should be returned
        $this->testData[__FUNCTION__]['response']['content']['items'][0] = '10000000000000';
        $this->testData[__FUNCTION__]['response']['content']['items'][1] = $this->customMid;

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
                'min_amount'  => 100,
                'max_amount'  => 1000
            ],
            [
                'id'          => 3,
                'min_amount'  => 1000,
                'max_amount'  => null,
                'workflow_id' => 'workflowId1000'
            ]
        ];

        for ($index = 0; $index < 2; $index++)
        {
            $this->testData[__FUNCTION__]['response']['content']['items'][$index]['workflow_id'] = $this->workflowIds[$index];
        }

        $this->testData[__FUNCTION__]['response']['content']['items'][$index]['workflow_id'] = 'workflowId1000';

        $index = 0;

        // Attach first two rules workflows which we have created without steps
        for (; $index < 2; $index++)
        {
            $entries[$index]['workflow_id'] = $this->workflowIds[$index];
            $this->fixtures->create('workflow_payout_amount_rules', $entries[$index]);
        }

        // Attach third rule to default workflow created in parent setup since it contains steps
        // This is used for testing whether steps field is given properly
        $entries[$index]['workflow_id'] = 'workflowId1000';
        $this->fixtures->create('workflow_payout_amount_rules', $entries[$index]);

        $this->startTest();
    }

    public function testCreateWorkflowRulesWithWrongPermission()
    {
        $this->ba->adminAuth();

        // Fetch permissionId of 'edit_admin' permission and store it in local permissionId variable
        $permissionId = DB::table('permissions')->where('name','=','edit_admin')->value('id');

        // Create new workflow having this permission
        $workflow = $this->fixtures->create('workflow',
            [
                'org_id'        => $this->org->getId(),
                'name'          => 'Test Workflow Z'
            ]
        );

        // Insert rule with this workflow having edit_admin permission instead of create_payout permission
        DB::table('workflow_permissions')->insert(
            [
                'workflow_id'      => $workflow->getId(),
                'permission_id'    => $permissionId
            ]
        );

        $this->testData[__FUNCTION__]['request']['content']['rules'][0]['workflow_id'] = 'workflow_'.$workflow->getId();

        $this->startTest();
    }

    public function testCreateRulesWithMultipleMerchants()
    {
        $this->ba->adminAuth();

        // Workflow with merchant id '10000000000000'
        $this->testData[__FUNCTION__]['request']['content']['rules'][0]['workflow_id'] = 'workflow_'.$this->workflowIds[0];

        // Workflow with custom merchant id which we created in setup function
        $this->testData[__FUNCTION__]['request']['content']['rules'][1]['workflow_id'] = 'workflow_'.$this->workflowIds[3];

        $this->startTest();
    }
}
