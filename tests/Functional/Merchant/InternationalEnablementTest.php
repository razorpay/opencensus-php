<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Mockery;
use RZP\Constants\Mode;
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

    /**
     * @var Mockery\Mock
     */
    private $storkMock;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InternationalEnablementTestData.php';

        parent::setUp();

        $this->mockStork();
    }

    protected function mockStork()
    {
        $this->storkMock = Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $this->startTest();

        // test draft update
        $testData = $this->testData['testDraftUpdate'];

        $this->startTest($testData);
    }

    public function testDraftWithValidationError()
    {
        $merchant = $this->createFixtures();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        // draft (without intl txn documents)

        $testData = $this->testData['testSubmitValidUseCase2'];

        $testData['request']['url'] = $this->testData['testDraft']['request']['url'];

        unset($testData['request']['content']['documents']);

        unset($testData['response']['content']['documents']);

        $this->startTest($testData);

        $testData = $this->testData['testPreviewForDraftWithoutIntlDocuments'];

        $this->startTest($testData);

        $testData = $this->testData['testInternationalVisibilityWithoutDocuments'];

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

        $testData = $this->testData['testInternationalVisibility'];

        $this->startTest($testData);
    }

    public function testDiscardCases()
    {
        $merchant = $this->createFixtures();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

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

    public function testReminderCallback()
    {
        $merchant = $this->createFixtures();

        //Reminder callback

        $this->ba->reminderAppAuth();

        $attributes = [
            'merchant_id'        => $merchant->getId(),
            'goods_type'         => 'physical_goods',
            'business_use_case'  => null,
            'allowed_currencies' => [
                'INR'
            ],
            'monthly_sales_intl_cards_min'        => 2000,
            'monthly_sales_intl_cards_max'        => 4000,
            'business_txn_size_min'               => 10000,
            'business_txn_size_max'               => 20000,
        ];

        $internationalEnablementDetail = $this->fixtures->international_enablement_detail->createEntityInTestAndLive('international_enablement_detail',$attributes);

        Mail::fake();

         $this->expectStorkSmsRequest([
            'templateName'      => 'sms.dashboard.international_enablement_reminder',
            'templateNamespace' => 'payments_dashboard',

        ]);

        $this->expectStorkWhatsappRequest("Hi {merchant_name} ! You're 1 step away from unlocking 30% more sales for {business_name} by activating international payments - finish it now!\nRegards,\nTeam Razorpay",
        [
            'params'=> [
                'merchant_name' => "Razorpay",
                'business_name' => "Razorpay",
            ]
        ]);

        //Success Response

        $testData = $this->testData['testReminderCallbackSuccess'];

        $testData['request']['url'] = '/international_enablement/reminders/live/'.$merchant->getId();

        $this->startTest($testData);

        //Failure Response

        $attributes['submit'] = 1;

        $internationalEnablementDetail = $this->fixtures->international_enablement_detail->createEntityInTestAndLive('international_enablement_detail',$attributes);

        $testData = $this->testData['testReminderCallbackFailure'];

        $testData['request']['url'] = '/international_enablement/reminders/live/'.$merchant->getId();

        $this->startTest($testData);

    }

    protected function expectStorkSmsRequest($expectInput): void
    {
        $this->storkMock
            ->shouldReceive('sendSms')
            ->times(1)
            ->with(
                Mockery::on(function ($mode)
                {
                    return true;
                }),
                Mockery::on(function ($input) use ($expectInput)
                {
                    $this->assertArraySelectiveEquals($expectInput, $input);

                    return true;
                }),
                Mockery::on(function ($mockInMode)
                {
                    return true;
                })
            )
            ->andReturnUsing(function ()
            {
                return ['success' => true];
            });
    }

    protected function expectStorkWhatsappRequest($expectedTemplate, $expectedInput,
                                                  $expectedReceiver = '9876543210'): void
    {
        $this->storkMock
            ->shouldReceive('sendWhatsappMessage')
            ->with(
                Mockery::on(function ($mode)
                {
                    return true;
                }),
                Mockery::on(function ($template) use ($expectedTemplate)
                {
                    return $expectedTemplate === $template;
                }),
                Mockery::on(function ($receiver) use ($expectedReceiver)
                {
                    return $receiver === $expectedReceiver;
                }),
                Mockery::on(function ($input) use ($expectedInput)
                {
                    $this->assertArraySelectiveEquals($expectedInput, $input);

                    return true;
                })
            )
            ->andReturnUsing(function ()
            {
                $response = new \Requests_Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

}
