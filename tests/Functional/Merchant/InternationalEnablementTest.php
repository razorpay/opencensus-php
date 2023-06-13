<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Base\EsDao;
use RZP\Services\RazorXClient;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\Merchant as MerchantMail;
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

    protected $esDao;

    protected $esClient;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/InternationalEnablementTestData.php';

        parent::setUp();

        $this->mockStork();

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();
    }

    protected function mockStork()
    {
        $this->storkMock = Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);
    }

    protected function createMerchantUserMapping(string $userId, string $merchantId, string $role, $mode = 'test', $product = 'primary')
    {
        DB::connection($mode)->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'product'     => $product,
                'role'        => $role,
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }

    private function createFixtures(array $permissionWorkflowNameMap = [], string $mode = 'live')
    {
        $merchant = $this->fixtures->on('live')->create('merchant', [
            'name'                  => 'testname',
            'id'                    => 'EV7j5qM0qca1U3',
            'product_international' => '0000',
            'pricing_plan_id'       => '1hDYlICobzOCYt',
        ]);

        $this->fixtures->user->createUserMerchantMappingForDefaultUser($merchant['id']);

        $user = $this->fixtures->create('user', ['email' => 'testingemail@gmail.com', 'contact_mobile' => '1234567890', 'contact_mobile_verified' => true]);

        $this->createMerchantUserMapping($user['id'], $merchant['id'], 'owner');

        $this->merchantAssignPricingPlan('1hDYlICobzOCYt', $merchant['id']);

        $this->fixtures->on('live')->create('merchant_detail', [
            'merchant_id'                   => $merchant['id'],
            'international_activation_flow' => 'whitelist']);

        if (empty($permissionWorkflowNameMap) === true)
        {
            $permissionWorkflowNameMap = [
                Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL      => 'PG International',
                Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL => 'Product 2.0 International',
            ];
        }

        $this->setupWorkflows($permissionWorkflowNameMap, $mode);

        $this->mockRazorx();

        return $merchant;
    }

    protected function setupWorkflows(array $permissionWorkflowNameMap, string $mode = 'live')
    {
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
            ], $mode);
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

    public function expectStorkSendSmsRequest($templateName, $destination, $expectedParams = [])
    {
        $this->storkMock
            ->shouldReceive('sendSms')
            ->times(1)
            ->with(
                Mockery::on(function($mockInMode) {
                    return true;
                }),
                Mockery::on(function($actualPayload) use ($templateName, $destination, $expectedParams) {
                    // We are sending null in contentParams in the payload if there is no SMS_TEMPLATE_KEYS present for that event
                    // Reference: app/Notifications/Dashboard/SmsNotificationService.php L:99
                    if (isset($actualPayload['contentParams']) === true)
                    {
                        $this->assertArraySelectiveEquals($expectedParams, $actualPayload['contentParams']);
                    }

                    if (($templateName !== $actualPayload['templateName']) or
                        ($destination !== $actualPayload['destination']))
                    {
                        return false;
                    }

                    return true;
                }))
            ->andReturnUsing(function() {
                return ['success' => true];
            });
    }

    public function expectStorkSendWhatsappMessageRequest($text, $destination, $useRegexForText = false): void
    {
        $this->storkMock
            ->shouldReceive('sendWhatsappMessage')
            ->times(1)
            ->with(
                Mockery::on(function($mode) {
                    return true;
                }),
                Mockery::on(function($actualText) use ($text, $useRegexForText) {
                    $actualText = trim(preg_replace('/\s+/', ' ', $actualText));

                    if ($useRegexForText === true)
                    {
                        if (preg_match($text, $actualText) === 0)
                        {
                            return false;
                        }
                    }
                    else
                    {
                        $text = trim(preg_replace('/\s+/', ' ', $text));
                        if ($actualText !== $text)
                        {
                            return false;
                        }
                    }

                    return true;
                }),
                Mockery::on(function($actualReceiver) use ($destination) {

                    if ($actualReceiver !== $destination)
                    {
                        return false;
                    }

                    return true;
                }),
                Mockery::on(function($input) {
                    return true;
                }))
            ->andReturnUsing(function() {
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
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

    /* Commenting this test as the validations from draft API has been temporarily removed.
      * Will uncomment it once the condition is added.
      *
      *
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
    */

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

        $this->startTest($testData);

        $testData = $this->testData['testSubmitValidUseCase1'];

        $this->startTest($testData);

        $testData = $this->testData['testSubmitValidUseCase2'];

        $this->startTest($testData);
    }

    public function testDraftV2Case()
    {
        $merchant = $this->createFixtures([
            Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testDraft'];

        $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

        $this->startTest($testData);

        // test draft update
        $testData = $this->testData['testDraftUpdate'];

        $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

        $this->startTest($testData);
    }

    /* Commenting this test as the validations from draft API has been temporarily removed.
      * Will uncomment it once the condition is added.
      *
      *
        public function testDraftV2WithValidationError()
        {
            $merchant = $this->createFixtures([
                 Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
            ], 'test');

            $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

            $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

            $testData = $this->testData['testDraftWithValidationErrorCase1'];

            $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

            $this->startTest($testData);

            $testData = $this->testData['testDraftWithValidationErrorCase2'];

            $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

            $this->startTest($testData);

            $testData = $this->testData['testDraftWithValidationErrorCase3'];

            $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

            $this->startTest($testData);
        }
     */

    public function testSubmitV2ValidationError()
    {
        $merchant = $this->createFixtures([
            Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testSubmitWithValidationError'];

        $withoutIntlTxnDocuments = $testData['use_cases']['without_intl_txn_documents'];

        $testData['request']['content'] = array_replace(
            $testData['request']['content'], $withoutIntlTxnDocuments['request']['content']);

        $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

        $testData['response'] = $withoutIntlTxnDocuments['response'];

        $this->startTest($testData);

        $this->fixtures->edit('merchant_detail', $merchant->getId(), [
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'trading',
        ]);

        $testData = $this->testData['testSubmitWithValidationError'];

        $withoutBusinessCategorySubcategoryDocuments = $testData['use_cases']['without_business_category_subcategory_documents'];

        $testData['request']['content'] = array_merge($testData['request']['content'], [
            'version'   => 'v2',
            'documents' => [
                'bank_statement_inward_remittance' => [
                    [
                        'id'           => 'doc_10000011111111',
                        'display_name' => 'display_name_1',
                    ],
                ],
                'current_payment_partner_settlement_record' => [
                    [
                        'id'           => 'doc_10000011111111',
                        'display_name' => 'display_name_1',
                    ],
                ],
            ],
        ]);

        $testData['response'] = $withoutBusinessCategorySubcategoryDocuments['response'];

        $testData['exception'] = $withoutBusinessCategorySubcategoryDocuments['exception'];

        $this->startTest($testData);
    }

    public function storkMockForUnderReview()
    {
        $tatDaysLater = Carbon::now()->addDays(2)->format('M d,Y');

        $expectedStorkParameters = [
            'update_date' => $tatDaysLater,
        ];

        $this->expectStorkSendSmsRequest('sms.dashboard.ie_under_review', '1234567890', $expectedStorkParameters);

        $this->expectStorkSendWhatsappMessageRequest('Hi testname,
Your request to activate international card payments is under review. We’ll verify your details in a few days and share an update by ' . $tatDaysLater . '.
Note: You’ll be able to collect international card payments only after verification is complete.
To check details, go to the ‘International payments’ option in ‘Account and Settings’ section on your Razorpay dashboard: https://dashboard.razorpay.com/app/payment-methods?instrument=international
Thank you,
Team Razorpay',
        '1234567890'
        );
    }

    public function storkMockForApprove()
    {
        $this->expectStorkSendSmsRequest('sms.dashboard.ie_successful', '1234567890');

        $this->expectStorkSendWhatsappMessageRequest('Hi testname,
Your request to activate international card payments was successful.
You can now collect international card payments on payment gateway, payment pages, payment links, and invoices.
To check details, go to the ‘International payments’ option in ‘Account and Settings’ section on your Razorpay dashboard: https://dashboard.razorpay.com/app/payment-methods?instrument=international
Thank you,
Team Razorpay',
            '1234567890'
        );
    }

    public function testSubmitV2WithoutMandatoryDocumentCase()
    {
        Mail::fake();

        $merchant = $this->createFixtures([
            Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $this->storkMockForUnderReview();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testSubmitValidUseCase1'];

        $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

        $this->startTest($testData);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            if ($mail->view === 'emails.merchant.ie_under_review')
            {
                return true;
            }

            return false;
        });
    }

    public function testSubmitV2WithMandatoryDocumentCase()
    {
        Mail::fake();

        $merchant = $this->createFixtures([
            Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $this->storkMockForUnderReview();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $this->fixtures->edit('merchant_detail', $merchant->getId(), [
            'business_category'     => 'financial_services',
            'business_subcategory'  => 'trading',
        ]);

        $testData = $this->testData['testSubmitValidUseCase2'];

        $testData['request']['content']['version'] = 'v2';

        $testData['request']['content']['documents']['sebi_certificate'] = [
            [
                'id'           => 'doc_10000011111111',
                'display_name' => 'display_name_1',
            ],
        ];

        $this->startTest($testData);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            if ($mail->view === 'emails.merchant.ie_under_review')
            {
                return true;
            }

            return false;
        });
    }

    public function testInternationalProductStatusRequestedSecondTime()
    {
        Mail::fake();

        $merchant = $this->createFixtures([
            Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $this->storkMockForUnderReview();

        $this->storkMockForApprove();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->edit('merchant', $merchant->getId(), ['product_international' => '0111']);

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testSubmitValidUseCase1'];

        $testData['request']['content']['version'] = 'v2';

        $this->startTest($testData);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testGetProductInternationalStatusV2Workflow'];

        $testData['response']['content']['data'] = [
            'payment_gateway' => 'in_review',
            'payment_links'   => 'approved',
            'payment_pages'   => 'approved',
            'invoices'        => 'approved',
        ];

        $this->startTest($testData);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            if ($mail->view === 'emails.merchant.ie_under_review')
            {
                return true;
            }

            return false;
        });

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->performWorkflowAction($workflowAction['id'], true, 'test');

        $this->esClient->indices()->refresh();

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testGetProductInternationalStatusV2Workflow'];

        $testData['response']['content']['data'] = [
            'payment_gateway' => 'approved',
            'payment_links'   => 'approved',
            'payment_pages'   => 'approved',
            'invoices'        => 'approved',
        ];

        $this->startTest($testData);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            if ($mail->view === 'emails.merchant.ie_successful')
            {
                return true;
            }

            return false;
        });
    }


    public function testInternationalProductStatusRequestedInReview()
    {
        Mail::fake();

        $merchant = $this->createFixtures([
            Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $this->storkMockForUnderReview();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->edit('merchant', $merchant->getId(), ['product_international' => '0111']);

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testSubmitValidUseCase1'];

        $testData['request']['content']['version'] = 'v2';

        $this->startTest($testData);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testGetProductInternationalStatusV2Workflow'];

        $testData['response']['content']['data'] = [
            'payment_gateway' => 'in_review',
            'payment_links'   => 'approved',
            'payment_pages'   => 'approved',
            'invoices'        => 'approved',
        ];

        $this->startTest($testData);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            if ($mail->view === 'emails.merchant.ie_under_review')
            {
                return true;
            }

            return false;
        });
    }

    public function testInternationalProductStatusRequestedApproved()
    {
        Mail::fake();

        $merchant = $this->createFixtures([
            Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $this->storkMockForApprove();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->edit('merchant', $merchant->getId(), ['product_international' => '0111']);

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testSubmitValidUseCase1'];

        $testData['request']['content']['version'] = 'v2';

        $this->startTest($testData);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->performWorkflowAction($workflowAction['id'], true, 'test');

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testGetProductInternationalStatusV2Workflow'];

        $testData['response']['content']['data'] = [
            'payment_gateway' => 'approved',
            'payment_links'   => 'approved',
            'payment_pages'   => 'approved',
            'invoices'        => 'approved',
        ];

        $this->startTest($testData);

        Mail::assertQueued(MerchantMail\MerchantDashboardEmail::class, function ($mail)
        {
            $viewData = $mail->viewData;

            if ($mail->view === 'emails.merchant.ie_successful')
            {
                return true;
            }

            return false;
        });
    }

    public function testInternationalProductStatusReject()
    {
        $merchant = $this->createFixtures([
             Permission\Name::TOGGLE_INTERNATIONAL_REVAMPED  => 'toggle_international_revamped',
        ], 'test');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testSubmitValidUseCase1'];

        $testData['request']['content'] = array_merge($testData['request']['content'], ['version' => 'v2']);

        $this->startTest($testData);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->performWorkflowAction($workflowAction['id'],false,'test');

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testGetProductInternationalStatusV2Workflow'];

        $testData['response']['content']['data'] = [
            'payment_gateway' => 'rejected',
            'payment_links'   => 'no_action_received',
            'payment_pages'   => 'no_action_received',
            'invoices'        => 'no_action_received',
        ];

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
                $response = new \WpOrg\Requests\Response;

                $response->body = json_encode(['key' => 'value']);

                return $response;
            });
    }

    public function testGetInternationalEnablementDetail()
    {
        $merchant = $this->createFixtures();

        $this->fixtures->edit('merchant', 'EV7j5qM0qca1U3', ['international' => 1]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testInternationalVisibility'];

        $this->startTest($testData);
    }

    public function testGetInternationalEnablementDetailNegative()
    {
        $merchant = $this->createFixtures();

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $merchantUser['id']);

        $testData = $this->testData['testInternationalVisibilityFalse'];

        $this->startTest($testData);
    }

}
