<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\EntityActionTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Org\Repository as OrgRepository;

class InternationalEnablementTest extends TestCase
{
    use EntityActionTrait;

    use DbEntityFetchTrait;

    use RequestResponseFlowTrait;

    use WorkflowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InternationalEnablementTestData.php';

        parent::setUp();
    }

    private function createFixtures()
    {
        $merchant = $this->fixtures->on('live')->create('merchant', [
            'id'                    => 'EV7j5qM0qca1U3',
            'product_international' => '0000',
            'pricing_plan_id'       => '1hDYlICobzOCYt',
        ]);

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant->getId(),
            'international_activation_flow' => 'whitelist']);

        $this->setupWorkflows();

        $this->mockRazorx();

        return $merchant;
    }

    protected function setupWorkflows()
    {
        $this->fixtures->on('live')->create('org:admin_for_razorpay_org');

        $permissionWorkflowNameMap = [
            Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL      => 'PG International',
            Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL => 'Product 2.0 International',
        ];

        $permissionNames = array_keys($permissionWorkflowNameMap);

        foreach ($permissionNames as $permissionName)
        {
            $permission = $this->getDbEntity('permission', ['name' => $permissionName], 'live');

            DB::connection('live')->table('permission_map')->insert(
                [
                    'entity_id'     => Org::RZP_ORG,
                    'entity_type'   => 'org',
                    'permission_id' => $permission->getId(),
                ]);
        }


        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        foreach ($permissionWorkflowNameMap as $permissionName => $workflowName)
        {
            $workflow = $this->createWorkflow([
                'org_id'      => '100000razorpay',
                'name'        => $workflowName,
                'permissions' => [$permissionName],
                'levels' => [
                    [
                        'level' => 1,
                        'op_type' => 'or',
                        'steps' => [
                            [
                                'reviewer_count' => 1,
                                'role_id' => Org::ADMIN_ROLE,
                            ],
                        ],
                    ],
                ],
            ], 'live');
        }
    }

    protected function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->willReturn('on');
    }

    public function testDraft()
    {
        $merchant = $this->createFixtures();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $this->startTest();

        // test draft update
        $testData = $this->testData['testDraftUpdate'];

        $this->startTest($testData);
    }

    public function testDraftWithValidationError()
    {
        $merchant = $this->createFixtures();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $testData = $this->testData['testDraftWithValidationErrorCase1'];

        $this->startTest($testData);

        $testData = $this->testData['testDraftWithValidationErrorCase2'];

        $this->startTest($testData);

        $testData = $this->testData['testDraftWithValidationErrorCase3'];

        $this->startTest($testData);
    }

    public function testGetCases()
    {
        $merchant = $this->createFixtures();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        // no entry

        $testData = $this->testData['testGetWithNoEntry'];

        $this->startTest($testData);

        // in draft

        // bootstrap draft

        $testData = $this->testData['testDraft'];

        $this->startTest($testData);

        // verify draft get

        $testData = $this->testData['testGetWithDraftEntry'];

        $this->startTest($testData);

        // submitted

        // bootstrap submitted

        $testData = $this->testData['testSubmitValidUseCase1'];

        $this->startTest($testData);

        // verify submitted get

        $testData = $this->testData['testGetWithSubmittedEntry'];

        $this->startTest($testData);

    }

    public function testPreviewCases()
    {
        $merchant = $this->createFixtures();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        // draft (without intl txn documents)

        $testData = $this->testData['testSubmitValidUseCase2'];

        $testData['request']['url'] = $this->testData['testDraft']['request']['url'];

        unset($testData['request']['content']['documents']);

        unset($testData['response']['content']['documents']);

        $this->startTest($testData);

        $testData = $this->testData['testPreviewForDraftWithoutIntlDocuments'];

        $this->startTest($testData);

        // submit - (with digital services and not accepting intl transaction)

        $testData = $this->testData['testSubmitValidUseCase1'];

        $this->startTest($testData);

        $testData = $this->testData['testPreviewForSubmit'];

        $this->startTest($testData);

        // submit - (with physical_goods and accepting intl transaction)

        $testData = $this->testData['testSubmitValidUseCase2'];

        $this->startTest($testData);

        $testData = $this->testData['testPreviewForSubmit'];

        $this->startTest($testData);
    }

    public function testDiscardCases()
    {
        $merchant = $this->createFixtures();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        // no entry

        $testData = $this->testData['testDiscardWithNoEntryAndSubmittedEntry'];

        $this->startTest($testData);

        // submitted entry

        // bootstrap submit

        $testData = $this->testData['testSubmitValidUseCase2'];

        $this->startTest($testData);

        // verify submit discard

        $testData = $this->testData['testDiscardWithNoEntryAndSubmittedEntry'];

        $this->startTest($testData);

        // in draft

        // bootstrap draft

        $testData = $this->testData['testDraft'];

        $this->startTest($testData);

        // verify draft discard

        $testData = $this->testData['testDiscardWithDraftEntry'];

        $this->startTest($testData);
    }

    public function testSubmitCases()
    {
        // TODO: approve workflows and verify the corresponding data as well

        $merchant = $this->createFixtures();

        $this->ba->proxyAuth('rzp_live_' . $merchant->getId());

        $testData = $this->testData['testSubmitWithValidationError'];

        $withoutIntlTxnDocuments = $testData['use_cases']['without_intl_txn_documents'];

        $testData['request']['content'] = array_replace(
            $testData['request']['content'], $withoutIntlTxnDocuments['request']['content']);

        $testData['response'] = $withoutIntlTxnDocuments['response'];

        $testData['exception'] = $withoutIntlTxnDocuments['exception'];

        $this->startTest($testData);

        $testData = $this->testData['testSubmitValidUseCase1'];

        $this->startTest($testData);

        $testData = $this->testData['testSubmitValidUseCase2'];

        $this->startTest($testData);
    }
}
