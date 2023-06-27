<?php

use Carbon\Carbon;
use RZP\Diag\EventCode;
use RZP\Jobs\BankingAccount\BankingAccountNotifyMob;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Models\Contact;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use Razorpay\OAuth\Client;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Services\HubspotClient;
use RZP\Models\Admin\Permission;
use RZP\Services\Mock\BankingAccountService;
use RZP\Services\Mock\Mozart;
use RZP\Services\RazorXClient;
use RZP\Models\User\BankingRole;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Status;
use RZP\Models\User\Entity as UserEntity;
use RZP\Services\Mock\CapitalCardsClient;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Models\BankingAccount\AccountType;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Mail\BankingAccount\XProActivation;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\BankingAccount\Activation\MIS;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Mail\BankingAccount\UpdatesForAuditor;
use RZP\Services\Segment\XSegmentClient;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Mail\BankingAccount\StatusNotifications\Created;
use RZP\Models\BankingAccount\Gateway\Rbl as RblGateway;
use RZP\Mail\BankingAccount\StatusNotifications\Rejected;
use RZP\Mail\BankingAccount\StatusNotifications\Processed;
use RZP\Mail\BankingAccount\StatusNotifications\Cancelled;
use RZP\Mail\BankingAccount\StatusNotifications\Activated;
use RZP\Models\BankingAccount\Activation\Detail\Validator;
use RZP\Models\BankingAccount\Core as BankingAccountCore;
use RZP\Mail\BankingAccount as BankingAccountMails;
use RZP\Mail\BankingAccount\Activation as ActivationMails;
use RZP\Mail\BankingAccount\StatusNotifications\Processing;
use RZP\Tests\Functional\Helpers\CreateLegalDocumentsTrait;
use RZP\Tests\Functional\Fixtures\Entity\User as UserFixture;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Mail\BankingAccount\StatusNotifications\Unserviceable;
use Illuminate\Contracts\Container\BindingResolutionException;
use RZP\Mail\BankingAccount\DocketMail\DocketMail;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\BankingAccount\Gateway\Rbl\Processor as RblProcessor;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\DiscrepancyInDoc;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantNotAvailable;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantPreparingDoc;
use RZP\Mail\BankingAccount\StatusNotifications\Factory as StatusUpdateMailerFactory;
use RZP\Models\BankingAccount\Activation\MIS\Leads;
use RZP\Models\BankingAccountService\BasDtoAdapter;
use RZP\Services\PincodeSearch;
use RZP\Tests\Traits\MocksSplitz;

class BankingAccountTest extends TestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use EventsTrait;
    use MocksDiagTrait;
    use CreateLegalDocumentsTrait;
    use MocksSplitz;

    const DefaultMerchantId = '10000000000000';

    const DefaultPartnerMerchantId = 'randomBankPaId';

    private $partnerOwnerUser;

    protected $storkMock;
    protected $authServiceMock;
    protected $bankingAccountServiceMock;

    protected $bankLMSSetupComplete;
    protected $bankLMSSetupResponse;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/BankingAccountTestData.php';

        parent::setUp();

        // storing the below in redis for purpose of test cases.
        $pincodeList = ['560030', '560034'];

        $this->app['redis']->sadd('rbl_pincode_set', $pincodeList);

        $this->app['config']->set('applications.banking_account.mock', true);
        $this->app['config']->set('applications.banking_account_service.mock', true);
        $this->app['config']->set('applications.salesforce.mock', true);

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);
        $this->bankingAccountServiceMock = Mockery::mock(BankingAccountService::class, [$this->app])->makePartial();

        $this->fixtures->create('merchant', ['id' => self::DefaultPartnerMerchantId]);

        $this->fixtures->create('merchant_detail:sane', [
            'merchant_id'       => self::DefaultPartnerMerchantId,
            'business_type'     => 1,
            'contact_name'      => 'contact name',
            'contact_mobile'    => '8888888888',
            'activation_status' => null
        ]);

        $this->partnerOwnerUser = $this->fixtures->user->createBankingUserForMerchant(self::DefaultPartnerMerchantId);

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $this->fixtures->on('live')->create('merchant_detail:sane', ['merchant_id'=>'10000000000000']);
        $this->fixtures->on('test')->create('merchant_detail:sane', ['merchant_id'=>'10000000000000']);
    }

    protected function mockBvsService()
    {
        $mock = $this->mockCreateLegalDocument();

        $mock->expects($this->once())->method('createLegalDocument')->withAnyParameters();
    }

    protected function setupBankPartnerMerchant()
    {


        $this->fixtures->merchant->edit(self::DefaultPartnerMerchantId, ['partner_type' =>  Merchant\Constants::BANK_CA_ONBOARDING_PARTNER]);

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' =>  Merchant\Constants::BANK_CA_ONBOARDING_PARTNER, 'merchant_id' => self::DefaultPartnerMerchantId]);

        $feature = $this->fixtures->on('live')->create('feature', [
            'entity_id'   => self::DefaultPartnerMerchantId,
            'name'        => Feature\Constants::RBL_BANK_LMS_DASHBOARD,
            'entity_type' => 'merchant',
        ]);
    }

    public function detachAdminPermission(string $permissionName)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $permissionId = (new Permission\Repository)->retrieveIdsByNames([$permissionName])[0];

        $role->permissions()->detach($permissionId);
    }

    protected function mockStork()
    {
        $this->storkMock = \Mockery::mock('RZP\Services\Stork', [$this->app])->makePartial()->shouldAllowMockingProtectedMethods();

        $this->app->instance('stork_service', $this->storkMock);
    }

    protected function mockHubSpotClient($methodName)
    {
        $hubSpotMock = $this->getMockBuilder(HubspotClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods([$methodName])
                            ->getMock();

        $this->app->instance('hubspot', $hubSpotMock);

        return $hubSpotMock;
    }

    protected function mockCapitalCards()
    {
        $capitalCardsMock = $this->getMockBuilder(CapitalCardsClient::class)
                                 ->setConstructorArgs([$this->app])
                                 ->setMethods(['getCorpCardAccountDetails'])
                                 ->getMock();

        $capitalCardsMock->method('getCorpCardAccountDetails')
                         ->will($this->returnCallback(
                             function($data) {
                                 $this->assertNotEmpty($data['balance_id']);

                                 if ($data['balance_id'] === 'hnaswdyeujdwsj')
                                 {
                                     return [];
                                 }

                                 return [
                                     'entity_id'      => 'qaghsquiqasdwd',
                                     'account_number' => '10234561782934',
                                     'user_id'        => 'wgahkasyqsdghws',
                                     'balance_id'     => $data['balance_id'],
                                 ];
                             }
                         ));

        $this->app->instance('capital_cards_client', $capitalCardsMock);
    }

    public function testCreateBankingAccount()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        Mail::fake();

        $expectedHubspotCall = false;
        $this->mockHubspotAndAssertForChangeEvent($expectedHubspotCall);

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        Mail::assertQueued(XProActivation::class);

        $this->assertTrue($expectedHubspotCall);
    }

    public function testCreateBankingAccountTwiceForSameMerchant()
    {
        $testData = $this->testData['testCreateBankingAccount'];

        $bankingAccount = $this->startTest($testData);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $bankingAccountTwo = $this->startTest($testData);

        $this->assertEquals($bankingAccount['id'], $bankingAccountTwo['id']);

        /*
         * after existing application is updated to `terminated` status, new application
         * can be created for same merchant
         */
        /* @var Entity $bankingAccountEntity*/
        $bankingAccountEntity = (new BankingAccount\Repository())->findByPublicId($bankingAccount['id']);
        $this->updateBankingAccount($bankingAccountEntity,[
            'status' => Status::TERMINATED
        ]);

        $this->ba->proxyAuth();

        $this->startTest($testData);
    }

    public function testCreateBankingAccountForNonRzpOrgMerchant()
    {
        $testData = $this->testData['testCreateBankingAccount'];

        $admin = $this->ba->getAdmin();

        $admin->merchants()->detach('10000000000000');

        $admin->setAllowAllMerchants();

        $admin->saveOrFail();

        $org = $this->fixtures->create('org');

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $org['id']]);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->startTest($testData);
    }

    public function testCreateBankingAccountAdminForNonRzpOrgMerchant()
    {
        $testData = $this->testData['testCreateBankingAccountAdmin'];

        $admin = $this->ba->getAdmin();

        $admin->merchants()->detach('10000000000000');

        $admin->setAllowAllMerchants();

        $admin->saveOrFail();

        $org = $this->fixtures->create('org');

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $org['id']]);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testCreateBankingAccountForNonRzpOrgMerchantFromDashboard()
    {
        $admin = $this->ba->getAdmin();

        $admin->merchants()->detach('10000000000000');

        $admin->setAllowAllMerchants();

        $admin->saveOrFail();

        $org = $this->fixtures->create('org');

        $this->fixtures->edit('merchant', '10000000000000', ['org_id' => $org['id']]);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testCreateBankingAccountWithUnserviceablePincode()
    {
        $this->startTest();
    }

    public function testCreateBankingAccountWithInvalidBank()
    {
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->createBankingAccount([Entity::CHANNEL => 'TEST']);
        });
    }

    public function testCreateBankingAccountWithEmptyPincode()
    {
        $data = $this->testData[__FUNCTION__];

        $this->runRequestResponseFlow($data, function()
        {
            $this->createBankingAccount([Entity::PINCODE => '']);
        });
    }

    public function testCreateBankingAccountWithActivationDetail()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $merchantId = $bankingAccount->merchant->getId();

        $this->createMerchantDetail([
            'merchant_id' => $merchantId,
            'business_name' => 'CA Business']
        );

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertQueued(XProActivation::class);

        return $bankingAccount;
    }

    public function testCreateBankingAccountWithAdditionalDetails()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $merchantId = $bankingAccount->merchant->getId();

        $this->createMerchantDetail([
            'merchant_id' => $merchantId,
            'business_name' => 'CA Business']
        );

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertQueued(XProActivation::class);

        return $bankingAccount;
    }

    public function testCreateBankingAccountWithActivationDetailFormDashboard()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertNotQueued(XProActivation::class);

        Mail::assertNotQueued(StatusUpdateMailerFactory::class);

        return $bankingAccount;
    }

    public function testCreateBankingAccountWithDefaultBusinessType()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->testData[__FUNCTION__] = $this->testData['testCreateBankingAccountWithActivationDetailFormDashboard'];

        $dataToReplace = [
            'request' => [
                'content' => [
                    'activation_detail' => [
                        'business_type' => 'default'
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testCreateBankingAccountFormMerchantDashboard()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $basMock = $this->bankingAccountServiceMock;

        $basMock->shouldNotReceive('createBusinessOnBas');

        $basMock->shouldNotReceive('createRblOnboardingApplicationOnBas');

        $this->app->instance('banking_account_service', $basMock);

        $this->createBankingAccountFromDashboard();

        Mail::fake();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertNotQueued(XProActivation::class);
    }

    public function mockBankingAccountServiceCallsForRblOnBasExperiment(bool $shouldMockBusinessCreation) {
        $basMock = $this->bankingAccountServiceMock;

        if ($shouldMockBusinessCreation)
        {
            $basMock->shouldReceive('createBusinessOnBas')->andReturns([
                'id'                            => 'Le5mr3Cd8iwuvy',
                'name'                          => 'name',
                'industry_type'                 => 'default',
                'merchant_id'                   => self::DefaultMerchantId,
                'constitution'                  => 'partnership',
                'registered_address'            => 'abcde',
                'registered_address_details'    => [
                    'address_pin_code' => 560030
                ]
            ]);
        }
        else
        {
            $basMock->shouldNotReceive('createBusinessOnBas');
        }

        $basMock->shouldReceive('createRblOnboardingApplicationOnBas')->andReturn([
            'id'                    => 'Le8uzhRdJoqH3o',
            'business_id'           => 'Le5mr3Cd8iwuvy',
            'application_status'    => 'created',
            'application_type'      => 'RBL_ONBOARDING_APPLICATION',
            'sales_team'            => 'SELF_SERVE',
            'person_details'        => [
                'first_name'            => 'Merchant Name',
                'email_id'              => 'test-abc@email.com',
                'phone_number'          => '9876543210',
                'role_in_business'      => 'Founder'
            ],
            'metadata'              => [
                'additional_details'    => [
                    'application_initiated_from'    => 'X_DASHBOARD',
                    'gstin_prefilled_address'       => 1,
                ],
            ],
        ]);

        $this->app->instance('banking_account_service', $basMock);
    }

    public function verifyCreateBankingAccountRblOnBasExperiment(bool $shouldCreateBusiness) {

        $attribute = ['activation_status' => 'activated', 'contact_email' => 'test@email.com'];

        if (!$shouldCreateBusiness) {
            $attribute['bas_business_id'] = 'Le5mr3Cd8iwuvy';
        }

        $merchantDetail = $this->fixtures->on('live')->edit('merchant_detail', '10000000000000', $attribute);
        $merchantDetail = $this->fixtures->on('test')->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->mobAppAuthForProxyRoutes();

        $this->ba->addXOriginHeader();

        $splitzMockInput = [
            'id'            => '10000000000000',
            'experiment_id' => 'LGcU6yGzKQCwoY',
        ];

        $splitzMockOutput = [
            'response' => [
                'variant' => [
                    'name' => 'active'
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzMockInput, $splitzMockOutput);

        $this->mockBankingAccountServiceCallsForRblOnBasExperiment($shouldCreateBusiness);

        $response = $this->createBankingAccountFromDashboard([
            Entity::ACTIVATION_DETAIL => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM => 'self_serve',
                ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'abced',
                ActivationDetail\Entity::MERCHANT_POC_EMAIL => 'test-abc@email.com',
                ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER => '9876543210',
                ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'Founder',
                ActivationDetail\Entity::MERCHANT_POC_NAME => 'Merchant Name',
                ActivationDetail\Entity::BUSINESS_TYPE => 'default',
                ActivationDetail\Entity::BUSINESS_NAME => 'name',
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    'application_initiated_from' => 'X_DASHBOARD',
                    'gstin_prefilled_address' => 1,
                ]
            ]
        ], false, false);

        $additionalDetails = $response[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ADDITIONAL_DETAILS];

        $response[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS]
            [ActivationDetail\Entity::ADDITIONAL_DETAILS] = json_decode($additionalDetails, true);

        $this->assertArraySelectiveEquals([
            'id' => 'bacc_Le8uzhRdJoqH3o',
            'status' => 'created',
            'channel' => 'rbl',
            'account_type' => 'current',
            BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS => [
                ActivationDetail\Entity::SALES_TEAM => 'self_serve',
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    'application_initiated_from' => 'X_DASHBOARD',
                    'gstin_prefilled_address' => 1,
                ],
            ]
        ], $response);

        Mail::fake();
        Mail::assertNotQueued(XProActivation::class);

        /* @var Merchant\Detail\Entity $updatedMerchantDetail*/
        $updatedMerchantDetail = (new Merchant\Detail\Repository())->getByMerchantId($merchantDetail->merchant['id']);
        $this->assertEquals('Le5mr3Cd8iwuvy', $updatedMerchantDetail->getBasBusinessId());
    }

    public function testCreateBankingAccountRblOnBasExperimentBusinessDoesNotExist()
    {
        $this->verifyCreateBankingAccountRblOnBasExperiment(true);
    }

    public function testCreateBankingAccountRblOnBasExperimentBusinessAlreadyExists()
    {
        $this->verifyCreateBankingAccountRblOnBasExperiment(false);
    }

    public function testCreateBankingAccountFromMerchantDashboardServiceabilityExperiment()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $splitzMockInput = [
            'id'            => '10000000000000',
            'experiment_id' => 'L2UsfwrU1dDxE4',
        ];

        $splitzMockOutput = [
            'response' => [
                'variant' => [
                    'name' => 'active'
                ]
            ]
        ];

        $basMock = $this->bankingAccountServiceMock;

        $basMock->shouldReceive('sendRequestAndProcessResponse')
            ->andReturn([
            'data' => [
                'serviceability' => [
                    [
                        'is_serviceable'        => true,
                        'partner_bank'          => 'RBL',
                        'unserviceable_reasons' => null,
                    ],
                    [
                        'is_serviceable'        => false,
                        'partner_bank'          => 'ICICI',
                        'unserviceable_reasons' => [
                            "PIN_CODE_UNSERVICEABLE"
                        ],
                    ]
                ],
                'pincode_details' => [
                    'city'      => 'belgaum',
                    'state'     => 'karnatka',
                    'region'    => 'south',
                    'error'     => ''
                ]
            ]
        ]);

        $this->app->instance('banking_account_service', $basMock);

        $this->createBankingAccountFromDashboard();

        Mail::fake();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertNotQueued(XProActivation::class);
    }

    public function testCreateBankingAccountAndSubmitFormMerchantDashboard()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        Mail::fake();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccountId,
                'method'  => 'PATCH',
            ],
        ];

        $this->assertFreshDeskTicketCreatedEventFired(true,[
            'banking_account_id' => substr($bankingAccountId,5),
            'status'             => 'picked',
        ]);

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertQueued(XProActivation::class);
    }

    public function testFreshDeskTicketForSelfServe()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        $this->bookSlotForBankingAccount($bankingAccountId);

        Mail::fake();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccountId,
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->assertFreshDeskTicketCreatedEventFired(true,[
            'banking_account_id' => substr($bankingAccountId,5),
            'status'             => 'picked',
        ]);

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertQueued(XProActivation::class, function ($mail) use($bankingAccount)
        {
            $mail->build();
            return $mail->hasTo('x.support@razorpay.com');
        });
    }

    public function verifyFreshDeskTicketCreationOnBankingAccountUpdate($baId, $baActivationDetailId, $oldDeclarationStep = 0, $oldSalesPitchCompleted = 0, $newDeclarationStep = 1, $newSalesPitchCompleted = 1, $shouldQueue = true)
    {
        $this->fixtures->edit('banking_account', $baId, ['status' => 'created']);

        $baDetails = ['declaration_step' => $oldDeclarationStep];

        if ($oldSalesPitchCompleted !== null)
        {
            $baDetails += ['additional_details' => json_encode(
                [
                    'sales_pitch_completed' => $oldSalesPitchCompleted,
                    'skip_dwt'              => 0
                ])];
        }

        $this->fixtures->edit('banking_account_activation_detail', $baActivationDetailId, $baDetails);

        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testFreshDeskTicketCreationOnBankingAccountUpdate'];

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/bacc_' . $baId,
                'content' => [
                    'activation_detail' => [
                        'declaration_step' => $newDeclarationStep,
                        'additional_details' => [
                            'sales_pitch_completed' => $newSalesPitchCompleted
                        ],
                    ]
                ],
            ],
            'response' => [
                'content' => [
                    'channel' => 'rbl',
                    'status'  => $shouldQueue ? 'picked' : 'created',
                ],
            ]
        ];

        if ($shouldQueue)
        {
            $this->assertFreshDeskTicketCreatedEventFired(true,[
                'banking_account_id' => $baId,
                'status'             => 'picked',
            ]);

        }

        $this->ba->proxyAuth('rzp_test_' . self::DefaultMerchantId);

        $this->startTest($dataToReplace);

        if ($shouldQueue)
        {
            Mail::assertQueued(XProActivation::class, function ($mail)
            {
                $mail->build();
                $this->assertArraySelectiveEquals(['skip_dwt_status' => 'PROCEED_WITH_DWT'],$mail->viewData);
                return $mail->hasTo('x.support@razorpay.com');
            });
        }
        else
        {
            Mail::assertNotQueued(XProActivation::class, function ($mail)
            {
                $mail->build();
                return $mail->hasTo('x.support@razorpay.com');
            });
        }
    }

    public function verifyFreshDeskTicketCreationOnActivationDetailUpdate($baId, $baActivationDetailId, $oldDeclarationStep = 0, $oldSalesPitchCompleted = 0, $newDeclarationStep = 1, $newSalesPitchCompleted = 1, $shouldQueue = true)
    {
        $this->fixtures->edit('banking_account', $baId, ['status' => 'created']);

        $admin = $this->fixtures->create('admin', ['org_id' => Org::RZP_ORG, 'email' => 'abc@razorpay.com']);

        $baDetails = ['declaration_step' => $oldDeclarationStep];

        if ($oldSalesPitchCompleted !== null)
        {
            $baDetails += ['additional_details' => json_encode(['sales_pitch_completed' => $oldSalesPitchCompleted])];
        }

        $this->fixtures->edit('banking_account_activation_detail', $baActivationDetailId, $baDetails);

        Mail::fake();

        $this->testData[__FUNCTION__] = $this->testData['testFreshDeskTicketCreationOnActivationDetailUpdate'];

        $additionDetailsResponse = json_encode(['sales_pitch_completed' => $newSalesPitchCompleted]);

        // Adding this because ArraySelective does not work for json strings
        if ($newDeclarationStep === 1)
        {
            $additionDetailsResponse = json_encode(['sales_pitch_completed' => $newSalesPitchCompleted,
                'skip_dwt' => 0]);
        }

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_internal/activation/bacc_' . $baId . '/details',
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail(),
                ],
                'content' => [
                    'declaration_step' => $newDeclarationStep,
                    'additional_details' => [
                        'sales_pitch_completed' => $newSalesPitchCompleted
                    ],
                ],
            ],
            'response' => [
                'content' => [
                    'declaration_step' => $newDeclarationStep ? '1' : '0',
                    'additional_details' => $additionDetailsResponse,
                ],
            ]
        ];

        $this->ba->mobAppAuthForInternalRoutes();

        if ($shouldQueue)
        {
            $this->assertFreshDeskTicketCreatedEventFired(true,[
                'banking_account_id' => $baId,
                'status'             => 'picked',
            ]);

        }

        $this->startTest($dataToReplace);

        if ($shouldQueue)
        {
            Mail::assertQueued(XProActivation::class, function ($mail)
            {
                $mail->build();
                return $mail->hasTo('x.support@razorpay.com');
            });
        }
        else
        {
            Mail::assertNotQueued(XProActivation::class, function ($mail)
            {
                $mail->build();
                return $mail->hasTo('x.support@razorpay.com');
            });
        }

        $bankingAccount = $this->getDbLastEntity('banking_account');
        $this->assertEquals($bankingAccount->getStatus(), $shouldQueue ? 'picked' : 'created');
    }

    public function testFreshDeskTicketCreationBehaviourForDifferentOneCaScenarios()
    {
        $attribute = ['activation_status' => 'activated'];

        $this->fixtures->edit('merchant_detail', self::DefaultMerchantId, $attribute);

        $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'ca_onboarding_flow', 'ONE_CA');

        $ba = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baActivationDetail = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba->getId(),
            'business_category'         => 'partnership',
            'sales_team'                => 'self_serve',
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'booking_date_and_time'     => strtotime('17-Nov-2021 11:30:00'),
        ]);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 1, 0, true);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 0, 1, true);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 1, 1, false);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 1, 0, true);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 0, 1, true);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 1, 1, false);
    }

    public function testFreshDeskTicketCreationBehaviourForDifferentNonOneCaScenarios()
    {
        $attribute = ['activation_status' => 'activated'];

        $this->fixtures->edit('merchant_detail', self::DefaultMerchantId, $attribute);

        $ba = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baActivationDetail = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba->getId(),
            'business_category'         => 'partnership',
            'sales_team'                => 'self_serve',
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'booking_date_and_time'     => strtotime('17-Nov-2021 11:30:00'),
        ]);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 1, 0, true);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 1, 0, true);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 0, null, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 1, 1, false);

        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnBankingAccountUpdate($ba->getId(), $baActivationDetail->getId(), 1, null, 1, 1, false);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 1, 0, true);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 0, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 1, 0, true);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 0, 1, 1, 1, true);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 0, 1, 1, false);

        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 0, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 0, 1, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 1, 0, false);
        $this->verifyFreshDeskTicketCreationOnActivationDetailUpdate($ba->getId(), $baActivationDetail->getId(), 1, 1, 1, 1, false);

    }

    public function testFreshDeskTicketforSalesAssistedFlow()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        Mail::fake();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccountId,
                'method'  => 'PATCH',
            ],
        ];

        $this->assertFreshDeskTicketCreatedEventFired(true,[
            'banking_account_id' => substr($bankingAccountId,5),
            'status'             => 'picked',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertQueued(XProActivation::class, function ($mail) use($bankingAccount)
        {
            $mail->build();
            return $mail->hasTo('x.support@razorpay.com');
        });
    }

    public function testCreateBankingAccountAndSubmitAgain()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        Mail::fake();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccountId,
                'method'  => 'PATCH',
            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        Mail::assertQueued(XProActivation::class);

        $this->startTest($dataToReplace);

        Mail::assertQueued(XProActivation::class, 1);
    }

    public function testCreateBankingAccountWithUnserviceableBusinessCategoryFormDashboard()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNull($bankingAccount);
    }

    public function testCreateBankingAccountWithUnserviceablePincodeFormDashboard()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNull($bankingAccount);
    }

    public function testCreateBankingAccountWithUnserviceableBusinessCategoryFromAdminDashboard()
    {

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->ba->adminAuth();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNull($bankingAccount);
    }

    public function testCreateBankingAccountWithServiceableBusinessCategoryFromAdminDashboard()
    {

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->ba->adminAuth();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotNull($bankingAccount);
    }

    public function testCreateBankingAccountWithActivationDetailWithSalesTeamAsCapitalSme()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $merchantId = $bankingAccount->merchant->getId();

        $this->createMerchantDetail([
                'merchant_id' => $merchantId,
                'business_name' => 'CA Business']
        );

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        $this->assertEquals('capital_sme', $activationDetailEntity['sales_team']);

        Mail::assertQueued(XProActivation::class);

        return $bankingAccount;
    }

    public function testCreateBankingAccountWithActivationDetailWithSalesTeamAsNitPartnerships()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $merchantId = $bankingAccount->merchant->getId();

        $this->createMerchantDetail([
                'merchant_id' => $merchantId,
                'business_name' => 'CA Business']
        );

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        $this->assertEquals('nit_partnerships', $activationDetailEntity['sales_team']);

        Mail::assertQueued(XProActivation::class);

        return $bankingAccount;
    }

    public function testCreateBankingAccountWithActivationDetailWithBusinessTypeAsOnePersonCompanies()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $merchantId = $bankingAccount->merchant->getId();

        $this->createMerchantDetail([
                'merchant_id' => $merchantId,
                'business_name' => 'CA Business']
        );

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        $this->assertEquals('one_person_company', $activationDetailEntity['business_category']);

        Mail::assertQueued(XProActivation::class);

        return $bankingAccount;
    }

    public function testCreateBankingAccountWithActivationDetailWithPoAndPoEVerified()
    {
        $requestPayload = [
            'activation_detail' => [
                'merchant_poc_name' => 'Umakant',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'sales_team' => 'sme',
                ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'ABC',
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'private_public_limited_company',
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    'verified_constitutions' => [
                        [
                            'constitution' => 'PUBLIC_LIMITED',
                            'source'       => 'gstin'
                        ],
                    ],
                    'verified_addresses' => [
                        [
                          'address' => 'ABC',
                          'source'  => 'gstin',
                        ],
                    ],
                ],
            ]
        ];
        $response = $this->createBankingAccountFromDashboard($requestPayload);

        $expectedAdditionalDetails = [
            'proof_of_entity' => [
                'status' => 'verified',
                'source' => 'gstin'
            ],
            'proof_of_address' => [
                'status' => 'verified',
                'source' => 'gstin'
            ],
        ];

        $actualAdditionalDetails = json_decode($response['banking_account_activation_details']['additional_details'],true);

        $this->assertArraySelectiveEquals($expectedAdditionalDetails,$actualAdditionalDetails);
    }

    public function testCreateBankingAccountWithActivationDetailWithPoAndPoENotVerified()
    {
        $requestPayload = [
            'activation_detail' => [
                'merchant_poc_name' => 'Umakant',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'sales_team' => 'sme',
                ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'ABCD',
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'trust',
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    'verified_constitutions' => [
                        [
                            'constitution' => 'PUBLIC_LIMITED',
                            'source'       => 'gstin'
                        ],
                    ],
                    'verified_addresses' => [
                        [
                            'address' => 'ABC',
                            'source'  => 'gstin',
                        ],
                    ],
                ],
            ]
        ];
        $response = $this->createBankingAccountFromDashboard($requestPayload);

        $actualAdditionalDetails = json_decode($response['banking_account_activation_details']['additional_details'],true);

        $this->assertArrayNotHasKey('proof_of_entity',$actualAdditionalDetails);

        $this->assertArrayNotHasKey('proof_of_address',$actualAdditionalDetails);
    }

    public function testCheckServiceableByRBL()
    {
        $this->app['config']->set('applications.banking_account.mock', true);

        $this->app['config']->set('applications.pincodesearcher.mock', true);

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testCheckWhitelistPincodeServiceableByIcic()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->startTest();
    }

    public function testCheckServiceableByRBLFromAdminDashboard()
    {
        $this->app['config']->set('applications.banking_account.mock', true);

        $this->app['config']->set('applications.pincodesearcher.mock', true);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testCreateBankingAccountWithActivationDetailFails()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->expectException(BadRequestValidationFailureException::class);

        $this->startTest();
    }

    public function testSuccessBankAccountInfoNotification(string $id = null)
    {
        $attribute =
            [
                'activation_status' => 'activated',
                'merchant_id'       => '1cXSLlUU8V9sXl',
            ];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $merchantId = '1cXSLlUU8V9sXl';

        $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $this->testCreateBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->testCreateActivationDetail(null, $bankingAccount);

        $this->fixtures->edit('banking_account',
            $bankingAccount->getId(),
            [
                'status' => 'initiated',
            ]);

        $this->assertEquals('created', $bankingAccount->getStatus());

        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber()
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->startTest($dataToReplace);

        $changeLogRequest  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($changeLogRequest);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('api_onboarding', $logs['items'][1]['status']);
        $this->assertEquals('in_review', $logs['items'][1]['sub_status']);
        $this->assertEquals('closed', $logs['items'][1]['bank_status']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals('bank_ops', $bankingAccountActivationDetail['assignee_team']);

        return $response;
    }

    public function testSuccessRblCoCreatedLeadCreation()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setMethods(['pushTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->Exactly(2))
                    ->method('pushTrackEvent')
                    ->willReturn(true);

        Mail::fake();

        $response = $this->startTest();

        Mail::assertSent(RZP\Mail\User\RazorpayX\SetPasswordRBLCoCreated::class, function ($mail)
        {
            $mail->build();

            $viewData = $mail->viewData;

            $this->assertArrayHasKey('token', $viewData);
            $this->assertArrayHasKey('email', $viewData);
            $this->assertEquals('emails.user.razorpayx.set_password_rbl_co_created', $mail->view);

            return ($mail->subject === RZP\Mail\User\RazorpayX\SetPasswordRBLCoCreated::SUBJECT && $mail->to[0]['address'] === 'harshada.mohite1@rblbank.com' && $mail->from[0]['address'] === 'x.support@razorpay.com');
        });


        $merchantAttribute = $this->getDbEntity('merchant_attribute', [
            'type' => 'ca_onboarding_flow',
        ]);

        $this->assertArraySelectiveEquals(
            [
                'product' => 'banking',
                'group'   => 'x_merchant_current_accounts',
                'type'    => 'ca_onboarding_flow',
                'value'   => 'RBL_CO_CREATED'
            ],
            $merchantAttribute->toArrayPublic()
        );

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->testAddVerificationDate($bankingAccount);

        $changeLogRequest  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($changeLogRequest);

        $this->assertEquals('created', $logs['items'][0]['status']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals('ops', $bankingAccountActivationDetail['assignee_team']);

        $this->assertEquals('co_created', $bankingAccountActivationDetail['application_type']);

        return $response;
    }

    public function testGetRblCoCreatedLeadsAfterCreation()
    {
        $response = $this->testSuccessRblCoCreatedLeadCreation();

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testAdminResetPasswordOnSuccessRblCoCreatedLeadCreation()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setMethods(['pushTrackEvent'])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(2))
                    ->method('pushTrackEvent')
                    ->willReturn(true);

        $response = $this->startTest();

        $merchantAttribute = $this->getDbEntity('merchant_attribute', [
            'type' => 'ca_onboarding_flow',
        ]);

        $this->assertArraySelectiveEquals(
            [
                'product' => 'banking',
                'group'   => 'x_merchant_current_accounts',
                'type'    => 'ca_onboarding_flow',
                'value'   => 'RBL_CO_CREATED'
            ],
            $merchantAttribute->toArrayPublic()
        );

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $changeLogRequest  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($changeLogRequest);

        $this->assertEquals('created', $logs['items'][0]['status']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals('ops', $bankingAccountActivationDetail['assignee_team']);

        $this->assertEquals('co_created', $bankingAccountActivationDetail['application_type']);

        $changeLogRequest  = [
            'url'     => '/users/co_created/reset-password',
            'method'  => 'POST',
            'content' => [
                'email' => 'Harshada.Mohite1@rblbank.com'
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($changeLogRequest);

        return $response;
    }

    public function testFailureRblCoCreatedLeadCreation()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $response = $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEmpty($bankingAccount);

        return $response;
    }

    public function testDuplicateRblCoCreatedLeadCreation()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $request  = [
            'url'     => '/banking_accounts/rbl/lead',
            'method'  => 'POST',
            'content' => [
                'NeoBankingLeadReq' => [
                    'Header' => [
                        'TranID'  => '1634732025132',
                        'Corp_ID' => 'WEIZMANNIM'
                    ],
                    'Body'   => [
                        'LeadID'                 => '550000',
                        'EmailAddress'           => 'Harshada.Mohite1@rblbank.com',
                        'Customer_Name'          => 'HarshadaMohite',
                        'Customer_Mobile_Number' => '9876767676',
                        'Customer_Address'       => 'Mulund',
                        'Customer_PinCode'       => '400080',
                        'Customer_City'          => 'Mulund'
                    ]
                ],
            ],
        ];

         $this->makeRequestAndGetContent($request);

        return $this->startTest();
    }

    public function testSuccessLeadCreationAndWebhookForAccountOpening()
    {
        $request  = [
            'url'     => '/banking_accounts/rbl/lead',
            'method'  => 'POST',
            'content' => [
                'NeoBankingLeadReq' => [
                    'Header' => [
                        'TranID'  => '1634732025132',
                        'Corp_ID' => 'WEIZMANNIM'
                    ],
                    'Body'   => [
                        'LeadID'                 => '550000',
                        'EmailAddress'           => 'Harshada.Mohite1@rblbank.com',
                        'Customer_Name'          => 'HarshadaMohite',
                        'Customer_Mobile_Number' => '9876767676',
                        'Customer_Address'       => 'Mulund',
                        'Customer_PinCode'       => '400080',
                        'Customer_City'          => 'Mulund'
                    ]
                ],
            ],
        ];

        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('created', $bankingAccount->getStatus());

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'],
                              [
                                  'status' => 'initiated',
                              ]);

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('trackEmailEvent')->withAnyArgs()->andReturn([]);

        $diagMock->shouldNotReceive('trackOnboardingEvent');

        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber()
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->startTest($dataToReplace);

        $changeLogRequest  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($changeLogRequest);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('api_onboarding', $logs['items'][1]['status']);
        $this->assertEquals('in_review', $logs['items'][1]['sub_status']);
        $this->assertEquals('closed', $logs['items'][1]['bank_status']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals('bank_ops', $bankingAccountActivationDetail['assignee_team']);

        return $response;
    }

    public function testValidateAccountOpeningDateInWebhook()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $bankingAccount = $this->setAuthAndCreateBankingAccount($merchantDetail->merchant['id']);

        $diagMock = $this->createAndReturnDiagMock();

        $expectedPayload = [
            'group' => 'onboarding',
            'name'  => 'x.ca.rbl.webhook.failure',
        ];
        $diagMock->shouldReceive('trackOnboardingEvent')
            ->once()
            ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                $this->assertEquals($expectedPayload, $eventData);
                return true;
            })
            ->andReturnNull();

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180851'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->startTest($dataToReplace);

        $this->assertEquals('Failure', $response['RZPAlertNotiRes']['Body']['Status']);
    }

    public function createAccountOpeningSuccessfulWebhook()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $bankingAccount = $this->setAuthAndCreateBankingAccount($merchantDetail->merchant['id']);

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->startTest($dataToReplace);

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);
    }

    public function testDataAmbiguityInWebhookWithSamePinCodeAndSameBusinessName()
    {
        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $merchantDetailArray);

        $bankingAccount = $this->setAuthAndCreateBankingAccount($merchantDetail->merchant['id']);

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        Mail::fake();

        $response = $this->startTest($dataToReplace);

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);

        Mail::assertNotQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class);

        Mail::assertNotQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            return ($mail->subject === ActivationMails\AccountOpeningWebhookDataAmbiguity::SUBJECT && $mail->to[0]['address'] === 'x-onboarding@razorpay.com');
        });
    }

    public function testDataAmbiguityInWebhookWithSamePinCodeAndSameBusinessNameInUpperCase()
    {
        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'Skull Gamers',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $merchantDetailArray);

        $bankingAccount = $this->setAuthAndCreateBankingAccount($merchantDetail->merchant['id']);

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        Mail::fake();

        $response = $this->startTest($dataToReplace);

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);

        Mail::assertNotQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class);

        Mail::assertNotQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            return ($mail->subject === ActivationMails\AccountOpeningWebhookDataAmbiguity::SUBJECT && $mail->to[0]['address'] === 'x-onboarding@razorpay.com');
        });
    }

    public function testDataAmbiguityInWebhookWithSamePinCodeAndDifferentBusinessName()
    {
        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'Razorpay Private Limited',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $merchantDetailArray);

        $bankingAccount = $this->setAuthAndCreateBankingAccount($merchantDetail->merchant['id']);

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        Mail::fake();

        $response = $this->startTest($dataToReplace);

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);

        Mail::assertQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class);

        Mail::assertQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            $recipientEmails = array_column($mail->to,'address');

            $this->assertArraySelectiveEquals(['x-onboarding@razorpay.com','x-caonboarding@razorpay.com'],$recipientEmails);

            return ($mail->subject === ActivationMails\AccountOpeningWebhookDataAmbiguity::SUBJECT);
        });
    }

    public function testDataAmbiguityInWebhookWithSamePinCodeAndSimilarityInBusinessNameLessThanRequiredPercent()
    {
        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'Internet Banking Ca',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $merchantDetailArray);

        $bankingAccount = $this->setAuthAndCreateBankingAccount($merchantDetail->merchant['id']);

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        Mail::fake();

        $response = $this->startTest($dataToReplace);

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);

        Mail::assertQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class);

        Mail::assertQueued(ActivationMails\AccountOpeningWebhookDataAmbiguity::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            $recipientEmails = array_column($mail->to,'address');

            $this->assertArraySelectiveEquals(['x-onboarding@razorpay.com','x-caonboarding@razorpay.com'],$recipientEmails);

            return ($mail->subject === ActivationMails\AccountOpeningWebhookDataAmbiguity::SUBJECT && $mail->to[0]['address'] === 'x-onboarding@razorpay.com');
        });
    }

    public function testAccountOpeningWebhookWithExistingAccountNumber()
    {
        $this->createAccountOpeningSuccessfulWebhook();

        $this->fixtures->user->createUserForMerchant('1cXSLlUU8V9sXl', [], 'owner', 'test');

        $this->createMerchantDetail(['merchant_id' => '1cXSLlUU8V9sXl','business_name' => 'foo']);

        $bankingAccount = $this->setAuthAndCreateBankingAccount('1cXSLlUU8V9sXl');

        $diagMock = $this->createAndReturnDiagMock();

        $expectedPayload = [
            'group' => 'onboarding',
            'name'  => 'x.ca.rbl.webhook.failure',
        ];
        $diagMock->shouldReceive('trackOnboardingEvent')
            ->once()
            ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                $this->assertEquals($expectedPayload, $eventData);
                return true;
            })
            ->andReturnNull();

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->startTest($dataToReplace);

        $this->assertEquals('Failure', $response['RZPAlertNotiRes']['Body']['Status']);

    }

    public function testRzpRefNumberNotExistScenarioInAccountOpeningWebhook()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->privateAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $diagMock = $this->createAndReturnDiagMock();

        $expectedPayload = [
            'group' => 'onboarding',
            'name'  => 'x.ca.rbl.webhook.failure',
        ];
        $diagMock->shouldReceive('trackOnboardingEvent')
            ->once()
            ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                $this->assertEquals($expectedPayload, $eventData);
                if (empty($merchant) == false)
                {
                    $this->assertEquals('100000Razorpay',$merchant->getId());
                }
                return true;
            })
            ->andReturnNull();

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => '00000',
                            'Account No' => '31900299180858'
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testResetWebhookDataCase()
    {
        $this->testUpdatedStatusFromInitiatedToAccountOpening();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->updateBankingAccount($bankingAccount, [
            Entity::STATUS                         => Status::API_ONBOARDING,
            Entity::SUB_STATUS                     => Status::IN_REVIEW,
            Entity::BANK_INTERNAL_STATUS           => Rbl\Status::API_ONBOARDING_IN_PROGRESS,
            Entity::ACCOUNT_IFSC                   => 'RATN0000156',
            Entity::ACCOUNT_NUMBER                 => '309002180853',
            Entity::BENEFICIARY_NAME               => 'INTERNET BANKING CA',
            Entity::BANK_INTERNAL_REFERENCE_NUMBER => 'random',
            Entity::BANK_REFERENCE_NUMBER          => '12345',
            Entity::BENEFICIARY_ADDRESS1           => 'RAM NAGAR',
            Entity::BENEFICIARY_ADDRESS2           => 'ADARSHA LANE',
            Entity::BENEFICIARY_ADDRESS3           => '.',
            Entity::ACCOUNT_ACTIVATION_DATE        => '1571119612',
            Entity::BENEFICIARY_CITY               => 'MUMBAI',
            Entity::BENEFICIARY_STATE              => 'MAHARASH',
            Entity::BENEFICIARY_COUNTRY            => 'INDIA',
            Entity::BENEFICIARY_MOBILE             => '1231231231',
            Entity::BENEFICIARY_EMAIL              => 'test@razorpay.com',
            Entity::BENEFICIARY_PIN                => '560030',
        ]);

        $this->addNewPermissionToExistingRole('reset_webhook_data');

        $this->ba->adminAuth();

        $resetWebhookDataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/webhooks/account_info/reset'
            ]
        ];

        $this->startTest($resetWebhookDataToReplace); // Fixing this test

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertNull($bankingAccount->getAccountNumber());

        $this->assertNull($bankingAccount->getBeneficiaryName());

        $this->assertEquals(RZP\Models\BankingAccount\Status::ACCOUNT_OPENING, $bankingAccount->getStatus());

        $statusChangeLogsArray = $statusChangeLogs['items'];

        $this->assertEquals($statusChangeLogsArray[count($statusChangeLogsArray) - 1]['status'], $bankingAccount->getStatus());

        $this->assertEquals($statusChangeLogsArray[count($statusChangeLogsArray) - 1]['sub_status'], $bankingAccount->getSubStatus());

        $this->assertEquals($statusChangeLogsArray[count($statusChangeLogsArray) - 1]['bank_status'], $bankingAccount->getBankInternalStatus());
    }

    public function testAccountInfoWebhookWithIncorrectAndThenCorrectDetails()
    {
        $response = $this->testFailedBankAccountInfoNotification();

        $this->assertEquals('Failure', $response['RZPAlertNotiRes']['Body']['Status']);

        $response = $this->testSuccessBankAccountInfoNotification();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('api_onboarding', $bankingAccount->getStatus());

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);
    }

    public function testFailedBankAccountInfoNotification()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $this->mockBankingAccountProcessRblAccountOpeningWebhook('Failure');

        return $this->startTest();
    }

    public function testUpdateAccountInfoWebhookInternally()
    {
        $this->ba->proxyAuth();

        $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account',
            $bankingAccount->getId(),
            [
                'status' => 'initiated',
            ]);

        $this->ba->adminAuth();

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber()
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testDoubleAccountOpeningWebhooks()
    {
        $this->testSuccessBankAccountInfoNotification();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No.' => '31900299180853'
                        ]
                    ]
                ]
            ]
        ];

        $diagMock = $this->createAndReturnDiagMock();

        $expectedPayload = [
            'group' => 'onboarding',
            'name'  => 'x.ca.rbl.webhook.failure',
        ];
        $diagMock->shouldReceive('trackOnboardingEvent')
            ->once()
            ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                $this->assertEquals($expectedPayload, $eventData);
                return true;
            })
            ->andReturnNull();

        $this->startTest($dataToReplace);

        // we are asserting that the values passed in second webhook will not be updated
        // as the first webhook is processed.
        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotEquals($bankingAccount['account_number'], 31900299180853);
    }

    public function testDoubleAccountOpeningWebhooksAllowedAfterManualIntervention()
    {
        $this->testSuccessBankAccountInfoNotification();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $newAccountNumber = '31900299180853';

        // asserting current account number is different
        $this->assertNotEquals($newAccountNumber, $bankingAccount->getAccountNumber());

        // Default behavior is to reject duplicate webhooks.
        // The following change allows for duplicate webhooks to update information.
        $this->fixtures->edit('banking_account', $bankingAccount['id'], [
            'account_activation_date' => null
        ]);

        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('trackOnboardingEvent');

        $dataToReplace = [
            'request' => [
                'content' => [
                    'RZPAlertNotiReq' => [
                        'Body' => [
                            'RZP_Ref No' => $bankingAccount->getBankReferenceNumber(),
                            'Account No.' => $newAccountNumber
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);

        // we are asserting that the values passed in second webhook will be updated
        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals($newAccountNumber, $bankingAccount['account_number']);
    }

    protected function createMerchantDetail(array $attrs = ['activation_status' => 'activated'])
    {
        if (array_key_exists('merchant_id', $attrs) and $attrs['merchant_id'] === '10000000000000')
        {
            $this->fixtures->edit('merchant_detail', '10000000000000', array_except($attrs, 'merchant_id'));
        }
        else
        {
            $this->fixtures->create('merchant_detail', $attrs);
        }
    }

    public function testActivate()
    {
        $this->setupBankPartnerMerchant();

        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        // ledger shadow experiment is NOT enabled
        $this->app->razorx->method('getTreatment')
            ->willReturn('control');

        $this->mockLedgerSns(0);

        Mail::fake();

        $this->mockRaven();

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        (new User())->createBankingUserForMerchant($merchantDetail->merchant['id'], [
            'contact_mobile' => '8888888888',
        ]);

        $this->testCreateActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'status'                => 'processed',
            'sub_status'            => 'api_onboarding_in_progress'
        ]);

        $this->setupDataForActivation($bankingAccount);

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $dataToReplace = [
          'request' => [
              'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
          ]
        ];

        $this->mockFundAccountService();

        $expectedHubspotCall = false;
        $this->mockHubspotAndAssertForChangeEvent($expectedHubspotCall);

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(1))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $this->mockCardVault(function ()
        {
            return [
                    'success' => true,
                    'token'   => 'random'
            ];
        });

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
                                                                    Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::ACTIVATED, $bankingAccount['status']);

        $bankingAccountStatementDetails = $this->getDbLastEntity(Table::BANKING_ACCOUNT_STATEMENT_DETAILS);

        $this->assertNotNull($bankingAccountStatementDetails);

        $this->assertEquals(BasDetails\Status::ACTIVE, $bankingAccountStatementDetails->getStatus());

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals(ActivationDetail\Entity::OPS, $bankingAccountActivationDetail['assignee_team']);

        $this->assertTrue($expectedHubspotCall);

        $balance = $this->getDbLastEntity('balance');

        $this->assertEquals('rbl', $balance[RZP\Models\Merchant\Balance\Entity::CHANNEL]);

        $this->assertEquals('direct', $balance[RZP\Models\Merchant\Balance\Entity::ACCOUNT_TYPE]);

        $this->assertEquals($balance[RZP\Models\Merchant\Balance\Entity::ID],
                            $bankingAccount[RZP\Models\BankingAccount\Entity::BALANCE_ID]);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);

        $request  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($request);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('activated', $logs['items'][1]['status']);
        $this->assertEquals(BankingAccount\Status::UPI_CREDS_PENDING, $logs['items'][1]['sub_status']);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);
        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);

        $contact = $this->getDbLastEntity('contact')->toArray();

        $this->assertEquals($contact['type'], Contact\Type::RZP_FEES);
        $this->assertEquals($contact['active'], true);
        $this->assertEquals($contact['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($contact['name'], config('banking_account.razorpayx_fee_details.name'));

        $fundAccount = $this->getDbLastEntity('fund_account')->toArray();

        $this->assertEquals($fundAccount['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($fundAccount['source_type'], 'contact');
        $this->assertEquals($fundAccount['source_id'], $contact['id']);
        $this->assertEquals($fundAccount['active'], true);

        $account = $this->getDbLastEntity('bank_account')->toArray();

        $this->assertEquals($account['account_number'], config('banking_account.razorpayx_fee_details.rbl.account_number'));
        $this->assertEquals($account['name'], config('banking_account.razorpayx_fee_details.name'));
        $this->assertEquals($account['ifsc'], config('banking_account.razorpayx_fee_details.rbl.ifsc'));
        $this->assertEquals($account['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($account['entity_id'], $contact['id']);

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        // Every activated merchant should have a default schedule task for fee recovery purposes.
        $this->assertEquals($scheduleTask['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($scheduleTask['entity_id'], $balance['id']);
        $this->assertEquals($scheduleTask['entity_type'], 'balance');
        $this->assertEquals($scheduleTask['schedule_id'], $schedule['id']);

        $counter = $this->getDbLastEntity('counter')->toArray();

        // Counter creation check
        $this->assertEquals($counter['balance_id'], $balance['id']);
        $this->assertEquals($counter['account_type'], $balance['account_type']);

        $feature = $this->getLastEntity('feature', true);

        $this->assertEquals($feature['name'], 'enable_ip_whitelist');
        $this->assertEquals($feature['entity_id'], '10000000000000');

        Mail::assertNotQueued(Activated::class);

        Mail::assertQueued(ActivationMails\StatusChange::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            return $mail->hasTo($bankingAccount->spocs()->first()['email']);
        });

        // reset mock
        $this->getXSegmentMock();
    }

    public function testActivateWithLedgerShadow()
    {
        $this->enableRazorXTreatmentForXOnboarding('on', 'off');

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        Mail::fake();

        $this->setupBankPartnerMerchant();

        $this->mockRaven();

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        (new User())->createBankingUserForMerchant($merchantDetail->merchant['id'], [
            'contact_mobile' => '8888888888',
        ]);

        $this->testCreateActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'status'                => 'processed',
            'sub_status'            => 'api_onboarding_in_progress'
        ]);

        $this->setupDataForActivation($bankingAccount);

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $this->mockFundAccountService();

        $expectedHubspotCall = false;
        $this->mockHubspotAndAssertForChangeEvent($expectedHubspotCall);

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(1))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $this->mockCardVault(function ()
        {
            return [
                'success' => true,
                'token'   => 'random'
            ];
        });

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
            Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $bankingAccountStmtDetails = $this->getDbLastEntity('banking_account_statement_details');

        $this->assertEquals(RZP\Models\BankingAccount\Status::ACTIVATED, $bankingAccount['status']);

        $bankingAccountStatementDetails = $this->getDbLastEntity(Table::BANKING_ACCOUNT_STATEMENT_DETAILS);

        $this->assertNotNull($bankingAccountStatementDetails);

        $this->assertEquals(BasDetails\Status::ACTIVE, $bankingAccountStatementDetails->getStatus());

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals(ActivationDetail\Entity::OPS, $bankingAccountActivationDetail['assignee_team']);

        $this->assertTrue($expectedHubspotCall);

        $balance = $this->getDbLastEntity('balance');

        $this->assertEquals('rbl', $balance[RZP\Models\Merchant\Balance\Entity::CHANNEL]);

        $this->assertEquals('direct', $balance[RZP\Models\Merchant\Balance\Entity::ACCOUNT_TYPE]);

        $this->assertEquals($balance[RZP\Models\Merchant\Balance\Entity::ID],
            $bankingAccount[RZP\Models\BankingAccount\Entity::BALANCE_ID]);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);

        $request  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($request);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('activated', $logs['items'][1]['status']);
        $this->assertEquals(BankingAccount\Status::UPI_CREDS_PENDING, $logs['items'][1]['sub_status']);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);
        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);

        $contact = $this->getDbLastEntity('contact')->toArray();

        $this->assertEquals($contact['type'], Contact\Type::RZP_FEES);
        $this->assertEquals($contact['active'], true);
        $this->assertEquals($contact['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($contact['name'], config('banking_account.razorpayx_fee_details.name'));

        $fundAccount = $this->getDbLastEntity('fund_account')->toArray();

        $this->assertEquals($fundAccount['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($fundAccount['source_type'], 'contact');
        $this->assertEquals($fundAccount['source_id'], $contact['id']);
        $this->assertEquals($fundAccount['active'], true);

        $account = $this->getDbLastEntity('bank_account')->toArray();

        $this->assertEquals($account['account_number'], config('banking_account.razorpayx_fee_details.rbl.account_number'));
        $this->assertEquals($account['name'], config('banking_account.razorpayx_fee_details.name'));
        $this->assertEquals($account['ifsc'], config('banking_account.razorpayx_fee_details.rbl.ifsc'));
        $this->assertEquals($account['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($account['entity_id'], $contact['id']);

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        // Every activated merchant should have a default schedule task for fee recovery purposes.
        $this->assertEquals($scheduleTask['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($scheduleTask['entity_id'], $balance['id']);
        $this->assertEquals($scheduleTask['entity_type'], 'balance');
        $this->assertEquals($scheduleTask['schedule_id'], $schedule['id']);

        $counter = $this->getDbLastEntity('counter')->toArray();

        // Counter creation check
        $this->assertEquals($counter['balance_id'], $balance['id']);
        $this->assertEquals($counter['account_type'], $balance['account_type']);

        Mail::assertNotQueued(Activated::class);

        Mail::assertQueued(ActivationMails\StatusChange::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            return $mail->hasTo($bankingAccount->spocs()->first()['email']);
        });

        $testFeaturesArray = $this->getDbEntity('feature',
            [
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant'
            ])->pluck('name')->toArray();

        // Assert that the da_ledger_journal_writes feature is enabled
        $this->assertContains('da_ledger_journal_writes', $testFeaturesArray);

        // assert ledger sns request
        for ($index = 0; $index<count($ledgerSnsPayloadArray); $index++)
        {
            $ledgerRequestPayload = $ledgerSnsPayloadArray[$index];

            $this->assertEquals('10000000000000', $ledgerRequestPayload['merchant_id']);
            $this->assertEquals('X', $ledgerRequestPayload['tenant']);
            $this->assertEquals('direct_merchant_onboarding', $ledgerRequestPayload['event']['name']);
            $this->assertEquals($bankingAccountStmtDetails->getPublicId(), $ledgerRequestPayload['event']['entities']['banking_account_stmt_detail_id'][0]);
        }
    }

    public function testMerchantHasKeyAccessWithCaActivatedAndWithoutKyc()
    {
        Mail::fake();

        $this->setupBankPartnerMerchant();

        $this->testData[__FUNCTION__] = $this->testData['testActivateWithoutKYC'];

        $this->mockRaven();

        $attribute = ['activation_status' => 'deactivated' , 'business_website' => 'www.businesswebsite.com'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        (new User())->createBankingUserForMerchant($merchantDetail->merchant['id'], [
            'contact_mobile' => '8888888888',
        ]);

        $this->testCreateActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'status'                => RZP\Models\BankingAccount\Status::PROCESSED,
            'sub_status'            => RZP\Models\BankingAccount\Status::API_ONBOARDING_IN_PROGRESS
        ]);

        $this->setupDataForActivation($bankingAccount);

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $this->mockFundAccountService();

        $expectedHubspotCall = false;
        $this->mockHubspotAndAssertForChangeEvent($expectedHubspotCall);

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(1))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $this->mockCardVault(function ()
        {
            return [
                'success' => true,
                'token'   => 'random'
            ];
        });

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
            Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertTrue($bankingAccount->merchant->getHasKeyAccess());
    }

    public function testActivateWithoutKYC()
    {
        Mail::fake();

        $this->mockRaven();

        $this->setupBankPartnerMerchant();

        $attribute = ['activation_status' => 'deactivated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        (new User())->createBankingUserForMerchant($merchantDetail->merchant['id'], [
            'contact_mobile' => '8888888888',
        ]);

        $this->testCreateActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'status'                => RZP\Models\BankingAccount\Status::PROCESSED,
            'sub_status'            => RZP\Models\BankingAccount\Status::API_ONBOARDING_IN_PROGRESS
        ]);

        $this->setupDataForActivation($bankingAccount);

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $this->mockFundAccountService();

        $expectedHubspotCall = false;
        $this->mockHubspotAndAssertForChangeEvent($expectedHubspotCall);

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(1))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $this->mockCardVault(function ()
        {
            return [
                'success' => true,
                'token'   => 'random'
            ];
        });

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
                                                                    Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::ACTIVATED, $bankingAccount['status']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals(ActivationDetail\Entity::OPS, $bankingAccountActivationDetail['assignee_team']);

        $this->assertTrue($expectedHubspotCall);

        $balance = $this->getDbLastEntity('balance');

        $this->assertEquals('rbl', $balance[RZP\Models\Merchant\Balance\Entity::CHANNEL]);

        $this->assertEquals('direct', $balance[RZP\Models\Merchant\Balance\Entity::ACCOUNT_TYPE]);

        $this->assertEquals($balance[RZP\Models\Merchant\Balance\Entity::ID],
                            $bankingAccount[RZP\Models\BankingAccount\Entity::BALANCE_ID]);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);

        $request  = [
            'url'     => '/banking_accounts/activation/' . 'bacc_' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($request);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('activated', $logs['items'][1]['status']);
        $this->assertEquals(BankingAccount\Status::UPI_CREDS_PENDING, $logs['items'][1]['sub_status']);

        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);
        $this->assertNotNull($bankingAccount[RZP\Models\BankingAccount\Entity::FTS_FUND_ACCOUNT_ID]);

        $contact = $this->getDbLastEntity('contact')->toArray();

        $this->assertEquals($contact['type'], Contact\Type::RZP_FEES);
        $this->assertEquals($contact['active'], true);
        $this->assertEquals($contact['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($contact['name'], config('banking_account.razorpayx_fee_details.name'));

        $fundAccount = $this->getDbLastEntity('fund_account')->toArray();

        $this->assertEquals($fundAccount['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($fundAccount['source_type'], 'contact');
        $this->assertEquals($fundAccount['source_id'], $contact['id']);
        $this->assertEquals($fundAccount['active'], true);

        $account = $this->getDbLastEntity('bank_account')->toArray();

        $this->assertEquals($account['account_number'], config('banking_account.razorpayx_fee_details.rbl.account_number'));
        $this->assertEquals($account['name'], config('banking_account.razorpayx_fee_details.name'));
        $this->assertEquals($account['ifsc'], config('banking_account.razorpayx_fee_details.rbl.ifsc'));
        $this->assertEquals($account['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($account['entity_id'], $contact['id']);

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        // Every activated merchant should have a default schedule task for fee recovery purposes.
        $this->assertEquals($scheduleTask['merchant_id'], $merchantDetail->merchant['id']);
        $this->assertEquals($scheduleTask['entity_id'], $balance['id']);
        $this->assertEquals($scheduleTask['entity_type'], 'balance');
        $this->assertEquals($scheduleTask['schedule_id'], $schedule['id']);

        $counter = $this->getDbLastEntity('counter')->toArray();

        // Counter creation check
        $this->assertEquals($counter['balance_id'], $balance['id']);
        $this->assertEquals($counter['account_type'], $balance['account_type']);

        Mail::assertNotQueued(Activated::class);

        Mail::assertQueued(ActivationMails\StatusChange::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            return $mail->hasTo($bankingAccount->spocs()->first()['email']);
        });
    }

    public function testActivateFailedDueToMozartGatewayException()
    {
        Mail::fake();

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
        ]);

        $this->setupDataForActivation($bankingAccount);

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $this->mockFundAccountService();

        $this->mockCardVault(function ()
        {
            return [
                'success' => true,
                'token'   => 'random'
            ];
        });

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(0))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $errorContent = [
            'error' => [
                'description' => '',
                'gateway_error_code' => '',
                'gateway_error_description' => \RZP\Services\Mozart::NO_ERROR_MAPPING_DESCRIPTION,
                'gateway_status_code' => 200,
                'internal_error_code' => 'GATEWAY_ERROR_UNKNOWN_ERROR'
            ],
            'data' => [
                'httpCode' => "401",
                "httpMessage" => "Unauthorized",
                'moreInformation' => 'Unauthorized Request',
            ]
        ];

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'description' => RblProcessor::GATEWAY_ERROR_PREFIX . $errorContent['data']['moreInformation']
                    ]
                ]
            ]
        ];

        $exception = new \RZP\Exception\GatewayErrorException('GATEWAY_ERROR_AUTHENTICATION_FAILED',
                                                              $errorContent['error']['gateway_error_code'],
                                                              $errorContent['error']['gateway_error_description'],
                                                              $errorContent);

        $this->setMozartMockResponseException($exception);

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testActivateFailedDueToFtsFailure()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' .  $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->setupDataForActivation($bankingAccount);

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(0))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
            Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->mockFundAccountService(function ()
        {
            throw new \Exception();
        });

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testActivateFailedDueToFtsDirectAccountCreationValidationFailure()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' .  $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->setupDataForActivation($bankingAccount);

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(0))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' .
            Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $internalErrorCode = \RZP\Error\ErrorCode::BAD_REQUEST_ERROR_DIRECT_FUND_ACCOUNT_AND_SOURCE_ACCOUNT_CREATION_VALIDATION_FAILED;

       $this->mockFundAccountService(function ()
        {
            return [
                'body' => [
                    "internal_error" => [
                        "code"      => "VALIDATION_ERROR",
                        "sub_code"  => 0
                    ],
                    "public_error" => [
                        "code"      => "BAD_REQUEST_ERROR",
                        "message"   => "invalid request sent"
                    ]
                ],
                "code" => 400
            ];
        });

        $endUserErrorDescription = 'Operation failed. FTS Account could not stored because of a validation error: '.'VALIDATION_ERROR' ;

        $this->testData[__FUNCTION__]['response']['content']['error']['description'] = $endUserErrorDescription;

        $this->testData[__FUNCTION__]['exception']['internal_error_code'] = $internalErrorCode;

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testActivateFailedDueToMissingData()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->createBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate'
            ]
        ];

        $xsegmentMock = $this->getXSegmentMock();
        $xsegmentMock->expects($this->exactly(0))
            ->method('pushIdentifyandTrackEvent')->willReturn(true);

        $this->mockCardVault(function ()
        {
            return [];
        });

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function updateBankingAccount(Entity $bankingAccount, array $attrs)
    {
        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount->getPublicId(),
                'content' => $attrs
            ],
            'response' => [
                'content' => [
                    'id' => $bankingAccount->getPublicId(),
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testupdateBankingAccountWithCommentViaMobWithAdminContext()
    {

        $bankingAccount = $this->createBankingAccount();

        Queue::fake();

        $admin = $this->fixtures->create('admin', ['org_id' => Org::RZP_ORG, 'email' => 'abc@razorpay.com']);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_lms_mob/' . $bankingAccount['id'],
                'content' => [
                    Entity::ACTIVATION_DETAIL                         => [
                        'comment' => 'test'
                    ],
                ],
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail(),
                ]
            ],
            'response' => [
                'content' => [
                    'id' => $bankingAccount['id'],
                ],
            ],
        ];

        $this->ba->mobAppAuthForInternalRoutes();

        $this->startTest($dataToReplace);

        Queue::assertNotPushed(BankingAccountNotifyMob::class);
    }

    public function getStatusChangeLog(Entity $bankingAccount)
    {
        $request  = [
            'url'     => '/banking_accounts/activation/' . $bankingAccount->getPublicId() . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($request);

        return $logs;
    }

    public function testStatusLastUpdatedAt()
    {
        $this->ba->proxyAuth();

        $this->testCreateBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->updateBankingAccount($bankingAccount, [
            'status' => Status::PICKED
        ]);

        $logs = $this->getStatusChangeLog($bankingAccount);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(end($logs['items'])[Entity::CREATED_AT],
            $bankingAccount->getStatusLastUpdatedAt());

        // updating substatus should not affect the status last updated at
        $this->updateBankingAccount($bankingAccount, [
            'sub_status' => Status::MERCHANT_NOT_AVAILABLE
        ]);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(end($logs['items'])[Entity::CREATED_AT],
            $bankingAccount->getStatusLastUpdatedAt());
    }

    public function testBankingAccountActivationOpenAndLoginDateAsNull()
    {
        $this->ba->proxyAuth();

        $bankingAccount = $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccountData = [
            'activation_detail' => [
                'account_open_date' => 1109748304,
                'account_login_date' => 1109748397,
        ]];

        $this->updateBankingAccount($bankingAccount, $bankingAccountData);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals($bankingAccountData['activation_detail']['account_open_date'], $bankingAccountActivationDetail['account_open_date']);

        $this->assertEquals($bankingAccountData['activation_detail']['account_login_date'], $bankingAccountActivationDetail['account_login_date']);

        //updating of account_open_date and account_login_date to null
        $this->updateBankingAccount($bankingAccount, [
            'activation_detail' => [
                'account_open_date' => null,
                'account_login_date' => null,
            ]
        ]);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals(null, $bankingAccountActivationDetail['account_open_date']);

        $this->assertEquals(null, $bankingAccountActivationDetail['account_login_date']);
    }

    protected function assertUpdateBankingAccountStatusFromToForNeostone(string $initialStatus,
                                                              string $finalStatus,
                                                              string $initialSubStatus = null,
                                                              string $finalSubStatus = null,
                                                              string $initialBankStatus = null,
                                                              string $finalBankStatus = null,
                                                              array $bankingAccount = null)
    {
        Mail::fake();

        $this->setupBankPartnerMerchant();

        if ($bankingAccount === null)
        {
            $attribute = ['activation_status' => 'activated'];

            $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

            $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

            $this->ba->addXOriginHeader();

            $bankingAccount = $this->createBankingAccountFromDashboard();

            $this->fixtures->edit('banking_account_activation_detail',
                                  $bankingAccount['banking_account_activation_details']['id'],
                                  [
                                      ActivationDetail\Entity::CONTACT_VERIFIED => 1,
                                  ]);
        }
        else
        {
            $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $bankingAccount['merchant_id']]);
        }

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    RZP\Models\BankingAccount\Entity::STATUS     => $finalStatus,
                ]
            ],
            'response' => [
                'content' => [
                    'merchant_id'                                => $merchantDetail->merchant['id'],
                    RZP\Models\BankingAccount\Entity::STATUS     => $finalStatus,
                ],
            ],
        ];

        if (empty($finalSubStatus) === false)
        {
            $dataToReplace['request']['content'][RZP\Models\BankingAccount\Entity::SUB_STATUS] = $finalSubStatus;
            $dataToReplace['response']['content'][RZP\Models\BankingAccount\Entity::SUB_STATUS] = $finalSubStatus;
        }

        if (empty($finalBankStatus) === false)
        {
            $dataToReplace['request']['content'][RZP\Models\BankingAccount\Entity::BANK_INTERNAL_STATUS] = $finalBankStatus;
            $dataToReplace['response']['content'][RZP\Models\BankingAccount\Entity::BANK_INTERNAL_STATUS] = $finalBankStatus;
        }

        $this->ba->adminAuth();

        $hubspotClient = $this->mockHubSpotClient('trackHubspotEvent');

        $hubspotClient->expects($this->atLeast(1))
                      ->method('trackHubspotEvent');

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'],
                              [
                                  'status'               => $initialStatus,
                                  'sub_status'           => $initialSubStatus,
                                  'bank_internal_status' => $initialBankStatus
                              ]);

        $this->startTest($dataToReplace);

        $bankingAccountStateUpdate = $this->getDbLastEntity('banking_account_state');

        $this->assertEquals($bankingAccount['id'], $bankingAccountStateUpdate->bankingAccount->getPublicId());

        $this->assertEquals($finalStatus, $bankingAccountStateUpdate['status']);

        $this->assertEquals($finalSubStatus, $bankingAccountStateUpdate['sub_status']);

        $this->assertEquals($finalBankStatus, $bankingAccountStateUpdate['bank_status']);

        Mail::assertNothingSent();
    }

    public function testUpdateBankingAccountStatusProcessingToProcessedForNeostone()
    {
        $this->assertUpdateBankingAccountStatusFromToForNeostone(
            Status::PROCESSING,
            Status::PROCESSED);
    }

    public function testUpdateBankingAccountStatusCreatedToPickedForNeostone()
    {
        $this->assertUpdateBankingAccountStatusFromToForNeostone(
            Status::CREATED,
            Status::PICKED);
    }

    public function testUpdateBankingAccountStatusPickedToInitiatedForNeostone()
    {
        $this->assertUpdateBankingAccountStatusFromToForNeostone(
            Status::PICKED,
            Status::INITIATED);
    }

    public function testUpdateBankingAccountStatusWithSubStatusForNeostone()
    {
        $this->assertUpdateBankingAccountStatusFromToForNeostone(
            Status::INITIATED,
            Status::PROCESSING,
            null,
            Status::DISCREPANCY_IN_DOCS);
    }

    protected function assertUpdateBankingAccountStatusFromTo(string $initialStatus,
                                                              string $finalStatus,
                                                              string $initialSubStatus = null,
                                                              string $finalSubStatus = null,
                                                              string $initialBankStatus = null,
                                                              string $finalBankStatus = null,
                                                              array $bankingAccount = null,
                                                              string $merchantId = '10000000000000',
                                                              string $expectedStatus = null,
                                                              string $expectedSubStatus = null,
                                                              bool $expectException = false,
                                                              bool $clevertapMigrationExpEnabled = false
    )
    {
        Mail::fake();

        if ($bankingAccount === null)
        {
            $attribute = ['activation_status' => 'activated'];

            $merchantDetail = $this->fixtures->edit('merchant_detail', $merchantId, $attribute);

            $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

            $this->ba->addXOriginHeader();

            $bankingAccount = $this->createBankingAccount();
        }
        else
        {
            $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $bankingAccount['merchant_id']]);
        }

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    RZP\Models\BankingAccount\Entity::STATUS     => $finalStatus,
                ]
            ],
            'response' => [
                'content' => [
                    'merchant_id'                                => $merchantDetail->merchant['id'],
                    RZP\Models\BankingAccount\Entity::STATUS     => $expectedStatus ?? $finalStatus,
                ],
            ],
        ];

        if (empty($finalSubStatus) === false)
        {
            $dataToReplace['request']['content'][RZP\Models\BankingAccount\Entity::SUB_STATUS] = $finalSubStatus;
            $dataToReplace['response']['content'][RZP\Models\BankingAccount\Entity::SUB_STATUS] = $expectedSubStatus ?? $finalSubStatus;
        }

        if (empty($finalBankStatus) === false)
        {
            $dataToReplace['request']['content'][RZP\Models\BankingAccount\Entity::BANK_INTERNAL_STATUS] = $finalBankStatus;
            $dataToReplace['response']['content'][RZP\Models\BankingAccount\Entity::BANK_INTERNAL_STATUS] = $finalBankStatus;
        }

        $xsegmentMock = $this->getXSegmentMock();
        if ($finalBankStatus === null)
        {
            $xsegmentMock->expects($this->exactly($expectException ? 0 : 1))
                ->method('pushIdentifyandTrackEvent')->willReturn(true);
        }

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status'               => $initialStatus,
                'sub_status'           => $initialSubStatus,
                'bank_internal_status' => $initialBankStatus
            ]);

        if (($finalStatus !== $initialStatus)
            and (in_array($finalStatus, [Status::INITIATED, Status::PICKED]) === false)
            and ($finalStatus === Status::ARCHIVED
            and in_array($finalSubStatus, [
                Status::NEGATIVE_PROFILE_SVR_ISSUE,
                Status::NOT_SERVICEABLE,
                Status::CANCELLED
            ])
        ))
        {

            if (in_array($finalStatus, BankingAccountCore::$notificationStatuses, true) === true) {

                $notificationContent = BankingAccountCore::getStatusUpdatePushNotificationContent($finalStatus, $finalSubStatus);

                $pushNotificationTitle = $notificationContent[0];

                $pushNotificationBody =  $notificationContent[1];

                if (empty($pushNotificationTitle) === false && empty($pushNotificationBody) === false)
                {
                    $splitzInput = [
                        'experiment_id' => 'LvyaZT13vrxdWR',
                        'id'            => $merchantId,
                    ];

                    $splitzOutput = [
                        'response' => [
                            'variant' => [
                                'name' => $clevertapMigrationExpEnabled ? 'active' : null,
                            ]
                        ]
                    ];

                    $this->mockSplitzTreatment($splitzInput, $splitzOutput);

                    $merchant = $this->getDbEntity('merchant', ['id' => $bankingAccount['merchant_id']]);

                    $this->mockStork();

                    $ownerId = $merchant->getId();
                    $ownerType = 'merchant';
                    if($clevertapMigrationExpEnabled)
                    {
                        $ownerId = 'MerchantUser01';
                        $ownerType = 'user';
                    }

                    $this->expectStorkSendPushNotificationRequest([
                        'ownerId' => $ownerId,
                        'ownerType' => $ownerType,
                        'title' => $pushNotificationTitle,
                        'body' => $pushNotificationBody
                    ]);
                }
            }
        }

        $this->startTest($dataToReplace);

        // reset mock
        $this->getXSegmentMock();

        $updatedBankingAccount = $this->getDbEntityById('banking_account', $bankingAccount['id']);

        $bankingAccountStateUpdate = $this->getDbLastEntity('banking_account_state');

        $this->assertEquals($bankingAccount['id'], $bankingAccountStateUpdate->bankingAccount->getPublicId());

        $this->assertEquals($expectedStatus ?? $finalStatus, $bankingAccountStateUpdate['status']);

        $this->assertEquals($merchantId, $bankingAccountStateUpdate['merchant_id']);

        $this->assertEquals($expectedSubStatus ?? $finalSubStatus, $bankingAccountStateUpdate['sub_status']);

        $this->assertEquals($finalBankStatus, $bankingAccountStateUpdate['bank_status']);

        if (($finalStatus !== $initialStatus)
            and (in_array($finalStatus, [Status::INITIATED, Status::PICKED]) === false)
            and ($finalStatus === Status::ARCHIVED
            and in_array($finalSubStatus, [
                Status::NEGATIVE_PROFILE_SVR_ISSUE,
                Status::NOT_SERVICEABLE,
                Status::CANCELLED
            ])
        ))
        {
            // Check notifyMerchantAboutUpdatedStatus
//            $mailableClass = RZP\Mail\BankingAccount\StatusNotifications\Factory::getMailer($updatedBankingAccount);
//
//            Mail::assertQueued(get_class($mailableClass));
            Mail::assertNothingSent();
        }
        else
        {
            Mail::assertNothingSent();
        }
    }

    public function mockBankingAccountService()
    {
        $this->app['config']->set('applications.banking_account_service.mock', true);
    }

    protected function expectStorkSendPushNotificationRequest($expectInput): void
    {
        $this->storkMock
            ->shouldReceive('init')
            ->times(1);

        $this->storkMock
            ->shouldReceive('requestAndGetParsedBody')
            ->times(1)
            ->with(
                Mockery::on(function ($route)
                {
                    return true;
                }),
                Mockery::on(function ($params) use ($expectInput)
                {

                    $title = $params['message']['push_notification_channels'][0]['push_notification_request']['target_user_campaign_request']['content_title'];
                    $body = $params['message']['push_notification_channels'][0]['push_notification_request']['target_user_campaign_request']['content_body'];

                    $this->assertEquals($expectInput['ownerId'], $params['message']['owner_id']);
                    $this->assertEquals($expectInput['ownerType'], $params['message']['owner_type']);
                    $this->assertEquals($expectInput['title'], $title);
                    $this->assertEquals($expectInput['body'], $body);

                    $this->assertEquals('razorpayx', $params['message']['push_notification_channels'][0]['push_notification_request']['account_name']);
                    $this->assertEquals(0, $params['message']['push_notification_channels'][0]['push_notification_request']['push_notification_type']);

                    return true;
                })
            )
            ->andReturnUsing(function ()
            {
                return [
                    'success' => true
                ];
            });
    }


    public function testUpdateBankingAccountStatusProcessingToProcessed()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PROCESSING,
            Status::PROCESSED);
    }

    public function testUpdateBankingAccountStatusCreatedToProcessed()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::CREATED,
            Status::PROCESSED);
    }

    public function testUpdateBankingAccountStatusCreatedToArchived()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::CREATED,
            Status::ARCHIVED);
    }

    public function testUpdateBankingAccountStatusCreatedToTerminated()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::CREATED,
            Status::TERMINATED
        );
    }

    public function testUpdateBankingAccountStatusCreatedToArchivedWithClevertapMigrationExpEnabled()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::CREATED,
            Status::ARCHIVED,
            null,
            Status::NOT_SERVICEABLE,
            null,null,null,'10000000000000',null,null,false,
            true);
    }

    public function testUpdateBankingAccountStatusPickedToProcessed()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PROCESSED);
    }

    public function testUpdateBankingAccountStatusInitiatedToProcessed()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED,
            Status::PROCESSED);
    }

    public function testUpdateBankingAccountStatusUnservicableToPicked()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::UNSERVICEABLE,
            Status::PICKED,
            null,
            Status::NONE);
    }

    public function testUpdateBankingAccountStatusCancelledToPicked()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::CANCELLED,
            Status::PICKED);
    }

    public function testUpdateBankingAccountStatusProcessedToArchived()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PROCESSED,
            Status::ARCHIVED,
            null,
            Status::CANCELLED);
    }

    public function testUpdateBankingAccountStatusArchivedToProcessed()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::ARCHIVED,
            Status::PROCESSED);
    }

    public function testUpdateBankingAccountStatusArchivedToTerminated()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::ARCHIVED,
            Status::TERMINATED
        );
    }

    public function testUpdateBankingAccountStatusRejectedToProcessed()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::REJECTED,
            Status::PROCESSED);
    }

    public function testUpdateBankingAccountSubStatus()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED,
            Status::INITIATED,
            null,
            Status::MERCHANT_NOT_AVAILABLE);
    }

    public function testUpdateBankingAccountSubStatusForPendingOnSales()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            null,
            Status::PENDING_ON_SALES_DOC_WALKTHROUGH_CALL_NOT_SCHEDULED);
    }

    public function testUpdateBankingAccountSubStatusForNotPendingOnSales()
    {

        $activationDetails = [
            'activation_detail' => [
                'merchant_poc_name' => 'Umakant',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'business_category' => 'limited_liability_partnership',
                'merchant_documents_address' => 'x, y, z',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
            ]
        ];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            Status::PENDING_ON_SALES_DOC_WALKTHROUGH_CALL_NOT_SCHEDULED,
            Status::MERCHANT_NOT_AVAILABLE,
            null,
            null,
            $bankingAccount);
    }

    public function testUpdateBankingAccountSubStatusForDocsWalkThrough()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            null,
            Status::DOCS_WALK_THROUGH_PENDING);
    }

    public function testUpdateBankingAccountSubStatusForDWTNotCompleted()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            null,
            Status::PENDING_ON_SALES_DWT_NOT_COMPLETED_MX_NOT_RESPONDING_SPOC_TO_RESCHEDULE);
    }

    public function testUpdateBankingAccountSubStatusForUnsupportedBusinessType()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            null,
            Status::PENDING_ON_SALES_UNSUPPORTED_MISMATCH_OF_BIZ_TYPE_ON_ADMIN_DASHBOARD_AND_LMS);
    }

    public function testUpdateBankingAccountSubStatusForNeedClarification()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            null,
            Status::NEEDS_CLARIFICATION_FROM_SALES);
    }

    public function testUpdateBankingAccountSubStatusForNeedClarificationfromRZP()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED,
            Status::INITIATED,
            null,
            Status::NEEDS_CLARIFICATION_FROM_RZP);
    }

    public function testUpdateBankingAccountStatusWithSubStatus()
    {
        $this->setupBankPartnerMerchant();

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::INITIATED,
            null,
            Status::MERCHANT_NOT_AVAILABLE);
    }

    public function testUpdateBankingAccountStatusWithInvalidSubStatus()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED,
            Status::PROCESSING,
            null,
            Status::MERCHANT_NOT_AVAILABLE,
            null,
            null,
            null,
            '10000000000000',
            null,
            null,
            true
        );
    }

    public function testUpdateBankingAccountStatusWithBlockedSubstatusMapping()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::ARCHIVED,
            Status::READY_TO_SEND_TO_BANK,
            null,
            null,
            null,
            null,
            '10000000000000',
            null,
            null,
            true
        );
    }

    public function testUpdateBankingAccountStatusWithNoneSubStatus()
    {
        $this->setupBankPartnerMerchant();

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::INITIATED,
            Status::MERCHANT_NOT_AVAILABLE,
            Status::NONE);
    }

    public function testUpdateBankingAccountSubStatusFromDocketInitiatedToDdInProgressFailsDueToMissingTrackingId()
    {
        $bankingAccount = $this->fixtures->on('test')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->on('test')->create('banking_account_activation_detail', [
            'banking_account_id'        => $bankingAccount->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => 'sme',
            'additional_details'        => json_encode([
                'docket_estimated_delivery_date' => 1575912600
            ])
        ]);

        $ba = $bankingAccount->toArray();
        $ba['id'] = 'bacc_' . $ba['id'];

        $this->expectException(\RZP\Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Both Tracking Id and Docket Estimated Delivery Date needs to be filled');

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            Status::DOCKET_INITIATED,
            Status::DD_IN_PROGRESS,
            "",
            "",
            $ba,
            $bankingAccount->getMerchantId(),
            null,
            null,
            true
        );
    }

    public function testUpdateBankingAccountSubStatusFromDocketInitiatedToDdInProgress()
    {
        $bankingAccount = $this->fixtures->on('test')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->on('test')->create('banking_account_activation_detail', [
            'banking_account_id'        => $bankingAccount->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => 'sme',
            'additional_details'        => json_encode([
                'docket_estimated_delivery_date' => 1575912600,
                'tracking_id'                    => 'TRACKING_ID'
            ])
        ]);

        $ba = $bankingAccount->toArray();
        $ba['id'] = 'bacc_' . $ba['id'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            Status::DOCKET_INITIATED,
            Status::DD_IN_PROGRESS,
            "",
            "",
            $ba,
            $bankingAccount->getMerchantId());
    }

    public function testUpdateBankingAccountSubStatusFromNoneToDwtRequired()
    {
        $bankingAccount = $this->fixtures->on('test')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->on('test')->create('banking_account_activation_detail', [
            'banking_account_id'        => $bankingAccount->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'merchant_documents_address' => 'ADDRESS',
            'sales_team'                => 'sme',
        ]);

        $ba = $bankingAccount->toArray();
        $ba['id'] = 'bacc_' . $ba['id'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            Status::NONE,
            Status::DWT_REQUIRED,
            "",
            "",
            $ba,
            $bankingAccount->getMerchantId());
    }

    public function testUpdateBankingAccountDocketInitiation()
    {
        Mail::fake();

        $attribute = [
            'activation_status' => 'activated',
            'business_type'     => 4, // Merchant\Detail::PRIVATE_LIMITED
        ];

        $merchantId = '10000000000000';

        $merchantDetail = $this->fixtures->edit('merchant_detail', $merchantId, $attribute);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'name'  => 'Merchant Name',
        ]);

        $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'skip_dwt_eligible', 'enabled');
        // $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'ca_onboarding_flow', 'ONE_CA');

        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name'                  => 'Umakant',
                'merchant_poc_designation'           => 'Financial Consultant',
                'merchant_poc_email'                 => 'sample@sample.com',
                'merchant_poc_phone_number'          => '9876556789',
                'business_category'                  => ActivationDetail\Validator::PRIVATE_PUBLIC_LIMITED_COMPANY,
                'merchant_documents_address'         => 'x, y, z',
                'sales_team'                         => 'sme',
                'sales_poc_id'                       => 'admin_'. Org::SUPER_ADMIN,
                'initial_cheque_value'               => 100,
                'account_type'                       => 'insignia',
                'merchant_city'                      => 'Bangalore',
                'is_documents_walkthrough_complete'  => true,
                'merchant_region'                    => 'South',
                'expected_monthly_gmv'               => 10000,
                'average_monthly_balance'            => 0,
                'additional_details'                 => [
                    'verified_constitutions' => [
                        [
                            'constitution' => 'PUBLIC_LIMITED',
                            'source'       => 'gstin'
                        ],
                    ],
                    'rbl_new_onboarding_flow_declarations' => [
                        'available_at_preferred_address_to_collect_docs' => 1,
                        'seal_available' => 1,
                        'signatories_available_at_preferred_address' => 1,
                        'signboard_available' => 1,
                    ]
                ]
            ]
        ];

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($bankingAccount);

        $bankingAccountActivationDetail = $bankingAccount['banking_account_activation_details'];

        $bankingAccountActivationDetailEntity = $this->fixtures->edit('banking_account_activation_detail', $bankingAccountActivationDetail['id'], [
            ActivationDetail\Entity::BUSINESS_NAME      => 'Merchant Name', // must be same as merchant name
        ]);

        // Equivalent of submiting Sales-form
        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'declaration_step'                   => 1,
                    'additional_details'                 => [
                        'business_details' => [
                            'category' => 'financial_services',
                            'sub_category' => 'lending',
                        ]
                    ]
                ]
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(Status::PICKED, $response[BankingAccount\Entity::STATUS]);
        $this->assertEquals(Status::DOCKET_INITIATED, $response[BankingAccount\Entity::SUB_STATUS]);

        Mail::assertQueued(DocketMail::class);
    }

    public function testUpdateBankingAccountDocketInitiationNegativeMerchantNameMatch()
    {
        $this->fixtures->create('merchant', [
            'id'    => '10000000000001',
            'name'  => 'Merchant Name',
        ]);

        $this->fixtures->create('merchant_detail:sane', [
            'merchant_id'       => '10000000000001',
            'business_type'     => 1,
            'contact_name'      => 'Merchant Name',
            'contact_mobile'    => '8888888888',
            'activation_status' => null
        ]);

        $this->fixtures->create('banking_account', [
            'channel'               => 'rbl',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000001',
            'status'                => 'picked'
        ]);

        Mail::fake();

        $attribute = [
            'activation_status' => 'activated',
            'business_type'     => 4, // Merchant\Detail::PRIVATE_LIMITED
        ];

        $merchantId = '10000000000000';

        $merchantDetail = $this->fixtures->edit('merchant_detail', $merchantId, $attribute);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'name'  => 'Merchant Name',
        ]);

        $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'skip_dwt_eligible', 'enabled');
        // $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'ca_onboarding_flow', 'ONE_CA');

        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name'                  => 'Umakant',
                'merchant_poc_designation'           => 'Financial Consultant',
                'merchant_poc_email'                 => 'sample@sample.com',
                'merchant_poc_phone_number'          => '9876556789',
                'business_category'                  => ActivationDetail\Validator::PRIVATE_PUBLIC_LIMITED_COMPANY,
                'merchant_documents_address'         => 'x, y, z',
                'sales_team'                         => 'sme',
                'sales_poc_id'                       => 'admin_'. Org::SUPER_ADMIN,
                'initial_cheque_value'               => 100,
                'account_type'                       => 'insignia',
                'merchant_city'                      => 'Bangalore',
                'is_documents_walkthrough_complete'  => true,
                'merchant_region'                    => 'South',
                'expected_monthly_gmv'               => 10000,
                'average_monthly_balance'            => 0,
                'additional_details'                 => [
                    'verified_constitutions' => [
                        [
                            'constitution' => 'PUBLIC_LIMITED',
                            'source'       => 'gstin'
                        ],
                    ],
                    'rbl_new_onboarding_flow_declarations' => [
                        'available_at_preferred_address_to_collect_docs' => 1,
                        'seal_available' => 1,
                        'signatories_available_at_preferred_address' => 1,
                        'signboard_available' => 1,
                    ]
                ]
            ]
        ];

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($bankingAccount);

        $bankingAccountActivationDetail = $bankingAccount['banking_account_activation_details'];

        $bankingAccountActivationDetailEntity = $this->fixtures->edit('banking_account_activation_detail', $bankingAccountActivationDetail['id'], [
            ActivationDetail\Entity::BUSINESS_NAME      => 'Merchant Name', // must be same as merchant name
        ]);

        // Equivalent of submiting Sales-form
        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'declaration_step'                   => 1,
                    'additional_details'                 => [
                        'business_details' => [
                            'category' => 'financial_services',
                            'sub_category' => 'lending',
                        ]
                    ]
                ]
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals([
            BankingAccount\Entity::STATUS           => Status::PICKED,
            BankingAccount\Entity::SUB_STATUS       => Status::INITIATE_DOCKET,
            BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS => [
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::SENT_DOCKET_AUTOMATICALLY => false,
                    ActivationDetail\Entity::REASONS_TO_NOT_SEND_DOCKET => [
                        'Application with Duplicate Merchant Name'
                    ],
                ]
            ]
        ], $response);

        Mail::assertNotQueued(DocketMail::class);
    }

    public function testUpdateBankingAccountDocketInitiationNegativeBusinessTypeMismatch()
    {
        Mail::fake();

        $attribute = [
            'activation_status' => 'activated',
            'business_type'     => 2
        ];

        $merchantId = '10000000000000';

        $merchantDetail = $this->fixtures->edit('merchant_detail', $merchantId, $attribute);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'name'  => 'Merchant Name',
        ]);

        $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'skip_dwt_eligible', 'enabled');
        // $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'ca_onboarding_flow', 'ONE_CA');

        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name'                  => 'Umakant',
                'merchant_poc_designation'           => 'Financial Consultant',
                'merchant_poc_email'                 => 'sample@sample.com',
                'merchant_poc_phone_number'          => '9876556789',
                'business_category'                  => ActivationDetail\Validator::PRIVATE_PUBLIC_LIMITED_COMPANY,
                'merchant_documents_address'         => 'x, y, z',
                'sales_team'                         => 'sme',
                'sales_poc_id'                       => 'admin_'. Org::SUPER_ADMIN,
                'initial_cheque_value'               => 100,
                'account_type'                       => 'insignia',
                'merchant_city'                      => 'Bangalore',
                'is_documents_walkthrough_complete'  => true,
                'merchant_region'                    => 'South',
                'expected_monthly_gmv'               => 10000,
                'average_monthly_balance'            => 0,
                'additional_details'                 => [
                    'verified_constitutions' => [
                        [
                            'constitution' => 'PUBLIC_LIMITED',
                            'source'       => 'gstin'
                        ],
                    ],
                    'rbl_new_onboarding_flow_declarations' => [
                        'available_at_preferred_address_to_collect_docs' => 1,
                        'seal_available' => 1,
                        'signatories_available_at_preferred_address' => 1,
                        'signboard_available' => 1,
                    ]
                ]
            ]
        ];

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($bankingAccount);

        $bankingAccountActivationDetail = $bankingAccount['banking_account_activation_details'];

        $bankingAccountActivationDetailEntity = $this->fixtures->edit('banking_account_activation_detail', $bankingAccountActivationDetail['id'], [
            ActivationDetail\Entity::BUSINESS_NAME      => 'Not Merchant Name', // must be same as merchant name
        ]);

        // Equivalent of submiting Sales-form
        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'declaration_step'                   => 1,
                    'additional_details'                 => [
                        'business_details' => [
                            'category' => 'financial_services',
                            'sub_category' => 'lending',
                        ]
                    ]
                ]
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals([
            BankingAccount\Entity::STATUS           => Status::PICKED,
            BankingAccount\Entity::SUB_STATUS       => Status::INITIATE_DOCKET,
            BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS => [
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::SENT_DOCKET_AUTOMATICALLY => false,
                    ActivationDetail\Entity::REASONS_TO_NOT_SEND_DOCKET => [
                        'Entity Name Mismatch',
                        'Entity Type Mismatch'
                    ],
                ]
            ]
        ], $response);

        Mail::assertNotQueued(DocketMail::class);
    }

    public function testUpdateBankingAccountDocketInitiatedToSentToBank()
    {
        $response = $this->setupBankLMSTest();

        $user = $response['user'];

        $bankingAccount = $response['bankingAccount'];

        $this->fixtures->edit('banking_account', $bankingAccount['id'], [
            BankingAccount\Entity::STATUS       => Status::PICKED,
            BankingAccount\Entity::SUB_STATUS   => Status::DOCKET_INITIATED,
        ]);

        $this->ba->adminAuth();

        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    ActivationDetail\Entity::MERCHANT_POC_NAME                              => 'Umakant',
                    ActivationDetail\Entity::MERCHANT_POC_DESIGNATION                       => 'Financial Consultant',
                    ActivationDetail\Entity::MERCHANT_POC_EMAIL                             => 'sample@sample.com',
                    ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER                      => '9876556789',
                    ActivationDetail\Entity::BUSINESS_CATEGORY                              => ActivationDetail\Validator::PRIVATE_PUBLIC_LIMITED_COMPANY,
                    ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS                     => 'x, y, z',
                    ActivationDetail\Entity::SALES_TEAM                                     => 'sme',
                    ActivationDetail\Entity::SALES_POC_ID                                   => 'admin_'. Org::SUPER_ADMIN,
                    ActivationDetail\Entity::SALES_POC_PHONE_NUMBER                         => 'admin_'. Org::SUPER_ADMIN,
                    ActivationDetail\Entity::INITIAL_CHEQUE_VALUE                           => 100,
                    ActivationDetail\Entity::ACCOUNT_TYPE                                   => 'insignia',
                    ActivationDetail\Entity::MERCHANT_CITY                                  => 'Bangalore',
                    ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE              => true,
                    ActivationDetail\Entity::MERCHANT_REGION                                => 'South',
                    ActivationDetail\Entity::EXPECTED_MONTHLY_GMV                           => 10000,
                    ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE                        => 0,
                    ActivationDetail\Entity::ADDITIONAL_DETAILS                             => [
                        'verified_constitutions' => [
                            [
                                'constitution' => 'PUBLIC_LIMITED',
                                'source'       => 'gstin'
                            ],
                        ],
                        ActivationDetail\Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS => [
                            ActivationDetail\Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS     => 1,
                            ActivationDetail\Entity::SEAL_AVAILABLE                                     => 1,
                            ActivationDetail\Entity::SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS         => 1,
                            ActivationDetail\Entity::SIGNBOARD_AVAILABLE                                => 1,
                        ],
                        ActivationDetail\Entity::DOCKET_DELIVERED_DATE => 1666204200
                    ]
                ]
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals([
            BankingAccount\Entity::STATUS           => Status::INITIATED,
            BankingAccount\Entity::SUB_STATUS       => Status::NONE,
        ], $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $request  = [
            'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccount['id'],
            'method'  => 'GET',
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['id'], $bankingAccount['id']);
    }

    public function testUpdateBankingAccountSubStatusFromDwtRequiredToDwtCompletedFailsDueToMissingDwtCompletedTimestamp()
    {
        $bankingAccount = $this->fixtures->on('test')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->on('test')->create('banking_account_activation_detail', [
            'banking_account_id'        => $bankingAccount->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'merchant_documents_address' => 'ADDRESS',
            'sales_team'                => 'sme',
        ]);

        $this->expectException(\RZP\Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('DWT completed timestamp needs to be present');

        $ba = $bankingAccount->toArray();
        $ba['id'] = 'bacc_' . $ba['id'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            Status::DWT_REQUIRED,
            Status::DWT_COMPLETED,
            "",
            "",
            $ba,
            $bankingAccount->getMerchantId(),
            null,
            null,
            true
        );
    }

    public function testUpdateBankingAccountSubStatusFromDwtRequiredToDwtCompleted()
    {
        $bankingAccount = $this->fixtures->on('test')->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->on('test')->create('banking_account_activation_detail', [
            'banking_account_id'        => $bankingAccount->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'merchant_documents_address' => 'ADDRESS',
            'sales_team'                => 'sme',
            'additional_details'        => json_encode([
                'dwt_completed_timestamp' => 1575912600,
            ])
        ]);

        $ba = $bankingAccount->toArray();
        $ba['id'] = 'bacc_' . $ba['id'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            Status::DWT_REQUIRED,
            Status::DWT_COMPLETED,
            "",
            "",
            $ba,
            $bankingAccount->getMerchantId());
    }

    public function mockHubspotAndAssertForChangeEvent(bool &$expectedHubspotCall, bool $isStatusChange = true, bool $isSubstatusChange = true)
    {
        $hubspotMock = $this->mockHubSpotClient('trackHubspotEvent');

        $expectedStatusCall = false;
        $expectedSubStatusCall = false;
        if ($isStatusChange === true)
        {
            $hubspotMock->expects($this->atLeast(1))
                ->method('trackHubspotEvent')
                ->will($this->returnCallback(
                    function(string $merchantEmail, array $payload) use (&$expectedSubStatusCall, &$expectedStatusCall, &$expectedHubspotCall)
                    {
                        if (isset($payload['ca_onboarding_status']) === true)
                        {
                            // asserting here within the callback does not fail the test case for some reason
                            $expectedStatusCall = true;
                            $expectedHubspotCall = ($expectedStatusCall && $expectedSubStatusCall);
                        }
                    }));
        }

        if ($isSubstatusChange === true)
        {
            $hubspotMock->expects($this->atLeast(1))
                ->method('trackHubspotEvent')
                ->will($this->returnCallback(
                    function(string $merchantEmail, array $payload) use (&$expectedSubStatusCall, &$expectedStatusCall, &$expectedHubspotCall)
                    {
                        if (isset($payload['ca_onboarding_substatus']) === true)
                        {
                            // asserting here within the callback does not fail the test case for some reason
                            $expectedSubStatusCall = true;
                            $expectedHubspotCall = ($expectedStatusCall && $expectedSubStatusCall);
                        }
                    }));
        }
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    public function testSegmentEventPushForBankingAccountStatusChange()
    {
        $this->createAndFetchMocks();

        $segmentMock = $this->getMockBuilder(XSegmentClient::class)
            ->setMethods(['pushIdentifyAndTrackEvent'])
            ->getMock();

        $this->app->instance('x-segment', $segmentMock);

        $segmentMock->expects($this->exactly(1))
            ->method('pushIdentifyAndTrackEvent')
            ->willReturn(true);

        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
        ]);

        $bankingAccount = $this->getDbEntity('banking_account',
            [
                'merchant_id' => '10000000000000',
            ]
        );

        (new BankingAccount\Core)->notifyIfStatusChanged($bankingAccount->toArray(),true,false);

    }

    public function testUpdateBankingAccountStatusAsProcessed()
    {
        Mail::fake();

        $expectedHubspotCall = false;
        $this->mockHubspotAndAssertForChangeEvent($expectedHubspotCall);

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccount();

        $this->prepareActivationDetail();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => $merchantDetail->merchant['id'],
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'],
            [
                'status' => 'initiated',
            ]);

        $this->startTest($dataToReplace);

        Mail::assertNotQueued(Processed::class);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        Mail::assertQueued(ActivationMails\StatusChange::class, function ($mail) use($bankingAccountEntity)
        {
            $mail->build();

            return $mail->hasTo($bankingAccountEntity->spocs()->first()['email']);
        });
        $this->assertTrue($expectedHubspotCall);
    }

    public function testSegmentEventCAActivated(){
        $this->createAndFetchMocks();

        $xsegmentMock = $this->getMockBuilder(XSegmentClient::class)
            ->setMethods(['pushIdentifyandTrackEvent'])
            ->getMock();

        $this->app->instance('x-segment', $xsegmentMock);
        $xsegmentMock->expects($this->exactly(2))
            ->method('pushIdentifyandTrackEvent')
            ->willReturn(true);

        $this->testUpdateBankingAccountStatusAsProcessed();
    }

    public function testUpdateBankingAccountStatusAsProcessedFailed()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'initiated',
            ]);

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccountIncorrectCurrentToPreviousStatus()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccountToUnserviceable()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'picked',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::UNSERVICEABLE, $bankingAccount->getStatus());

        Mail::assertNotQueued(Unserviceable::class);
    }

    public function testUpdateBankingAccountToInitiated()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $bankingAccount = $this->createBankingAccount();

        $this->setupBankPartnerMerchant();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'picked',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::INITIATED, $bankingAccount->getStatus());

        $request  = [
            'url'     => '/banking_accounts/activation/' . $bankingAccount->getPublicId() . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $this->ba->adminAuth();

        $logs = $this->makeRequestAndGetContent($request);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('initiated', $logs['items'][1]['status']);
    }

    public function testUpdateBankingAccountToPicked()
    {
        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PICKED, $bankingAccount->getStatus());
    }

    public function testUpdateBankingAccountPincode()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('560031', $bankingAccount->getPincode());
    }

    public function testBusinessPanValidation()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $mid = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $mid);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccountId,
                'method'  => 'PATCH',
            ],
        ];

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $expectedValues = [
            'artefact_type' => 'business_pan',
            'owner_id'      => $bankingAccount->getId(),
        ];

        $bvsValidation = $this->getDbEntity('bvs_validation', ['owner_id' => $bankingAccount->getId(), 'owner_type' => 'banking_account'], 'live');

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function testPanValidation()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $mid = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $mid);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccountId,
                'method'  => 'PATCH',
            ],
        ];

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $expectedValues = [
            'artefact_type' => 'business_pan',
            'owner_id'      => $bankingAccount->getId(),
        ];

        $bvsValidation = $this->getDbEntity('bvs_validation', ['owner_id' => $bankingAccount->getId(), 'owner_type' => 'banking_account'], 'live');

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function testPanValidationForPersonalPan()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $mid = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $mid);

        $this->ba->addXOriginHeader();

        $activationDetail = ['activation_detail' => [
            ActivationDetail\Entity::BUSINESS_CATEGORY => 'sole_proprietorship',
            ActivationDetail\Entity::SALES_TEAM        => 'self_serve']
        ];

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetail);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', 'success');

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $expectedValues = [
            'artefact_type' => 'personal_pan',
            'owner_id'      => $bankingAccount->getId(),
        ];

        $bvsValidation = $this->getDbEntity('bvs_validation', ['owner_id' => $bankingAccount->getId(), 'owner_type' => 'banking_account']);

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function validateSuccessBvsValidation(\RZP\Models\Merchant\BvsValidation\Entity $bvsValidation,
                                                 array $expectedValues = [])
    {
        $this->assertNotNull($bvsValidation->getValidationId());
        $this->assertEmpty($bvsValidation->getErrorCode());
        $this->assertEmpty($bvsValidation->getErrorDescription());
        $this->bvsValidation($bvsValidation, $expectedValues);
    }

    private function bvsValidation(\RZP\Models\Merchant\BvsValidation\Entity $bvsValidation,
                                   array $expectedValues = [])
    {
        //
        // resetting time based data
        //
        unset($expectedValues['created_at']);
        unset($expectedValues['updated_at']);

        foreach ($expectedValues as $key => $value)
        {
            $this->assertEquals($value, $bvsValidation->getAttribute($key));
        }
    }

    /**
     * Test for getting multiple banking accounts using admin access
     */
    public function testGetBankingAccounts()
    {
        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name' => 'Umakant',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'business_category' => 'limited_liability_partnership',
                'merchant_documents_address' => 'x, y, z',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
            ]
        ];

        $this->ba->proxyAuth('rzp_test_10000000000000');

        $this->ba->addXOriginHeader();

        $this->createBankingAccountFromDashboard($bankingAccount);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts?pending_on=admin_RzrpySprAdmnId&expand[]=spocs',
                'method'  => 'GET',

            ],
        ];

        $this->startTest($dataToReplace);
    }

    /**
     * Test for getting multiple banking accounts using admin access with archived status
     */
    public function testGetBankingAccountsArchived()
    {

        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name' => 'Umakant',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'business_category' => 'limited_liability_partnership',
                'merchant_documents_address' => 'x, y, z',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'assignee_team' => 'sales',
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
            ]
        ];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($bankingAccount);

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'archived',
            ]);
        $this->ba->adminAuth();

        $dataToReplace = [
            'request'  => [
                'url'     => '/admin/banking_account?pending_on=admin_'.Org::SUPER_ADMIN,
                'method'  => 'GET',

            ],
        ];

        $this->startTest($dataToReplace);
    }

    public function testGetBankingAccount()
    {
        $activationDetails = [
            'activation_detail' => [
            'merchant_poc_name' => 'Sample Name',
            'merchant_poc_designation' => 'Financial Consultant',
            'merchant_poc_email' => 'sample@sample.com',
            'merchant_poc_phone_number' => '9876556789',
            'merchant_documents_address' => 'x, y, z',
            'business_type' => 'ecommerce',
            'account_type' => 'insignia',
            'merchant_city' => 'Bangalore',
            'is_documents_walkthrough_complete' => true,
            'merchant_region' => 'South',
            'expected_monthly_gmv' => 10000,
            'average_monthly_balance' => 0,
            'business_category' => 'partnership',
            'sales_team' => 'self_serve',
            ]
        ];

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'GET',

            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function testGetBankingAccountByAccountTypeFromMOB()
    {
        $this->ba->mobAppAuthForProxyRoutes();

        $this->fixtures->create('banking_account', [
            'id'             => 'hgqastyuiosdfg',
            'account_number' => '2224440041626905',
            'account_type'   => 'current',
            'merchant_id'    => '10000000000000',
            'channel'        => 'rbl',
            'status'         => 'created'
        ]);

        $this->startTest();
    }

    public function testGetBankingAccountByAccountTypes()
    {
        $this->mockCapitalCards();

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_' . '10000000000000', $user->getId());

        $this->fixtures->create('banking_account', [
            'account_number' => '2224440041626905',
            'account_type'   => 'nodal',
            'merchant_id'    => '10000000000000',
            'channel'        => 'rbl',
            'status'         => 'created'
        ]);

        $this->fixtures->create('banking_account', [
            'id'             => '01234567890127',
            'account_number' => '2224440041626908',
            'account_type'   => 'current',
            'merchant_id'    => '10000000000000',
            'channel'        => 'icici',
            'status'         => 'created'
        ]);

        $this->fixtures->create('banking_account', [
            'id'             => '01234567890128',
            'account_number' => '2224440041626905',
            'account_type'   => 'corp_card',
            'merchant_id'    => '10000000000000',
            'channel'        => 'm2p',
            'status'         => 'created'
        ]);

        $this->startTest();
    }

    public function testGetCorpCardBankingAccountByAccountType()
    {
        $this->mockCapitalCards();

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_' . '10000000000000', $user->getId());

        $balanceId = 'wahjkqsliosdfd';

        $this->fixtures->create('balance', [
            'id'           => $balanceId,
            'balance'      => 10000000000,
            'merchant_id'  => '10000000000000',
            'type'         => 'banking',
            'account_type' => 'corp_card'
        ]);

        $this->fixtures->create('banking_account', [
            'account_number' => '2224440041626905',
            'account_type'   => 'corp_card',
            'merchant_id'    => '10000000000000',
            'channel'        => 'rbl',
            'balance_id'     => $balanceId,
            'status'         => 'created'
        ]);

        $this->startTest();
    }

    public function testGetCorpCardBankingAccountNotFound()
    {
        $this->mockCapitalCards();

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'test');

        $this->ba->proxyAuth('rzp_test_' . '10000000000000', $user->getId());

        $balanceId = 'hnaswdyeujdwsj';

        $this->fixtures->create('balance', [
            'id'           => $balanceId,
            'balance'      => 10000000000,
            'merchant_id'  => '10000000000000',
            'type'         => 'banking',
            'account_type' => 'corp_card'
        ]);

        $this->fixtures->create('banking_account', [
            'account_number' => '2224440041626905',
            'account_type'   => 'corp_card',
            'merchant_id'    => '10000000000000',
            'channel'        => 'rbl',
            'balance_id'     => $balanceId,
        ]);

        $this->startTest();
    }

    public function testGetBankingAccountInternalViaMob()
    {
        $activationDetails = [
            'activation_detail' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'business_type' => 'ecommerce',
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'self_serve',
            ]
        ];

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $this->ba->appAuthTest(config('applications.master_onboarding.secret'));

        $this->ba->addXOriginHeader();

        //$bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_internal/' . $bankingAccount['id'],
                'method'  => 'GET',

            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function addSalesforceAuth()
    {
        $salesforceSecret = Config::get('applications.salesforce')['secret'];
        $this->ba->basicAuth('rzp_test', $salesforceSecret);
    }

    public function testCreateBankingAccountFromSalesforce()
    {
        $this->addSalesforceAuth();

        $merchantAttribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $merchantAttribute);
        $this->ba->addAccountAuth($merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $data = [
            Entity::PINCODE => '560030',
            Entity::CHANNEL => 'rbl',
        ];

        $attributes = [
            'activation_detail' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'business_type' => 'ecommerce',
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'self_serve',
            ]
        ];
        $data = array_merge($data, $attributes);

        $dataToReplace = [
            'request'  => [
                'url'     => '/salesforce/banking_account/rbl',
                'method'  => 'POST',
                'content' => $data,
            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function testSalesforceOpportunityDetails()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetBankingAccountFromSalesforce()
    {
        $activationDetails = [
            'activation_detail' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'business_type' => 'ecommerce',
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'self_serve',
            ]
        ];

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $dataToReplace = [
            'request'  => [
                'url'     => '/salesforce/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'GET',

            ],
        ];

        // replace auth by salesforce internal auth
        $this->addSalesforceAuth();
        $this->ba->addAccountAuth($merchantDetail->merchant['id']);
        $this->ba->addXOriginHeader();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function testGetBankingAccountOfOtherMerchant()
    {
        $activationDetails = [
            'activation_detail' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'business_type' => 'ecommerce',
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'self_serve',
            ]
        ];

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->create('merchant_detail', $attribute);

        $user = $this->fixtures->user->createBankingUserForMerchant($merchantDetail->merchant['id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id'],$user->getId());

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $this->ba->proxyAuth();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'GET',
            ],
        ];

        $this->expectException(\RZP\Exception\BadRequestValidationFailureException::class);

        $this->startTest($dataToReplace);
    }

    public function testGetBankingAccountForRmNotAssigned()
    {
        $activationDetails = [
            'activation_detail' => [
                'merchant_poc_name' => 'Sample Name',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'merchant_documents_address' => 'x, y, z',
                'business_type' => 'ecommerce',
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'business_category' => 'partnership',
                'sales_team' => 'self_serve',
            ]
        ];

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $this->fixtures->edit('banking_account_activation_detail',
                              $bankingAccount['banking_account_activation_details']['id'], ['rm_name' => 'rm not assigned']);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'GET',

            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals('560030', $bankingAccount->getPincode());
    }

    public function testUpdateBankingAccountToInitiatedWithInternalComments()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'picked',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->setupBankPartnerMerchant();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::INITIATED, $bankingAccount->getStatus());
    }

    protected function createBankingAccount(array $attributes = [])
    {

        $data = [
            Entity::PINCODE => '560030',
            Entity::CHANNEL => 'rbl',
        ];

        $data = array_merge($data, $attributes);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts',
            'content' => $data
        ];

        $response = $this->makeRequestAndGetContent($request);

        return $response;
    }

    public function testSkipMidOfficeCall()
    {
        $attribute = ['activation_status' => 'activated'];

        $this->addFasterDocCollectionAttribute();

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $data = [
            Entity::PINCODE => '560030', // Pincode Search Mock will be used
            Entity::CHANNEL => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::PROOF_OF_ENTITY => [
                        'status' => 'verified',
                        'source' => 'gstin'
                    ],
                    ActivationDetail\Entity::PROOF_OF_ADDRESS => [
                        'status' => 'verified',
                        'source' => 'llpin'
                    ],
                    ActivationDetail\Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS => [
                        ActivationDetail\Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS => 1,
                        ActivationDetail\Entity::SEAL_AVAILABLE => 0,
                        ActivationDetail\Entity::SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS => 1,
                        ActivationDetail\Entity::SIGNBOARD_AVAILABLE => 0,
                    ],
                ],
            ]
        ];

        $this->createBankingAccountFromDashboard($data);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        $additionalDetails = json_decode($activationDetailEntity[ActivationDetail\Entity::ADDITIONAL_DETAILS], true);

        $this->assertEquals(ActivationDetail\Entity::SALES, $additionalDetails[ActivationDetail\Entity::APPOINTMENT_SOURCE]);

        $this->assertEquals(1, $additionalDetails[ActivationDetail\Entity::SKIP_MID_OFFICE_CALL]);
    }

    public function mockPincodeSearchForCity($city, $state)
    {
        $pincodeSearchMock = Mockery::mock(PincodeSearch::class, [$this->app])->makePartial();

        $pincodeSearchMock->shouldReceive('fetchCityAndStateFromPincode')
            ->andReturn(
                [
                    'city'      => $city,
                    'state'    => $state,
                ]);

        $this->app->instance('pincodesearch', $pincodeSearchMock);
    }

    public function testSkipMidOfficeCallNegativeCase()
    {
        $attribute = ['activation_status' => 'activated'];

        $this->addFasterDocCollectionAttribute();

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->mockPincodeSearchForCity('Aligarh', 'Uttar Pradesh');

        $data = [
            Entity::PINCODE => '202122', // Pincode Search Mock will be used
            Entity::CHANNEL => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::PROOF_OF_ENTITY => [
                        'status' => 'verified',
                        'source' => 'gstin'
                    ],
                    ActivationDetail\Entity::PROOF_OF_ADDRESS => [
                        'status' => 'verified',
                        'source' => 'llpin'
                    ],
                ],
            ]
        ];

        $this->createBankingAccountFromDashboard($data);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        $additionalDetails = json_decode($activationDetailEntity[ActivationDetail\Entity::ADDITIONAL_DETAILS], true);

        $this->assertEquals(ActivationDetail\Entity::MID_OFFICE, $additionalDetails[ActivationDetail\Entity::APPOINTMENT_SOURCE]);

        $this->assertEquals(0, $additionalDetails[ActivationDetail\Entity::SKIP_MID_OFFICE_CALL]);
    }

    protected function createBankingAccountFromDashboard(array $attributes = [], bool $assertNotifyMob = true, bool $expectHubSpotMock = true)
    {
        Queue::fake();

        $data = [
            Entity::PINCODE => '560030', // Pincode Search Mock will be used
            Entity::CHANNEL => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'self_serve'
            ]
        ];

        $data = array_merge($data, $attributes);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_dashboard',
            'content' => $data
        ];

        Mail::fake();

        if ($expectHubSpotMock)
        {
            $hubspotClient = $this->mockHubSpotClient('trackHubspotEvent');

            $hubspotClient->expects($this->atLeast(1))
                ->method('trackHubspotEvent');
        }

        $response = $this->makeRequestAndGetContent($request);

        Mail::assertNotQueued(XProActivation::class);

        if (empty($response['errorMessage']) and $assertNotifyMob)
        {
            Queue::assertPushed(BankingAccountNotifyMob::class);
        } else
        {
            Queue::assertNotPushed(BankingAccountNotifyMob::class);
        }

        return $response;
    }

    public function testBankingAccountFetch()
    {
        $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForAccountNumber()
    {
        $response = $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account', $response['id'], [
            'account_number' => '1234567808',
        ]);

        $this->startTest();
    }

    public function testBankingAccountFetchForCurrentAccount()
    {
        $this->createBankingAccount();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560047,
            'business_dba'               => 'test',
            'business_name'              => 'rzp_test',
            'business_operation_city'    => 'Bangalore',
        ];
        $this->fixtures->edit('merchant_detail', '10000000000000', $merchantDetailArray);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testAdminFetchBankingAccountRequests()
    {
        $attribute = [
            'contact_email'     => 'test@rzp.com',
            'activation_status' => 'activated'
        ];

        $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);
        $this->fixtures->on('live')->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_live_10000000000000');

        $this->ba->addXOriginHeader();

        $payload = [
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
                ActivationDetail\Entity::BUSINESS_PAN      => 'RZPD38493L',
                ActivationDetail\Entity::BUSINESS_NAME     => 'ABC pvt',
                ActivationDetail\Entity::DECLARATION_STEP  => 1
            ]
        ];

        $this->createBankingAccountFromDashboard($payload);

        $this->ba->adminAuth('live');

        $this->startTest();
    }

    public function testFetchBankingAccountRequests()
    {
        $this->createBankingAccount();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560047,
            'business_dba'               => 'test',
            'business_name'              => 'rzp_test',
            'business_operation_city'    => 'Bangalore',
        ];
        $this->fixtures->edit('merchant_detail', '10000000000000', $merchantDetailArray);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForCurrentAccountFailure()
    {
        $response = $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $response['id'],
            [
                'account_type' => 'virtual',
            ]);
    }

    public function testBankingAccountFetchForMerchantName(string $dbName = 'Test Account123', string $searchName = "Test Account123")
    {
        $this->fixtures->edit('merchant_detail', '10000000000000',
            [
                'business_name'     => $dbName
            ]);

        $ba = $this->createBankingAccount();

        $this->testData[__FUNCTION__]['request']['content']['merchant_business_name'] = $searchName;

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $ba['id'];
        $this->testData[__FUNCTION__]['response']['content']['items'][0]['merchant']['merchant_detail']['business_name'] = $dbName;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForMerchantNamePartialMatch()
    {
        $this->testBankingAccountFetchForMerchantName("Fullmatch", "Fullmat");
    }

    public function testBankingAccountFetchForMerchantNameCaseMismatch()
    {
        $this->testBankingAccountFetchForMerchantName("CaSe SeNsItIvE", "case sensitive");
    }

    public function testBankingAccountFetchForMerchantNameMultipleMatch()
    {
        $mid1 = '10000000000000';

        $this->fixtures->edit('merchant_detail', '10000000000000',
            [
                "business_name"   => "Test ACCOUNT 1"
            ]);

        $mid2 = '10000000000019';

        $this->fixtures->edit('merchant_detail', $mid2,
            [
                "merchant_id"   => $mid2,
                "business_name" => "test account 2"
            ]);

        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => $mid1,
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $xBalance2 = $this->fixtures->create('balance',
            [
                'merchant_id'       => $mid2,
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '1234567808',
                'balance'           => 100000,
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => $mid1,
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
            'merchant_id'    => $mid2
        ]);

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $ba1['id'];
        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $ba2['id'];

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForMerchantEmail()
    {
        $ba = $this->createBankingAccount();

        $this->fixtures->edit('merchant',
            '10000000000000',
            [
                "email" => "razorpay@testemail.com"
            ]);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $ba['id'];

        $this->startTest();
    }

    public function testBankingAccountFetchForRZPRefNo()
    {
        $response = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $response['id'],
            [
                "bank_reference_number" => "191919"
            ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchWithMerchantPromotion()
    {
        // Given
        // merchant promotions created
        $ba = $this->createBankingAccount();

        $p = $this->fixtures->create('promotion', [
            'name'          => 'RZPNEO',
            'product'       => 'banking',
            'credit_amount' => 0,
            'iterations'    => 1
        ]);
        $this->fixtures->create('merchant_promotion', [
            'merchant_id'           => $ba['merchant_id'],
            'promotion_id'          => $p['id'],
            'start_time'            => time(),
            'remaining_iterations'  => 1,
            'expired'               => 0
        ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForMerchantPromotion()
    {
        // Given
        // merchant promotions created
//        $ba = $this->createBankingAccount();

        $p = $this->fixtures->create('promotion', [
            'name'          => 'RZPNEO',
            'product'       => 'banking',
            'credit_amount' => 0,
            'iterations'    => 1
        ]);
        $this->fixtures->create('merchant_promotion', [
            'merchant_id'           => '10000000000000',
            'promotion_id'          => $p['id'],
            'start_time'            => time(),
            'remaining_iterations'  => 1,
            'expired'               => 0
        ]);

        $baAttributes = [
//            'merchant_id'                => '10000000000000',
        ];

        $searchBody = [
            'source' => 'RZPNEO'
        ];

        $this->assertBankingAccountFetchCommon($baAttributes, $searchBody);
    }

    public function testBankingAccountFetchForMerchantPocCity(string $dbName = 'Bangalore', string $searchName = "Bangalore")
    {
        $ba = $this->testCreateActivationDetail([
                                                    ActivationDetail\Entity::MERCHANT_CITY => $dbName
        ]);

        $this->testData[__FUNCTION__]['request']['content']['merchant_poc_city'] = $searchName;

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id']                                                                         = $ba['id'];
        $this->testData[__FUNCTION__]['response']['content']['items'][0]['banking_account_activation_details'][ActivationDetail\Entity::MERCHANT_CITY] = $dbName;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForFOSCities()
    {

        $this->testCreateActivationDetail([
            ActivationDetail\Entity::MERCHANT_CITY => 'Bengaluru'
        ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForNonFOSCities()
    {

        $this->testCreateActivationDetail([
            ActivationDetail\Entity::MERCHANT_CITY => 'Indore'
        ]);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForDocsWalkthrough(bool $dbValue = true, bool $searchValue = true)
    {
        $ba = $this->testCreateActivationDetail([
                                                    ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => $dbValue
        ]);

        $this->testData[__FUNCTION__]['request']['content'][ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE] = $searchValue;

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id']                                                                                             = $ba['id'];
        $this->testData[__FUNCTION__]['response']['content']['items'][0]['banking_account_activation_details'][ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE] = intval($dbValue);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForBankAccountType(string $dbName = 'insignia', string $searchName = "insignia")
    {
        $ba = $this->testCreateActivationDetail([
                                                    ActivationDetail\Entity::ACCOUNT_TYPE => $dbName
        ]);

        $this->testData[__FUNCTION__]['request']['content']['bank_account_type'] = $searchName;

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id']                                                                        = $ba['id'];
        $this->testData[__FUNCTION__]['response']['content']['items'][0]['banking_account_activation_details'][ActivationDetail\Entity::ACCOUNT_TYPE] = $dbName;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchBankingAccountsOfCreatedStatus()
    {
        Mail::fake();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560047,
            'business_dba'               => 'test',
            'business_name'              => 'rzp_test',
            'business_operation_city'    => 'Bangalore',
        ];
        $this->fixtures->edit('merchant_detail', '10000000000000', $merchantDetailArray);

        $response = $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
            $response['id'],
            [
                'status' => 'created',
            ]);

        $this->startTest();

        Mail::assertNotQueued(Created::class);
    }

    public function testUpdatedStatusFromCreatedToPicked()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PICKED, $bankingAccount->getStatus());
    }

    public function testUpdatedStatusFromCreatedToCancelled()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::CANCELLED, $bankingAccount->getStatus());

        Mail::assertNotQueued(Cancelled::class);
    }

    public function testUpdatedStatusFromInitiatedToAccountOpening()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'] ,
                              [
                                  'status' => BankingAccount\Status::VERIFICATION_CALL,
                              ]);

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::ACCOUNT_OPENING, $bankingAccount->getStatus());

        Mail::assertNotQueued(Processing::class);
    }

    public function testUpdatedStatusFromProcessingToProcessed()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'] ,
                              [
                                  'status' => 'processing',
                              ]);

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PROCESSED, $bankingAccount->getStatus());

        // Mail::assertQueued(Processed::class);
    }

    public function testUpdateOnDiffBankInternalStatus()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->setupBankPartnerMerchant();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'] ,
            [
                'status' => 'picked',
            ]);

        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'status' => 'initiated',
            ]
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'status' => 'processing',
                ]
        ];

        $this->makeRequestAndGetContent($request);

        $request  = [
            'url'     => '/banking_accounts/' . $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'status'               => 'processing',
                'sub_status'           =>  Status::DISCREPANCY_IN_DOCS,
                'bank_internal_status' =>  Rbl\Status::DISCREPANCY_IN_DOCS,
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getLastEntity('banking_account', true);

        $changeLogRequest  = [
            'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/status_change_log',
            'method'  => 'GET',
            'content' => []
        ];

        $logs = $this->makeRequestAndGetContent($changeLogRequest);

        $this->assertEquals('created', $logs['items'][0]['status']);
        $this->assertEquals('initiated', $logs['items'][1]['status']);
        $this->assertEquals('processing', $logs['items'][2]['status']);
        $this->assertNull($logs['items'][2]['bank_status']);
        $this->assertEquals('processing', $logs['items'][3]['status']);
        $this->assertEquals(Rbl\Status::DISCREPANCY_IN_DOCS, $logs['items'][3]['bank_status']);
    }

    public function testUpdateInvalidBankInternalStatusThrowsError()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED,
            Status::INITIATED,
            null,
            Status::MERCHANT_NOT_AVAILABLE,
            null,
            Rbl\Status::MERCHANT_PREPARING_DOCS,
            null,
            '10000000000000',
            null,
            null,
            true
        );
    }

    public function testCombinationsOfBankStatusUpdate()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccount();

        $rblProcessor = new ReflectionClass(Rbl\Status::class);

        $statusSubStatusBankStatusMap = $rblProcessor->getStaticProperties()['bankToInternalStatusSubStatusMap'];

        foreach ($statusSubStatusBankStatusMap as $status => $substatusBankStatusMap)
        {
            foreach ($substatusBankStatusMap as $substatus => $bankStatuslist)
            {
                if ($substatus === Rbl\Status::ALL)
                {
                    continue;
                }
                foreach ($bankStatuslist as $bankStatus)
                {
                    sleep(1);

                    $this->assertUpdateBankingAccountStatusFromTo(
                        $status,
                        $status,
                        null,
                        $substatus,
                        null,
                        $bankStatus,
                        $bankingAccount);
                }
            }
        }
    }

    public function testUpdatedStatusFromProcessingToRejected()
    {
        Mail::fake();

        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status' => 'processing',
            ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::REJECTED, $bankingAccount->getStatus());

        Mail::assertNotQueued(Rejected::class);
    }

    public function testUpdateBankingAccountDetails()
    {
        $this->testUpdateBankingAccountStatusAsProcessed();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->adminAuth();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount->getId(),
                'method'  => 'PATCH',

            ],
        ];

        $this->startTest($dataToReplace);

        $request = [
            'url'       => '/admin/banking_account/' . 'bacc_' . $bankingAccount->getId(),
            'method'    => 'GET',
            'content'   => [
                'expand' => ['banking_account_details'],
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->sendRequest($request);

        $response = json_decode($response->getContent(), true);

        $actualDetails = $response['banking_account_details']['items'];

        $expectedDetails = [
            [
                'gateway_key'   => 'client_secret',
                'gateway_value' => 'YXBpX3NlY3JldA==',
            ],
            [
                'gateway_key'   => 'client_id',
                'gateway_value' => 'api_key',
            ]
        ];

        $this->assertArraySelectiveEquals($expectedDetails, $actualDetails);
    }

    public function testUpdateBankingAccountDetailsWithOverride()
    {
        $this->testUpdateBankingAccountDetails();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->adminAuth();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . 'bacc_' . $bankingAccount->getId(),
                'method'  => 'PATCH',
            ],
        ];

        $this->startTest($dataToReplace);

        $bankingAccountDetails = $this->getDbLastEntity('banking_account_detail');

        $this->assertEquals('api_key_two', $bankingAccountDetails['gateway_value']);
    }

    public function testBankingAccountFetchOnProxyAuthForArchivedCA()
    {
        $xBalance1 = $this->fixtures->create('balance',
                                             [
                                                 'merchant_id'       => '10000000000000',
                                                 'type'              => 'banking',
                                                 'account_type'      => 'shared',
                                                 'account_number'    => '2224440041626905',
                                                 'balance'           => 200,
                                             ]);

        $xBalance2 = $this->fixtures->create('balance',
                                             [
                                                 'merchant_id'       => '10000000000000',
                                                 'type'              => 'banking',
                                                 'account_type'      => 'direct',
                                                 'account_number'    => '1234567808',
                                                 'balance'           => 100000,
                                             ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'shared',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => '10000000000000',
            Details\Entity::BALANCE_ID              => $xBalance2->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '1234567808',
            Details\Entity::CHANNEL                 => Details\Channel::RBL,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 100000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'status'         => 'archived',
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchOnProxyAuthForOnlyOneArchivedCA()
    {
        $xBalance2 = $this->fixtures->create('balance',
                                             [
                                                 'merchant_id'       => '10000000000000',
                                                 'type'              => 'banking',
                                                 'account_type'      => 'direct',
                                                 'account_number'    => '1234567808',
                                                 'balance'           => 100000,
                                             ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => '10000000000000',
            Details\Entity::BALANCE_ID              => $xBalance2->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '1234567808',
            Details\Entity::CHANNEL                 => Details\Channel::RBL,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 100000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'status'         => 'archived',
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchOnProxyAuth()
    {
        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $xBalance2 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '1234567808',
                'balance'           => 100000,
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
        ]);

        $this->fixtures->create('balance',
                                [
                                    'merchant_id'    => '10000000000000',
                                    'type'           => 'banking',
                                    'account_type'   => 'direct',
                                    'account_number' => '567890362718193',
                                    'balance'        => 20000,
                                    'channel'        => 'icici',
                                ]);

        $this->ba->proxyAuth();

        $this->startTest();

        $bankingAccount = $this->getDbEntity('banking_account', [
            'account_number'    => '567890362718193',
        ]);

        $this->assertNull($bankingAccount);
    }

    public function testBankingAccountFetchOnProxyAuthFromLedger()
    {
        $this->app['config']->set('applications.ledger.enabled', false);

        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $xBalance2 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '1234567808',
                'balance'           => 100000,
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
        ]);

        $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'account_type'   => 'direct',
                'account_number' => '567890362718193',
                'balance'        => 20000,
                'channel'        => 'icici',
            ]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::LEDGER_REVERSE_SHADOW]);

        $this->ba->proxyAuth();

        $this->startTest();

        $bankingAccount = $this->getDbEntity('banking_account', [
            'account_number'    => '567890362718193',
        ]);

        $this->assertNull($bankingAccount);
    }

    public function testBankingAccountFetchOnPrivateAuth()
    {
        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $xBalance2 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '1234567808',
                'balance'           => 100000,
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
        ]);

        $this->fixtures->create('balance',
            [
                'merchant_id'    => '10000000000000',
                'type'           => 'banking',
                'account_type'   => 'direct',
                'account_number' => '567890362718193',
                'balance'        => 20000,
                'channel'        => 'icici',
            ]);

        $this->ba->privateAuth();

        $this->startTest();

        $bankingAccount = $this->getDbEntity('banking_account', [
            'account_number'    => '567890362718193',
        ]);

        $this->assertNull($bankingAccount);
    }

    public function testBankingAccountFetchOnAppleWatchOAuth()
    {
        $xBalance1 = $this->fixtures->on(Mode::LIVE)->create('balance',
            [
                'id'                => 'JBLee6cC0erMpg',
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $ba1 = $this->fixtures->on(Mode::LIVE)->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'activated',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->on(Mode::LIVE)->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $client = Client\Entity::factory()->create(['environment' => 'prod']);

        $this->fixtures->on('live')->merchant->edit('10000000000000', ['activated' => 1]);

        $accessToken = $this->generateOAuthAccessToken(['scopes'=> ['apple_watch_read_write'], 'mode' => 'live', 'client_id' => $client->getId()], 'prod');

        $this->fixtures->feature->create([
            Feature\Entity::ENTITY_TYPE => Feature\Constants::APPLICATION,
            Feature\Entity::ENTITY_ID   => $client->application_id,
            Feature\Entity::NAME        => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH
        ]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();
    }

    public function testBankingAccountFetchCheckFieldLastFetchedAtInBalance()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $merchantId = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 300,
            ]);

        $xBalance2 = $this->fixtures->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '1234567808',
                'balance'           => 90000,
                'channel'           => 'rbl'
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => $merchantId,
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $ba2 = $this->createBankingAccount();

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => $merchantId,
            Details\Entity::BALANCE_ID              => $xBalance2->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '1234567808',
            Details\Entity::CHANNEL                 => Details\Channel::RBL,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 90000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
            'balance_last_fetched_at' => 1587565319,
        ]);

        $this->fixtures->edit('banking_account', $ba2['id'], [
            'account_number' => '1234567808',
            'balance_id'     => $xBalance2->getId(),
            'balance_last_fetched_at' => 1587565319,
        ]);

        $response = $this->startTest();

        $this->assertNull($response['items'][0]['balance']['last_fetched_at']);

        $this->assertNotNull($response['items'][1]['balance']['last_fetched_at']);
    }

    public function testBankingAccountSPOCDetailsOnBankingAccountFetch()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $merchantId = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $balance = $this->fixtures->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '1234567890',
                'balance'           => 90000,
                'channel'           => 'rbl'
            ]);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => $merchantId,
            Details\Entity::BALANCE_ID              => $balance->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '1234567890',
            Details\Entity::CHANNEL                 => Details\Channel::RBL,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 90000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $bankingAccount['id'], [
            'account_number'        => '1234567890',
            'account_type'          => 'current',
            'account_ifsc'          => 'YESB000198',
            'beneficiary_name'      => 'abc',
            'beneficiary_mobile'    => '9999999999',
            'beneficiary_email'     => 'aa@abc.com',
            'beneficiary_address1'  => 'blr1',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'username'              => 'MERCHANT_1234',
            'password'              => 'RANDOM_STRING',
            'reference1'            => 'MERCHANT_SUB_CORP',
            'balance_id'            => $balance->getId(),
        ]);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->testUpdateActivationDetail($bankingAccountEntity);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $response = $this->startTest();

        $this->assertEquals('Test RM', $response['items'][0]['banking_account_ca_spoc_details']['rm_name']);

        $this->assertEquals('9234567890', $response['items'][0]['banking_account_ca_spoc_details']['rm_phone_number']);

        $this->assertEquals('1234554321', $response['items'][0]['banking_account_ca_spoc_details']['sales_poc_phone_number']);
    }

    public function testBankingAccountSPOCDetailsOnBankingAccountFetchWithRmNameAsVague()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $merchantId = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $balance = $this->fixtures->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '1234567890',
                'balance'           => 90000,
                'channel'           => 'rbl'
            ]);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => $merchantId,
            Details\Entity::BALANCE_ID              => $balance->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '1234567890',
            Details\Entity::CHANNEL                 => Details\Channel::RBL,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 90000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $bankingAccount['id'], [
            'account_number'        => '1234567890',
            'account_type'          => 'current',
            'account_ifsc'          => 'YESB000198',
            'beneficiary_name'      => 'abc',
            'beneficiary_mobile'    => '9999999999',
            'beneficiary_email'     => 'aa@abc.com',
            'beneficiary_address1'  => 'blr1',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'username'              => 'MERCHANT_1234',
            'password'              => 'RANDOM_STRING',
            'reference1'            => 'MERCHANT_SUB_CORP',
            'balance_id'            => $balance->getId(),
        ]);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->testUpdateActivationDetailWithRmNameAsVague($bankingAccountEntity);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $response = $this->startTest();

        $this->assertEquals(null, $response['items'][0]['banking_account_ca_spoc_details']['rm_name']);

        $this->assertEquals('9234567891', $response['items'][0]['banking_account_ca_spoc_details']['rm_phone_number']);

        $this->assertEquals('1234554321', $response['items'][0]['banking_account_ca_spoc_details']['sales_poc_phone_number']);

    }

    public function testBankingAccountSPOCDetailsOnBankingAccountFetchWithRmNameAsVagueWithCaseInSensitiveCheck()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $merchantId = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $balance = $this->fixtures->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '1234567890',
                'balance'           => 90000,
                'channel'           => 'rbl'
            ]);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => $merchantId,
            Details\Entity::BALANCE_ID              => $balance->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '1234567890',
            Details\Entity::CHANNEL                 => Details\Channel::RBL,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 90000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $bankingAccount['id'], [
            'account_number'        => '1234567890',
            'account_type'          => 'current',
            'account_ifsc'          => 'YESB000198',
            'beneficiary_name'      => 'abc',
            'beneficiary_mobile'    => '9999999999',
            'beneficiary_email'     => 'aa@abc.com',
            'beneficiary_address1'  => 'blr1',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'username'              => 'MERCHANT_1234',
            'password'              => 'RANDOM_STRING',
            'reference1'            => 'MERCHANT_SUB_CORP',
            'balance_id'            => $balance->getId(),
        ]);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->testUpdateActivationDetailWithRmNameAsVagueWithCaseInSensitiveCheck($bankingAccountEntity);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $response = $this->startTest();

        $this->assertEquals(null, $response['items'][0]['banking_account_ca_spoc_details']['rm_name']);

        $this->assertEquals('9234567891', $response['items'][0]['banking_account_ca_spoc_details']['rm_phone_number']);

        $this->assertEquals('1234554321', $response['items'][0]['banking_account_ca_spoc_details']['sales_poc_phone_number']);

    }

    public function testBankingAccountSPOCDetailsOnBankingAccountFetchWithRmNameAsEmpty()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $merchantId = $merchantDetail->merchant['id'];

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $balance = $this->fixtures->create('balance',
            [
                'merchant_id'       => $merchantId,
                'type'              => 'banking',
                'account_type'      => 'direct',
                'account_number'    => '1234567890',
                'balance'           => 90000,
                'channel'           => 'rbl'
            ]);

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => $merchantId,
            Details\Entity::BALANCE_ID              => $balance->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '1234567890',
            Details\Entity::CHANNEL                 => Details\Channel::RBL,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 90000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $bankingAccount = $this->createBankingAccount();

        $this->fixtures->edit('banking_account', $bankingAccount['id'], [
            'account_number'        => '1234567890',
            'account_type'          => 'current',
            'account_ifsc'          => 'YESB000198',
            'beneficiary_name'      => 'abc',
            'beneficiary_mobile'    => '9999999999',
            'beneficiary_email'     => 'aa@abc.com',
            'beneficiary_address1'  => 'blr1',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'username'              => 'MERCHANT_1234',
            'password'              => 'RANDOM_STRING',
            'reference1'            => 'MERCHANT_SUB_CORP',
            'balance_id'            => $balance->getId(),
        ]);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->testUpdateActivationDetailWithRmNameAsEmpty($bankingAccountEntity);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $response = $this->startTest();

        $this->assertSame(null, $response['items'][0]['banking_account_ca_spoc_details']['rm_name']);

        $this->assertEquals('9234567891', $response['items'][0]['banking_account_ca_spoc_details']['rm_phone_number']);

        $this->assertEquals('1234554321', $response['items'][0]['banking_account_ca_spoc_details']['sales_poc_phone_number']);

    }

    protected function setMozartMockResponse($mockedResponse)
    {
        $mock = Mockery::mock(Mozart::class)->makePartial();

        $mock->shouldReceive([
            'sendMozartRequest' => $mockedResponse
        ]);

        $this->app->instance('mozart', $mock);
    }

    protected function setMozartMockResponseException($exception)
    {
        $mock = Mockery::mock(Mozart::class)->makePartial();

        $mock->shouldReceive('sendMozartRequest')->andThrow($exception);

        $this->app->instance('mozart', $mock);
    }

    protected function getMozartMockedResponse(string $key)
    {
        return $this->testData[$key];
    }

    protected function setupDataForActivation($bankingAccount)
    {
        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'account_type'          => 'current',
            'account_ifsc'          => 'YESB000198',
            'beneficiary_name'      => 'abc',
            'beneficiary_mobile'    => '9999999999',
            'beneficiary_email'     => 'aa@abc.com',
            'beneficiary_address1'  => 'blr1',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'username'              => 'MERCHANT_1234',
            'password'              => 'RANDOM_STRING',
            'reference1'            => 'MERCHANT_SUB_CORP',
        ]);

        $attributes = [
            [
                'id'                 => 'badetail000000',
                'banking_account_id' => $bankingAccount->getId(),
                'gateway_key'        => 'client_id',
                'gateway_value'      => '123zz',
                'merchant_id'        => '10000000000000',
            ],
            [
                'id'                 => 'badetail000001',
                'banking_account_id' => $bankingAccount->getId(),
                'gateway_key'        => 'client_secret',
                'gateway_value'      => '123zz',
                'merchant_id'        => '10000000000000',
            ]
        ];

        $this->fixtures->create('banking_account_detail', $attributes[0]);
        $this->fixtures->create('banking_account_detail', $attributes[1]);

    }

    protected function setupDefaultScheduleForFeeRecovery()
    {
        $createScheduleRequest = [
            'method'  => 'POST',
            'url'     => '/schedules',
            'content' => [
                'type'      => 'fee_recovery',
                'name'      => 'Basic T+7',
                'period'    => 'daily',
                'interval'  => 7,
                'hour'      => 8,
            ],
        ];

        $this->ba->adminAuth();

        $schedule = $this->makeRequestAndGetContent($createScheduleRequest);

        return $schedule;
    }

    public function testBulkAssignReviewersToBankingAccounts()
    {
        $bankingAccount1 = $this->fixtures->create('banking_account', [
            'id'            => 'randomBaAccId1',
            'account_type'  => 'current',
        ]);

        $bankingAccount2 = $this->fixtures->create('banking_account', [
            'id'            => 'randomBaAccId2',
            'account_type'  => 'current',
        ]);

        $randomAdmin = $this->fixtures->create('admin', [
            'org_id' => '100000razorpay'
        ]);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['content']['reviewer_id']            = $randomAdmin->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][0] = $bankingAccount1->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][1] = $bankingAccount2->getPublicId();

        $this->startTest();

        $auditorId1 = $bankingAccount1->reviewers()->first()->pivot->admin_id;
        $auditorId2 = $bankingAccount2->reviewers()->first()->pivot->admin_id;

        $this->assertEquals($randomAdmin->getId(), $auditorId1);
        $this->assertEquals($randomAdmin->getId(), $auditorId2);
    }

    public function testBulkAssignInvalidReviewersToBankingAccounts()
    {
        $bankingAccount1 = $this->fixtures->create('banking_account', [
            'id'            => 'randomBaAccId1',
            'account_type'  => 'current',
        ]);

        $bankingAccount2 = $this->fixtures->create('banking_account', [
            'id'            => 'randomBaAccId2',
            'account_type'  => 'current',
        ]);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['content']['reviewer_id']            = 'admin_wrongAdminId12';
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][0] = $bankingAccount1->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][1] = $bankingAccount2->getPublicId();

        $this->startTest();
    }

    public function testBulkAssignReviewersToInvalidBankingAccounts()
    {
        $randomAdmin = $this->fixtures->create('admin', [
            'org_id' => '100000razorpay'
        ]);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['content']['reviewer_id']                = $randomAdmin->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][0]     = 'bacc_wrongCurAccId1';
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][1]     = 'bacc_wrongCurAccId1';
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][2]     = 'bacc_correctAcctId2';

        $this->startTest();
    }

    public function testBulkAssignReviewersToPartiallyInvalidBankingAccountList()
    {
        $bankingAccount1 = $this->fixtures->create('banking_account', [
            'id'            => 'randomBaAccId1',
            'account_type'  => 'current',
        ]);

        $randomAdmin = $this->fixtures->create('admin', [
            'org_id' => '100000razorpay'
        ]);

        $this->ba->adminAuth();

        $this->testData[__FUNCTION__]['request']['content']['reviewer_id']            = $randomAdmin->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][0] = $bankingAccount1->getPublicId();
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][1] = 'bacc_wrongCurAccId2';
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][2] = 'bacc_correctAcctId2';

        $this->startTest();

        $auditorId1 = $bankingAccount1->reviewers()->first()->pivot->admin_id;

        $this->assertEquals($randomAdmin->getId(), $auditorId1);
    }

    public function testCreateBankingAccountAdmin()
    {
        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $this->ba->adminAuth();

        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        Mail::assertQueued(XProActivation::class);

    }

    public function testCreateBankingAccountAdminWithClarityContext()
    {
        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        $this->ba->adminAuth();

        Mail::fake();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        Mail::assertNotQueued(XProActivation::class);

    }

    public function testCreateBankingAccountWithRestrictionExcludedForLMS()
    {
        $this->ba->adminAuth();

        Mail::fake();

        $this->startTest();

        Mail::assertQueued(XProActivation::class);

    }

    public function testCreateActivationDetail(array $input = null, RZP\Models\BankingAccount\Entity $bankingAccount=null)
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        if ($bankingAccount === null)
        {
            $bankingAccount = $this->createBankingAccount();

            $bankingAccountId = $bankingAccount['id'];
        }
        else
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }


        $comment = $input['comment'] ?? "Sample comment";

        $adminId = "admin_" . Org::SUPER_ADMIN;

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'POST',
                'content' => [
                    'sales_poc_id' => $adminId,
                    'comment'      => $comment
                ]
            ],
        ];

        if ($input !== null)
        {
            $dataToReplace['request']['content'] = array_merge($dataToReplace['request']['content'], $input);
        }

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccountId  = str_replace("bacc_", "", $bankingAccountId);

        $commentEntity = $this->getDbEntity('banking_account_comment', [
            'banking_account_id' => $bankingAccountId
        ]);

        $this->assertEquals($comment, $commentEntity->comment);

        $commentEntity = $this->getDbEntity('banking_account_comment', [
            'banking_account_id' => $bankingAccountId
        ]);

        $this->assertEquals($comment, $commentEntity->comment);

        $spocId  = DB::table('admin_audit_map')->where('entity_id','=',$bankingAccountId)->value('admin_id');

        $this->assertEquals(Org::SUPER_ADMIN, $spocId);

        return $bankingAccount;
    }

    public function testCreateBankingAccountActivationComment(array $bankingAccount = null, array $comment = null)
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        if ($bankingAccount === null){
            $bankingAccount = $this->createBankingAccount();
        }

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/comments',
                'method'  => 'POST',
            ],
        ];

        if ($comment !== null)
        {
            $dataToReplace['request']['content']= $comment;
            $dataToReplace['response']['content']= $comment;
        }

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        return $bankingAccount;
    }

    public function testCreateBankingAccountActivationCallLog(array $bankingAccount = null, string $finalStatus = null, string $finalSubStatus = null)
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        if ($bankingAccount === null){
            $attribute = ['activation_status' => 'activated'];

            $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

            $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

            $this->ba->addXOriginHeader();

            $bankingAccount = $this->createBankingAccount();
        }

        $finalStatus    = $finalStatus ?:'picked';
        $finalSubStatus = $finalSubStatus ?: Status::CONNECTIVITY__ASKED_TO_CALL_LATER;

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    RZP\Models\BankingAccount\Entity::STATUS     => $finalStatus,
                    RZP\Models\BankingAccount\Entity::SUB_STATUS => $finalSubStatus,
                    'activation_detail'                          => [
                        'call_log' => [
                            'date_and_time'           => '1631008860',
                            'follow_up_date_and_time' => '1641008860'
                        ],
                        'comment'  => [
                            'source_team'      => 'ops',
                            'added_at'         => '1631008860',
                            'comment'          => 'this is a comment from Ops team',
                            'source_team_type' => 'internal',
                            'type'             => 'internal'
                        ]
                    ]
                ]
            ],
            'response' => [
                'content' => [
                    RZP\Models\BankingAccount\Entity::STATUS => $finalStatus,
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        return $bankingAccount;
    }

    public function bookSlotForBankingAccount(string $bankingAccountId = null, int $slotBookingDateAndTime = null)
    {
        if ($bankingAccountId === null)
        {
            $attribute = ['activation_status' => 'activated'];

            $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

            $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

            $this->ba->addXOriginHeader();

            $payload = [
                'activation_detail' => [
                    ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                    ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
                    ActivationDetail\Entity::BUSINESS_PAN      => 'RZPD38493L',
                    ActivationDetail\Entity::BUSINESS_NAME     => 'ABC pvt',
                    ActivationDetail\Entity::DECLARATION_STEP  => 1,
                ]
            ];

            $bankingAccount = $this->createBankingAccountFromDashboard($payload);

            $bankingAccountId = $bankingAccount['id'];

            if(str_contains($bankingAccountId, Entity::getIdPrefix()) === false)
            {
                $bankingAccountId = $bankingAccount->getPublicId();
            }
        }

        $slotBookingDateAndTime = $slotBookingDateAndTime ?: 1639960752;

    $request = [
        'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details/slot_booking',
        'method'  => 'POST',
        'content' => [
            "admin_email"           => "superadmin@razorpay.com",
            "booking_date_and_time" => $slotBookingDateAndTime,
            "additional_details"    => [
                "booking_id" => "SRF2345"
            ]
        ],
    ];

    $this->ba->bankingAccountServiceAppAuth();

    return $this->makeRequestAndGetContent($request);
}

    public function testSortBySlotBookingDate()
    {
        $this->bookSlotForBankingAccount();

        $this->ba->adminAuth('live');

        $request = [
            'request'  => [
                'url'     => '/admin/banking_account?count=20&skip=0&sort_slot_booked=asc',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant', 'merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count'  => 0,
                    'items'  => [
                    ],
                ],
            ]
        ];

        $this->startTest($request);
    }

    public function testFilterSlotBookingDate()
    {
        $this->bookSlotForBankingAccount();

        $this->ba->adminAuth('live');

        $request  = [
            'request' => [
                'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&declaration_step=1&business_category=partnership&filter_slot_booked=1',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count'  => 0,
                    'items'  => [
                    ],
                ],
            ]
        ];

        $this->startTest($request);
    }

    public function testFilterSlotBookingDateWithClarityContext()
    {
        $createBankingAccountResp = $this->createBankingAccount();

        $this->createMerchantAttribute($createBankingAccountResp['merchant_id'], 'banking',
            'x_merchant_current_accounts', 'clarity_context', 'completed');

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFilterFromToSlotBookingDate()
    {
        $this->bookSlotForBankingAccount(null, 1639960752);

        $this->ba->adminAuth('live');

        $request  = [
            'request' => [
                'url'     => '/admin/banking_account?count=20&skip=0&sales_team=self_serve&from_slot_booked=1639960712&to_slot_booked=1639960792',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count'  => 0,
                    'items'  => [
                    ],
                ],
            ]
        ];

        $this->startTest($request);
    }

    public function testSortBankingAccountActivationCallLog()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->testCreateBankingAccountActivationCallLog($bankingAccount);

        $this->testCreateBankingAccountActivationCallLog($bankingAccount, Status::PICKED, Status::CONNECTIVITY__DISCONNECTED_THE_CALL);

        $dataToReplace = [
            'request' => [
                'url'     => '/admin/banking_account?count=20&skip=0&sort_follow_up_date=asc',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                    ],
                ],
            ]
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testFilterBankingAccountActivationCallFollowUpDate()
    {
        $bankingAccount = $this->createBankingAccount();

        $this->testCreateBankingAccountActivationCallLog($bankingAccount);

        $this->testCreateBankingAccountActivationCallLog($bankingAccount, Status::PICKED, Status::CONNECTIVITY__DISCONNECTED_THE_CALL);

        $dataToReplace = [
            'request' => [
                'url'     => '/admin/banking_account?count=20&skip=0&from_follow_up_date=1641005860&to_follow_up_date=1641009860',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                    ],
                ],
            ]
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateActivationDetail(RZP\Models\BankingAccount\Entity $bankingAccount = null)
    {
        $bankingAccount = $this->testCreateActivationDetail(null, $bankingAccount);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testAddVerificationDate(RZP\Models\BankingAccount\Entity $bankingAccount = null)
    {
        if ($bankingAccount === null)
        {
            $bankingAccount = $this->testCreateActivationDetail(null, $bankingAccount);
        }

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateActivationSlotBookingDetail()
    {
        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details/slot_booking',
                'method'  => 'POST',
            ],
        ];

        $this->ba->bankingAccountServiceAppAuth();

        $this->startTest($dataToReplace);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->assertNotNull($bankingAccountEntity->reviewers());
    }

    public function testGetSlotBookingDetails()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard();

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $request = [
        'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details/slot_booking',
        'method'  => 'POST',
        'content' => [
            "admin_email"           => "superadmin@razorpay.com",
            "booking_date_and_time" => 1639960752,
            "additional_details"    => [
                "booking_id" => "SRF2345"
            ]
        ],
    ];

        $this->ba->bankingAccountServiceAppAuth();

        $this->makeRequestAndGetContent($request);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->assertNotNull($bankingAccountEntity->reviewers());

        $dataToReplace = [
            'request' => [
                'url'     => '/booking/slot',
                'method'  => 'GET',
                'content' => [
                    "id"      => $bankingAccountId,
                    "channel" => 'rbl'
                ],
            ],
        ];

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $this->startTest($dataToReplace);
    }

    public function testUpdateActivationDetailIfNameUpdated()
    {
        $bankingAccount = $this->testCreateActivationDetail();

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateAdditionalDetailUpdated()
    {
        $this->fixtures->edit('merchant_detail', '10000000000000',
            [
                'business_type'     => '2',
            ]);

        $bankingAccount = $this->testCreateActivationDetail();

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateAdditionalDetailswithDifferentValues()
    {
        $bankingAccount = $this->testCreateActivationDetail([
            ActivationDetail\Entity::ADDITIONAL_DETAILS => json_encode([
                'green_channel' => false,
                'entity_proof_documents'    => [
                    [
                        'document_type' => 'gst_certificate',
                        'file_id'       => 'test',
                    ]
                ],
            ])
        ]);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateActivationDetailForNeostoneFlow()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $activationDetail = ['activation_detail' => [
            ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
            ActivationDetail\Entity::BUSINESS_CATEGORY => 'sole_proprietorship',
            ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
            ActivationDetail\Entity::BUSINESS_PAN      => 'RZPA34243L']
        ];

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetail);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bvsValidation = $this->getDbEntity('bvs_validation', ['owner_id' => $bankingAccountId, 'owner_type' => 'banking_account'], 'live');

        $this->assertNull($bvsValidation);
    }

    public function testFreshDeskTicketforSalesAssistedFlowFromAdminDashboard()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $activationDetail = ['activation_detail' => [
            ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
            ActivationDetail\Entity::BUSINESS_CATEGORY => 'sole_proprietorship',
            ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
            ActivationDetail\Entity::BUSINESS_PAN      => 'RZPA34243L',]
        ];

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetail);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->assertFreshDeskTicketCreatedEventFired(true,[
            'banking_account_id' => substr($bankingAccountId,5),
            'status'             => 'picked',
        ]);

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        Mail::assertQueued(XProActivation::class, function ($mail) use($bankingAccount)
        {
            $mail->build();
            return $mail->hasTo('x.support@razorpay.com');
        });

    }

    public function testUpdateActivationDetailForNeostoneFlowIfNameUpdated()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $activationDetail = ['activation_detail' => [
            ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
            ActivationDetail\Entity::BUSINESS_CATEGORY => 'sole_proprietorship',
            ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
            ActivationDetail\Entity::BUSINESS_PAN      => 'RZPA34243L']
        ];

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetail);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateActivationDetailWithRmNameAsVague(RZP\Models\BankingAccount\Entity $bankingAccount = null)
    {
        $bankingAccount = $this->testCreateActivationDetail(null, $bankingAccount);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateActivationDetailWithRmNameAsVagueWithCaseInSensitiveCheck(RZP\Models\BankingAccount\Entity $bankingAccount = null)
    {
        $bankingAccount = $this->testCreateActivationDetail(null, $bankingAccount);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateActivationDetailWithRmNameAsEmpty(RZP\Models\BankingAccount\Entity $bankingAccount = null)
    {
        $bankingAccount = $this->testCreateActivationDetail(null, $bankingAccount);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testCreateBankingAccountActivationCommentViaBatch()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $bankingAccount = $this->createBankingAccount();

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->testCreateActivationDetail(null, $bankingAccountEntity);

        $admin = $this->getDbLastEntity('admin');

        $comment = 'this is a comment from Ops team';

        $dataToReplace = [
            'request'  => [
                'content' => [
                    'bank_reference_number' => $bankingAccount['bank_reference_number'],
                    'admin_id'          => $admin['id'],
                    'comment'           => $comment
                ]
            ],
        ];

        $this->ba->batchAppAuth();

        $this->startTest($dataToReplace);

        $bankingAccountComment = $this->getDbLastEntity('banking_account_comment');

        $this->assertequals($comment, $bankingAccountComment->comment);

        return $bankingAccount;
    }

    public function testUpdateActivationDetailWithRmNameAndPhoneNumber(RZP\Models\BankingAccount\Entity $bankingAccount = null)
    {
        $bankingAccount = $this->testCreateActivationDetail(null, $bankingAccount);

        $bankingAccountId = $bankingAccount['id'];

        if(str_contains($bankingAccount['id'], Entity::getIdPrefix()) === false)
        {
            $bankingAccountId = $bankingAccount->getPublicId();
        }

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/activation/' . $bankingAccountId . '/details',
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function prepareActivationDetail(array $input = null)
    {
        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->testCreateActivationDetail($input, $bankingAccountEntity);
    }

    public function testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch(string $comment = null, string $status = null, string $subStatus = null)
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $bankingAccount = $this->createBankingAccount();

        $this->prepareActivationDetail();

        $admin = $this->getDbLastEntity('admin');

        $comment = ($comment !== null) ? $comment: 'this is a comment from Ops team';

        $status = ($status !== null) ? $status: 'RazorpayProcessing';

        $subStatus = ($subStatus !== null) ? $subStatus: '';

        $dataToReplace = [
            'request'  => [
                'content' => [
                    'bank_reference_number' => $bankingAccount['bank_reference_number'],
                    'admin_id'          => $admin['id'],
                    'comment'           => $comment,
                    'status'            => $status,
                    'sub_status'        => $subStatus,
                ]
            ],
        ];

        $bankingAccountOld = $this->getDbLastEntity('banking_account');

        $this->ba->batchAppAuth();

        $this->startTest($dataToReplace);

        $bankingAccountComment = $this->getDbLastEntity('banking_account_comment');

        if ($comment === '')
        {
            $this->assertNotEquals($comment, $bankingAccountComment->comment);
        }
        else
        {
            $this->assertequals($comment, $bankingAccountComment->comment);
        }

        $bankingAccountUpdated = $this->getDbEntityById('banking_account', $bankingAccount['id']);

        if ($status === '')
        {
            $expectedStatus = $bankingAccountOld['status'];
        }
        else
        {
            $expectedStatus = Status::transformFromExternalToInternal($status);
        }

        $this->assertEquals($expectedStatus, $bankingAccountUpdated->getStatus());

        if ($subStatus === '')
        {
            $expectedSubStatus = $bankingAccountOld['sub_status'];
        }
        else
        {
            $expectedSubStatus = Status::transformSubStatusFromExternalToInternal($subStatus);
        }

        $this->assertEquals($expectedSubStatus, $bankingAccountUpdated->getSubStatus());

        return $bankingAccount;
    }

    public function testUpdateStatusWithEmptyCommentViaBatch()
    {
        $this->testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch('','RazorpayProcessing');
    }

    public function testCreateBankingAccountCommentWithEmptyStatusViaBatch()
    {
        $this->testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch('Sample comment','');
    }

    public function testCreateBankingAccountCommentWithSubStatusViaBatch()
    {
        $this->testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch('Sample comment','RazorpayProcessing', 'Merchant is preparing Docs');
    }

    public function testCreateBankingAccountCommentWithNoneSubStatusViaBatch()
    {
        $this->testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch('Sample comment','RazorpayProcessing', 'None');
    }

    public function testCreateBankingAccountCommentWithForbiddenStatusChangeViaBatch()
    {
        // Application Received (initial state) -> Bank Processing is not permitted
        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch('Sample comment','BankProcessing');
    }

    public function testCreateBankingAccountCommentArchiveActivatedAccountViaBatch()
    {
        $attribute = ['activation_status' => 'activated'];

        $this->fixtures->edit('merchant_detail', self::DefaultMerchantId, $attribute);

        $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '560038',
            'bank_reference_number' => '191919',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['testCreateBankingAccountActivationCommentAndUpdateStatusViaBatch'];

        $admin = $this->getDbLastEntity('admin');

        $dataToReplace = [
            'request'  => [
                'content' => [
                    'bank_reference_number' => '191919',
                    'admin_id'          => $admin['id'],
                    'comment'           => 'This is a comment',
                    'status'            => 'Archived',
                ]
            ],
        ];

        $this->ba->batchAppAuth();

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->expectExceptionMessage(\RZP\Error\PublicErrorDescription::BAD_REQUEST_BANKING_ACCOUNT_ALREADY_ACTIVATED);

        $this->startTest($dataToReplace);
    }

    public function testGetBankingAccountActivationComment()
    {
        $bankingAccount =$this->testCreateBankingAccountActivationComment();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/comments?expand[]=admin',
                'method'  => 'GET',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testGetBankingAccountActivationCallLog()
    {
        $bankingAccount = $this->testCreateBankingAccountActivationCallLog();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/call_logs?expand[]=comment&expand[]=admin&expand[]=state_log',
                'method'  => 'GET',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testGetBankingAccountActivationCallLogForMoreThanOne()
    {
        $bankingAccount = $this->testCreateBankingAccountActivationCallLog();

        $bankingAccount = $this->testCreateBankingAccountActivationCallLog($bankingAccount, Status::PICKED, Status::CONNECTIVITY__DISCONNECTED_THE_CALL);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/call_logs?expand[]=comment&expand[]=admin&expand[]=state_log',
                'method'  => 'GET',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testGetBankingAccountActivationCallLogForMoreThanOneForSameStatus()
    {
        $bankingAccount = $this->testCreateBankingAccountActivationCallLog();

        $bankingAccount = $this->testCreateBankingAccountActivationCallLog($bankingAccount);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/activation/' . $bankingAccount['id'] . '/call_logs?expand[]=comment&expand[]=admin&expand[]=state_log',
                'method'  => 'GET',
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testResolveBankingAccountActivationComment(){
        $this->testCreateBankingAccountActivationComment();

        $comment = $this->getDbLastEntity('banking_account_comment');

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/activation/comments/' . $comment->getId(),
                'content' => [
                    'type' => 'external_resolved'
                ],
            ],
            'response' => [
                'content' => [
                    'type' => 'external_resolved'
                ]
            ]
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function testCreateBankingAccountCreatesAssignee()
    {
        $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccountActivationDetails = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals('ops', $bankingAccountActivationDetails[ActivationDetail\Entity::ASSIGNEE_TEAM]);
    }

    public function testUpdateBankingAccountAssignee(array $content = null)
    {
        $bankingAccount = $this->testCreateBankingAccountWithActivationDetail();

        if ($content === null)
        {
            $content = [
                'activation_detail' => [
                    'assignee_team' => 'sales',
                    'comment' => [
                        'comment' => 'sample comment while changing assignee',
                        'source_team' => 'ops',
                        'source_team_type' => 'internal',
                        'type' => 'internal',
                        'added_at' => 1597217557
                    ]
                ]
            ];
        }

        $startingState = $this->getDbLastEntity('banking_account_state');

        $this->assertNotEquals($startingState->getAssigneeTeam(), $content['activation_detail']['assignee_team']);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount->getPublicId(),
                'method'  => 'PATCH',
                'content' => $content
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $bankingAccountActivationDetails = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals($content['activation_detail']['assignee_team'], $bankingAccountActivationDetails[ActivationDetail\Entity::ASSIGNEE_TEAM]);

        $finalState = $this->getDbLastEntity('banking_account_state');

        $this->assertEquals($finalState->getAssigneeTeam(), $content['activation_detail']['assignee_team']);

        Mail::assertQueued(ActivationMails\AssigneeChange::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            return ($mail->viewData['body']  === 'This is to notify that test admin and team sales is the new assignee for Current Account for Merchant CA Business.');
        });

        return $bankingAccount;
    }

    public function testUpdateBankingAccountAssigneeWithoutCommentFails()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->testUpdateBankingAccountAssignee([
            'activation_detail' => [
                'assignee_team' => 'sales',
            ]
        ]);
    }

    public function testUpdateBankingAccountAsigneeWithStatusSubStatusChangeCapturedInChangeLog()
    {
        $content = [
            'status' => Status::PICKED,
            'sub_status' => Status::MERCHANT_PREPARING_DOCS,
            'activation_detail' => [
                'assignee_team' => 'sales',
                'comment' => [
                    'comment' => 'sample comment while changing assignee',
                    'source_team' => 'ops',
                    'source_team_type' => 'internal',
                    'type' => 'internal',
                    'added_at' => 1597217557
                ]
            ]
        ];

        $this->testUpdateBankingAccountAssignee($content);

        $finalState = $this->getDbLastEntity('banking_account_state');

        $this->assertEquals($finalState->getStatus(), $content['status']);
        $this->assertEquals($finalState->getSubStatus(), $content['sub_status']);
    }

    public function assertUpdateViaBatch(array $content)
    {
        $admin = $this->getDbLastEntity('admin');

        $dataToReplace = [
            'request'  => [
                'content' => [
                    'admin_id' => $admin['id'],
                    'channel' => 'rbl',
                ]
            ],
        ];

        $dataToReplace['request']['content'] = array_merge($dataToReplace['request']['content'], $content);

        $this->ba->batchAppAuth();

        $this->startTest($dataToReplace);
    }

    public function testUpdateBankingAccountAssigneeTeamViaBatch()
    {
        $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $content = [
            'bank_reference_number' => $bankingAccount['bank_reference_number'],
            'comment' => 'sample comment from batch',
            'source_team' => 'bank',
            'source_team_type' => 'external',
            'added_at' => 1594800229,
            'assignee_team' => 'sales',
        ];

        $this->assertUpdateViaBatch($content);
    }

    public function assertBankingAccountFetchCommon(array $baAttributes, $searchBody)
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->testCreateActivationDetail($baAttributes);

        $this->ba->adminAuth();

        $lastCreatedBankingAccount = $this->getDbLastEntity('banking_account');

        $dataToReplace = [
            'request' => [
                'content' => $searchBody
            ],
            'response' => [
                'content' => [
                    'items' => [
                        [
                            'id' => $lastCreatedBankingAccount->getPublicId()
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankingAccountFetchForAssignee()
    {

        $baAttributes = [
            'assignee_team' => 'ops'
        ];

        $searchBody = [
            'assignee_team' => 'ops'
        ];

        $this->assertBankingAccountFetchCommon($baAttributes, $searchBody);
    }

    public function testBankingAccountFetchForAssigneeBankOps()
    {
        $baAttributes = [
            'assignee_team' => 'ops'
        ];

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->testCreateActivationDetail($baAttributes);

        $this->ba->adminAuth();

        $lastCreatedBankingAccount = $this->getDbLastEntity('banking_account');

        $lastBankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->fixtures->edit('banking_account_activation_detail',
            $lastBankingAccountActivationDetail->getId(),
            [
                'assignee_team' => 'bank_ops'
            ]);

        $searchBody = [
            'assignee_team' => 'ops'
        ];

        $dataToReplace = [
            'request' => [
                'content' => $searchBody
            ],
            'response' => [
                'content' => [
                    'items' => [
                        [
                            'id' => $lastCreatedBankingAccount->getPublicId()
                        ]
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankingAccountFetchForSpoc()
    {
        $baAttributes = [
            'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
        ];

        $searchBody = [
            'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN
        ];

        $this->assertBankingAccountFetchCommon($baAttributes, $searchBody);
    }

    public function testBankingAccountExternalCommentsMIS()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $bankingAccount = $this->createBankingAccount();

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->prepareActivationDetail([
            'assignee_team' => 'bank'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment 2',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample internal comment',
            'type' => 'internal'
        ]);

        $misProcessor = new MIS\ExternalComments([]);

        $fileInput = $misProcessor->getFileInput();

        // voluntarily mis-aligned to assert new line
        // TODO: Assert cleanly

        $today = '['. epoch_format(time(), 'M d, Y'). ']';
        $expectedFileInput = [
            [
                'RZP Ref No' => '10000',
                'Comments'   => $today.' Sample external comment
'.$today. ' Sample external comment 2
',
                'Customer Name' => $bankingAccountEntity->merchant->name,
                'Sales POC Name' => $bankingAccountEntity->spocs()->first()->name,
                'Sales POC Number' => $bankingAccountEntity->bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER]
            ]
        ];

        $this->assertEquals($expectedFileInput, $fileInput);
    }

    public function testBankingAccountLeadsMIS()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->createBankingAccount();

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->prepareActivationDetail([
            'assignee_team' => 'bank',
            'merchant_poc_name' => 'Sample Name',
            'merchant_poc_designation' => 'Financial Consultant',
            'merchant_poc_email' => 'sample@sample.com',
            'merchant_poc_phone_number' => '9876556789',
            'account_type' => 'insignia',
            'expected_monthly_gmv' => 40000,
            'average_monthly_balance' => 0,
            'business_category' => 'partnership',
            'initial_cheque_value' => 222,
            'additional_details' => [
                'green_channel' => false,
                'feet_on_street' => true,
            ]
        ]);

        $createdAt = Carbon::now()->timestamp;

        $this->fixtures->create('banking_account_state',
            [
                'banking_account_id'    => $bankingAccountEntity->getId(),
                'status'                => 'initiated',
                'sub_status'            => 'none',
                'bank_status'           => null,
                'created_at'            => $createdAt,
            ]);

        $misProcessor = new MIS\Leads([]);

        // Assigning first value of array to fileinput to test with the input we created
        $fileInput[0] = $misProcessor->getFileInput()[0];

        $sentToBankDate = Carbon::createFromTimestamp($createdAt, Timezone::IST)->format('Y-m-d') ?? '';
        $sentToBankTime = Carbon::createFromTimestamp($createdAt, Timezone::IST)->format('h:i A') ?? '';;

        // voluntarily mis-aligned to assert new line
        // TODO: Assert cleanly

        $expectedFileInput = [
            [
                Leads::RZP_REF_NO => '10000',
                Leads::MERCHANT_NAME =>  $bankingAccountEntity->merchant->name,
                Leads::MERCHANT_POC_NAME => 'Sample Name',
                Leads::MERCHANT_POC_DESIGNATION => 'Financial Consultant',
                Leads::MERCHANT_POC_EMAIL => 'sample@sample.com',
                Leads::MERCHANT_POC_PHONE => '9876556789',
                Leads::PINCODE => $bankingAccountEntity->getPincode(),
                Leads::MERCHANT_CITY => 'Bangalore',
                Leads::CONSTITUTION_TYPE => 'Partnership',
                Leads::MERCHANT_ICV => 222,
                Leads::APPLICATION_SUBMISSION_DATE => $sentToBankDate,
                Leads::TIMESTAMP => $sentToBankTime,
                Leads::BUSINESS_MODEL => null,
                Leads::ACCOUNT_TYPE => 'Insignia',
                Leads::COMMENT => 'Sample comment',
                Leads::EXPECTED_MONTHLY_GMV => 40000,
                Leads::SALES_POC =>  $bankingAccountEntity->spocs()->first()->name,
                Leads::SALES_POC_PHONE_NUMBER => $bankingAccountEntity->bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER],
                Leads::GREEN_CHANNEL => 'No',
                Leads::FOS => 'Yes',
                Leads::REVIVED_LEAD => '',
                Leads::OPS_POC_NAME => '',
                Leads::OPS_POC_EMAIL => '',
                Leads::DOCKET_DELIVERY_DATE => '',
                Leads::STATUS => 'ApplicationReceived',
                Leads::SUB_STATUS => '',
                Leads::ASSIGNEE => 'Bank',
                Leads::MID_OFFICE_POC => '',
                Leads::LEAD_REFERRED_BY_RBL_STAFF => '',
                Leads::OFFICE_AT_DIFFERENT_LOCATIONS => '',
                Leads::CUSTOMER_APPOINTMENT_DATE => '',
                Leads::APPOINTMENT_TAT => null,
                Leads::LEAD_IR_NO => null,
                Leads::RM_NAME => null,
                Leads::RM_MOBILE_NO => null,
                Leads::BRANCH_CODE => null,
                Leads::BRANCH_NAME => '',
                Leads::BM => '',
                Leads::BM_MOBILE_NO => '',
                Leads::TL => '',
                Leads::CLUSTER => '',
                Leads::REGION => '',
                Leads::DOC_COLLECTION_DATE => '',
                Leads::DOC_COLLECTION_TAT => null,
                Leads::IP_CHEQUE_VALUE => null,
                Leads::API_DOCS_RECEIVED_WITH_CA_DOCS => '',
                Leads::API_DOC_DELAY_REASON => null,
                Leads::REVISED_DECLARATION => '',
                Leads::ACCOUNT_IR_NO => null,
                Leads::ACCT_LOGIN_DATE => '',
                Leads::IR_LOGIN_TAT => null,
                Leads::PROMO_CODE => null,
                Leads::CASE_LOGIN => '',
                Leads::SR_NO => null,
                Leads::ACCOUNT_OPEN_DATE => '',
                Leads::ACCOUNT_IR_CLOSED_DATE => '',
                Leads::AO_FTNR => '',
                Leads::AO_FTNR_REASONS => null,
                Leads::AO_TAT_EXCEPTION => '',
                Leads::AO_TAT_EXCEPTION_REASON => null,
                Leads::API_IR_NO => null,
                Leads::API_IR_LOGIN_DATE => '',
                Leads::LDAP_ID_MAIL_DATE => '',
                Leads::API_REQUEST_TAT => null,
                Leads::API_IR_CLOSED_DATE => '',
                Leads::API_REQUEST_PROCESSING_TAT => null,
                Leads::API_FTNR => '',
                Leads::API_FTNR_REASONS => null,
                Leads::API_TAT_EXCEPTION => '',
                Leads::API_TAT_EXCEPTION_REASON => null,
                Leads::CORP_ID_MAIL_DATE => '',
                Leads::RZP_CA_ACTIVATED_DATE => '',
                Leads::UPI_CREDENTIALS_DATE => '',
                Leads::UPI_CREDENTIALS_NOT_DONE_REMARKS => null,
                Leads::DROP_OFF_DATE => '',
                Leads::API_SERVICE_FIRST_QUERY => null,
                Leads::API_BEYOND_TAT => '',
                Leads::API_BEYOND_TAT_DEPENDENCY => null,
                Leads::FIRST_CALLING_TIME => null,
                Leads::SECOND_CALLING_TIME => null,
                Leads::WA_MESSAGE_SENT_DATE => '',
                Leads::WA_MESSAGE_RESPONSE_DATE => '',
                Leads::API_DOCKET_RELATED_ISSUE => null,
                Leads::AOF_SHARED_WITH_MO => '',
                Leads::AOF_SHARED_DISCREPANCY => '',
                Leads::AOF_NOT_SHARED_REASON => null,
                Leads::CA_BEYOND_TAT_DEPENDENCY => null,
                Leads::CA_BEYOND_TAT => '',
                Leads::CA_SERVICE_FIRST_QUERY => null,
                Leads::LEAD_IR_STATUS => null,
                Leads::CUSTOMER_APPOINTMENT_BOOKING_DATE => '',
                Leads::CUSTOMER_ONBOARDING_TAT => null,
            ]
        ];

        $this->assertEquals($expectedFileInput, $fileInput);
    }

    public function testBankingAccountExternalCommentsMISWithAssigneeTeamAsOps()
    {
        $this->testData[__FUNCTION__] = $this->testData['testBankingAccountExternalCommentsMIS'];

        $this->testData[__FUNCTION__]['request']['content']['assignee_team'] = 'ops';

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $bankingAccount = $this->createBankingAccount();

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->prepareActivationDetail([
            'assignee_team' => 'ops'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment 2',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment 3',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample internal comment',
            'type' => 'internal'
        ]);

        $misProcessor = new MIS\ExternalComments([]);

        $fileInput = $misProcessor->getFileInput();

        $today = '['. epoch_format(time(), 'M d, Y'). ']';
        $expectedFileInput = [
            [
                'RZP Ref No' => '10000',
                'Comments'   => $today.' Sample external comment
'.$today. ' Sample external comment 2
'.$today. ' Sample external comment 3
',
                'Customer Name' => $bankingAccountEntity->merchant->name,
                'Sales POC Name' => $bankingAccountEntity->spocs()->first()->name,
                'Sales POC Number' => $bankingAccountEntity->bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER]
            ]
        ];

        $this->assertEquals($expectedFileInput, $fileInput);
    }

    public function testBankingAccountExternalCommentsMISWithAssigneeTeamAsSales()
    {

        $this->testData[__FUNCTION__] = $this->testData['testBankingAccountExternalCommentsMIS'];

        $this->testData[__FUNCTION__]['request']['content']['assignee_team'] = 'sales';

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $bankingAccount = $this->createBankingAccount();

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->prepareActivationDetail([
            'assignee_team' => 'sales'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment 2',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample external comment 3',
            'type' => 'external'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample internal comment',
            'type' => 'internal'
        ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount, [
            'comment' => 'Sample internal comment 2',
            'type' => 'internal'
        ]);

        $misProcessor = new MIS\ExternalComments([]);

        $fileInput = $misProcessor->getFileInput();

        $today = '['. epoch_format(time(), 'M d, Y'). ']';
        $expectedFileInput = [
            [
                'RZP Ref No' => '10000',
                'Comments'   => $today.' Sample external comment
'.$today. ' Sample external comment 2
'.$today. ' Sample external comment 3
',
                'Customer Name' => $bankingAccountEntity->merchant->name,
                'Sales POC Name' => $bankingAccountEntity->spocs()->first()->name,
                'Sales POC Number' => $bankingAccountEntity->bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER]
            ]
        ];

        $this->assertEquals($expectedFileInput, $fileInput);
    }

    public function testUpdateAccountOpenDateAndLoginDateViaBatch()
    {
        $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $content = [
            'bank_reference_number' => $bankingAccount['bank_reference_number'],
            'comment' => 'sample comment from batch',
            'source_team' => 'bank',
            'source_team_type' => 'external',
            'added_at' => 1594800229,
            'assignee_team' => 'sales',
            'account_open_date' => '23-Jun-2020',
            'account_login_date' => '23-Jun-2020'
        ];

        $this->assertUpdateViaBatch($content);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals('1592850600', $bankingAccountActivationDetail[ActivationDetail\Entity::ACCOUNT_OPEN_DATE]);
    }

    public function testUpdateBankStatusViaBatch()
    {
        $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account',
            $bankingAccount->getId(),
            [
                'status'               => Status::PROCESSING,
                'sub_status'           => Status::DISCREPANCY_IN_DOCS,
                'bank_internal_status' => Rbl\Status::DISCREPANCY_IN_DOCS
            ]);

        $content = [
            'bank_reference_number' => $bankingAccount['bank_reference_number'],
            'comment' => 'sample comment from batch',
            'source_team' => 'bank',
            'source_team_type' => 'external',
            'added_at' => 1594800229,
            'status' => Status::BANK_PROCESSING,
            'sub_status' => Status::BANK_OPENED_ACCOUNT_EXTERNAL,
            'bank_internal_status' => Rbl\Status::ACCOUNT_OPENED_EXTERNAL,
            'assignee_team' => 'sales',
            'account_open_date' => '23-Jun-2020'
        ];

        $this->assertUpdateViaBatch($content);

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::PROCESSING);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::BANK_OPENED_ACCOUNT);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::ACCOUNT_OPENED);
    }

    public function testUpdateBankStatusViaBatchNewStates()
    {
        $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account',
            $bankingAccount->getId(),
            [
                'status'               => Status::VERIFICATION_CALL,
                'sub_status'           => Status::IN_PROCESSING,
                'bank_internal_status' => null
            ]);

        $content = [
            'bank_reference_number' => $bankingAccount['bank_reference_number'],
            'comment' => 'sample comment from batch',
            'source_team' => 'bank',
            'source_team_type' => 'external',
            'added_at' => 1594800229,
            'assignee_team' => 'sales',
            'account_open_date' => '23-Jun-2020'
        ];

        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::VERIFICATION_CALL_EXTERNAL,
            'sub_status' => Status::CUSTOMER_NOT_RESPONDING_EXTERNAL,
            'bank_internal_status' => Rbl\Status::MERCHANT_NOT_AVAILABLE_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::VERIFICATION_CALL);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::CUSTOMER_NOT_RESPONDING);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::MERCHANT_NOT_AVAILABLE);


        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::VERIFICATION_CALL_EXTERNAL,
            'sub_status' => Status::FOLLOW_UP_REQUESTED_BY_MERCHANT_EXTERNAL,
            'bank_internal_status' => Rbl\Status::MERCHANT_PREPARING_DOCS_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::VERIFICATION_CALL);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::FOLLOW_UP_REQUESTED_BY_MERCHANT);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::MERCHANT_PREPARING_DOCS);

        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::VERIFICATION_CALL_EXTERNAL,
            'sub_status' => Status::NEEDS_CLARIFICATION_FROM_RZP_EXTERNAL,
            'bank_internal_status' => Rbl\Status::RAZORPAY_DEPENDENT_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::VERIFICATION_CALL);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::NEEDS_CLARIFICATION_FROM_RZP);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::RAZORPAY_DEPENDENT);

        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::DOC_COLLECTION_EXTERNAL,
            'sub_status' => Status::VISIT_DUE_EXTERNAL,
            'bank_internal_status' => Rbl\Status::YET_TO_PICKUP_DOCS_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::DOC_COLLECTION);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::VISIT_DUE);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::YET_TO_PICKUP_DOCS);

        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::ACCOUNT_OPENING_EXTERNAL,
            'sub_status' => Status::IR_IN_DISCREPANCY_EXTERNAL,
            'bank_internal_status' => Rbl\Status::DISCREPANCY_IN_DOCS_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::ACCOUNT_OPENING);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::IR_IN_DISCREPANCY);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::DISCREPANCY_IN_DOCS);


        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::ACCOUNT_OPENING_EXTERNAL,
            'sub_status' => Status::CA_OPENED_SUB_STATUS_EXTERNAL,
            'bank_internal_status' => Rbl\Status::ACCOUNT_OPENED_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::ACCOUNT_OPENING);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::CA_OPENED_SUB_STATUS);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::ACCOUNT_OPENED);

        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::API_ONBOARDING_EXTERNAL,
            'sub_status' => Status::IN_REVIEW_EXTERNAL,
            'bank_internal_status' => Rbl\Status::API_ONBOARDING_IN_PROGRESS_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::API_ONBOARDING);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::IN_REVIEW);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::API_ONBOARDING_IN_PROGRESS);


        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::API_ONBOARDING_EXTERNAL,
            'sub_status' => Status::IR_IN_DISCREPANCY_EXTERNAL,
            'bank_internal_status' => Rbl\Status::DISCREPANCY_IN_API_DOCS_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::API_ONBOARDING);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::IR_IN_DISCREPANCY);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::DISCREPANCY_IN_API_DOCS);


        $this->assertUpdateViaBatch(array_merge($content, [
            'status' => Status::ARCHIVED_EXTERNAL,
            'sub_status' => Status::NEGATIVE_PROFILE_SVR_ISSUE_EXTERNAL,
            'bank_internal_status' => Rbl\Status::REJECTED_EXTERNAL,
        ]));

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertEquals(end($statusChangeLogs['items'])['status'], Status::ARCHIVED);
        $this->assertEquals(end($statusChangeLogs['items'])['sub_status'], Status::NEGATIVE_PROFILE_SVR_ISSUE);
        $this->assertEquals(end($statusChangeLogs['items'])['bank_status'], Rbl\Status::REJECTED);

    }

    public function testBackFillOfDataInLmsViaBatch()
    {
        $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->fixtures->edit('banking_account',
            $bankingAccount->getId(),
            [
                'status'               => Status::PROCESSING,
                'sub_status'           => Status::DISCREPANCY_IN_DOCS,
                'bank_internal_status' => Rbl\Status::DISCREPANCY_IN_DOCS
            ]);

        $content = [
            'bank_reference_number' => $bankingAccount['bank_reference_number'],
            'sales_team' => 'capital_sme',
            'sales_poc_email' => 'superadmin@razorpay.com'
        ];

        $this->assertUpdateViaBatch($content);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertEquals($content['sales_team'], $activationDetailEntity['sales_team']);
    }

    public function testCitiesForAutoComplete()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testSpocDailyUpdates()
    {
        Mail::fake();

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $bankingAccount = $this->testCreateBankingAccountWithActivationDetail();

        $this->fixtures->edit('banking_account', $bankingAccount['id'],
            [
                'status' => 'initiated',
                'sub_status' => 'merchant_not_available'
            ]);

        $this->testCreateBankingAccountActivationComment($bankingAccount->toArrayPublic());

        $baComment = $this->getDbLastEntity('banking_account_comment');

        $yesterday9pm = Carbon::yesterday(Timezone::IST)->hour(21)->getTimestamp();

        $this->fixtures->edit('banking_account_comment', $baComment->getId(), [
            'created_at' => $yesterday9pm
        ]);

        $this->ba->cronAuth();

        $this->startTest();

        Mail::assertQueued(UpdatesForAuditor::class);
    }

    /**
     * @param string $merchantId
     * @return mixed
     */
    public function setAuthAndCreateBankingAccount(string $merchantId)
    {
        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $this->ba->addXOriginHeader();

        $this->testCreateBankingAccount();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->ba->privateAuth('rzp_test', 'RANDOM_RBL_SECRET');
        return $bankingAccount;
    }

    public function testUpdateWithoutAppropriatePermission(){
        $this->detachAdminPermission(Permission\Name::VIEW_ACTIVATION_FORM);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->expectExceptionMessage(
            'Access Denied');

        $this->testUpdateBankingAccountDetails();
    }

    public function testCreateWithoutAppropriatePermission(){
        $this->detachAdminPermission(Permission\Name::VIEW_ACTIVATION_FORM);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->expectExceptionMessage(
            'Access Denied');

        $this->testCreateBankingAccountAdmin();
    }

    public function testCommentCreateWithoutAppropriatePermission(){
        $this->detachAdminPermission(Permission\Name::VIEW_ACTIVATION_FORM);

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $this->expectExceptionMessage(
            'Access Denied');

        $this->testCreateBankingAccountActivationComment();
    }

    public function addNewPermissionToExistingRole(string $permissionName)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $permission = $this->fixtures->on('test')->create('permission', [
            'name' => $permissionName
        ]);

        $role->permissions()->attach($permission->getId());
    }

    public function testSendOtpToContact()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $this->createBankingAccountFromDashboard();

        $this->startTest();
    }

    public function verifyContactSetup() :array
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $data = [
            Entity::PINCODE => '560030',
            Entity::CHANNEL => 'rbl',
            "activation_detail" => [
                "business_category"=> "partnership", 'sales_team' => 'self_serve',
            ]
        ];

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_dashboard',
            'content' => $data,
            'server'  => [
                'X-Dashboard-User-Id' => '20000000000000',
            ],
        ];

        $bankingAccount = $this->makeRequestAndGetContent($request);

        return [
            'request'  => [
                'url'     => '/banking_accounts/verify_otp/' . $bankingAccount['id'],
                'method'  => 'POST',
            ],
        ];
    }

    public function testVerifyOtpForContactForOwnerWithSameRblContact()
    {
        $dataToReplace = $this->verifyContactSetup();

        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 0,
                UserEntity::CONTACT_MOBILE          => '9999999999'
            ]);

        $this->testData[__FUNCTION__] = $this->testData['testVerifyOtpForContact'];

        $this->startTest($dataToReplace);

        $user = DB::connection('test')->table('users')
            ->where('id', '=', UserFixture::MERCHANT_USER_ID)
            ->pluck(UserEntity::CONTACT_MOBILE_VERIFIED)
            ->toArray();

        $this->assertEquals($user[0], 1);
    }

    public function testVerifyOtpForContactForOwnerWithDifferentRblContact()
    {
        $dataToReplace = $this->verifyContactSetup();

        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 0,
                UserEntity::CONTACT_MOBILE          => '9999999998'
            ]);

        $this->testData[__FUNCTION__] = $this->testData['testVerifyOtpForContact'];

        $this->startTest($dataToReplace);

        $user = DB::connection('test')->table('users')
            ->where('id', '=', UserFixture::MERCHANT_USER_ID)
            ->pluck(UserEntity::CONTACT_MOBILE_VERIFIED)
            ->toArray();

        $this->assertEquals($user[0], 0);
    }

    public function testVerifyOtpForContactForNonOwner()
    {
        $dataToReplace = $this->verifyContactSetup();

        $this->fixtures->edit('user', UserFixture::MERCHANT_USER_ID,
            [
                UserEntity::CONTACT_MOBILE_VERIFIED => 0,
                UserEntity::CONTACT_MOBILE          => '9999999999'
            ]);

        DB::table('merchant_users')->where('merchant_id', '=', '10000000000000')
            ->where('user_id', '=', UserFixture::MERCHANT_USER_ID)
            ->where('product', '=', 'banking')
            ->update(['role' => 'admin']);

        $this->testData[__FUNCTION__] = $this->testData['testVerifyOtpForContact'];

        $this->startTest($dataToReplace);

        $user = DB::connection('test')->table('users')
            ->where('id', '=', UserFixture::MERCHANT_USER_ID)
            ->pluck(UserEntity::CONTACT_MOBILE_VERIFIED)
            ->toArray();

        $this->assertEquals($user[0], 0);
    }

    public function testFetchBankingAccountForPayoutService()
    {
        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
            'balance_id'     => $xBalance1->getId(),
        ]);

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts/' . '2224440041626905' .'/10000000000000';

        $response = $this->startTest();

        $this->assertEquals($response[Entity::ACCOUNT_NUMBER], $ba1->getAccountNumber());
        $this->assertEquals($response[Entity::ID], $ba1->getId());
    }

    public function testFetchNonExistentBankingAccountForPayoutService()
    {
        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts/' . '2224440041626905' .'/10000000000000';

        $this->startTest();
    }

    public function testFetchNonExistentBankingAccountForPayoutServiceWithBalanceId()
    {
        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts_balance_id/1000000balance';

        $this->startTest();
    }

    public function testFetchBankingAccountForPayoutServiceInvalidMerchantId()
    {
        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts/' . '2224440041626905' .'/1';

        $this->startTest();
    }

    public function testFetchBankingAccountForPayoutServiceInvalidAccountNumber()
    {
        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts/' . '222' .'/10000000000000';

        $this->startTest();
    }

    public function testFetchBankingAccountWithBalanceIdForPayoutService()
    {
        $xBalance1 = $this->fixtures->create('balance',
            [
                'merchant_id'       => '10000000000000',
                'type'              => 'banking',
                'account_type'      => 'shared',
                'account_number'    => '2224440041626905',
                'balance'           => 200,
            ]);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $xBalance1->getId(),
        ]);

        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts_balance_id/' . $xBalance1->getId();

        $response = $this->startTest();

        $this->assertEquals($response[Entity::ID], $ba1->getId());
        $this->assertEquals($response[Entity::ACCOUNT_NUMBER], $ba1->getAccountNumber());
        $this->assertEquals($response[Entity::ACCOUNT_TYPE], $xBalance1->getAccountType());
        $this->assertEquals($response[Entity::BALANCE_ID], $xBalance1->getId());
        $this->assertEquals($response[Entity::BALANCE_TYPE], $xBalance1->getType());
    }

    public function testFetchBankingAccountForPayoutServiceWithInvalidBalanceId()
    {
        $this->ba->appAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts_balance_id/' . '1';

        $this->startTest();
    }

    public function testFetchBankingAccountBeneficiaryViaAccountNumberandIfsc()
    {
        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'yesbank',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'beneficiary_name'      => 'ACME PVT Ltd',
        ]);

        $this->fixtures->edit('banking_account', $ba1->getId(), [
            'account_number' => '2224440041626905',
        ]);

        $this->ba->bvsAppAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts_beneficiary/' . '2224440041626905' .'/RATN0000156';

        $response = $this->startTest();

        $this->assertEquals($response['beneficiary_name'], 'ACME PVT Ltd');

    }

    public function testFetchBankingAccountBeneficiaryViaAccountNumberandInvalidIfsc()
    {
        $this->ba->bvsAppAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts_beneficiary/' . '2224440041626905' .'/1';

        $this->startTest();
    }

    public function testFetchBankingAccountBeneficiaryViaInvalidAccountNumber()
    {
        $this->ba->bvsAppAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/banking_accounts_beneficiary/' . '222' .'/RATN0000156';

        $this->startTest();
    }

    public function testNotifyToSPOC()
    {
        Mail::fake();

        $bankingAccount = $this->testCreateActivationDetail();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'] ,
                              [
                                  'status' => Status::INITIATED,
                              ]);

        $request = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    Entity::SUB_STATUS => Status::MERCHANT_NOT_AVAILABLE,
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($request);

        Mail::assertQueued(MerchantNotAvailable::class);
    }

    public function testNotifyToSPOCForMerchantPreparingDoc()
    {
        Mail::fake();

        $this->fixtures->edit('merchant_detail', '10000000000000', [
            'activation_status'    => 'activated',
            'business_category'    => 'education',
            'business_subcategory' => 'college']);

        $bankingAccount = $this->testCreateActivationDetail();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'] ,
                              [
                                  'status' => Status::INITIATED,
                              ]);

        $request = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    Entity::SUB_STATUS => Status::MERCHANT_PREPARING_DOCS,
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($request);

        $createdAt = strtotime('- 5 day - 5 hours');

        $bankingAccountState = $this->getDbLastEntity('banking_account_state');

        $this->fixtures->edit('banking_account_state',
                              $bankingAccountState->getId(),
                              [
                                  'created_at' => $createdAt,
                              ]);

        $this->ba->cronAuth();

        $this->startTest();

        Mail::assertQueued(MerchantPreparingDoc::class, function ($mail) use($bankingAccount)
        {
            $mail->build();
            return $mail->hasTo('superadmin@razorpay.com');
        });
    }

    public function testNotifyToSPOCForDiscrepancyInDoc()
    {
        Mail::fake();

        $this->fixtures->edit('merchant_detail', '10000000000000', [
            'activation_status'    => 'activated',
            'business_category'    => 'education',
            'business_subcategory' => 'college']);

        $bankingAccount = $this->testCreateActivationDetail();

        $this->fixtures->edit('banking_account',
                              $bankingAccount['id'] ,
                              [
                                  'status' => Status::PROCESSING,
                              ]);

        $request = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    Entity::SUB_STATUS => Status::DISCREPANCY_IN_DOCS,
                ],
            ],
        ];

        $this->ba->adminAuth();

        $this->startTest($request);

        $bankingAccountState = $this->getDbLastEntity('banking_account_state');

        $createdAt = strtotime('- 5 day - 5 hours');

        $this->fixtures->edit('banking_account_state',
                              $bankingAccountState->getId(),
                              [
                                  'created_at' => $createdAt,
                              ]);

        $this->ba->cronAuth();

        $this->startTest();

        Mail::assertQueued(DiscrepancyInDoc::class, function ($mail) use($bankingAccount)
        {
            $mail->build();
            return $mail->hasTo('superadmin@razorpay.com');
        });
    }

    public function createMerchantAttribute(string $merchant_id, string $product, string $group, string $type, string $value)
    {
        $this->fixtures->create('merchant_attribute',
                                [
                                    'merchant_id'   => $merchant_id,
                                    'product'       => $product,
                                    'group'         => $group,
                                    'type'          => $type,
                                    'value'         => $value,
                                    'updated_at'    => time(),
                                    'created_at'    => time()
                                ]);
    }

    public function testArchiveAccount()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'RBL');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_proceeded_bank', 'RBL');

        $bankingAccount = $this->createBankingAccount();

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    Entity::STATUS => Status::ARCHIVED,
                ],
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->startTest($dataToReplace);

        $this->assertEquals(Status::ARCHIVED, $response[Entity::STATUS]);
    }

    public function verifyArchiveAndTerminateForActivatedAccount(string $status)
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'RBL');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_proceeded_bank', 'RBL');

        $this->testActivate();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $dataToReplace  = [
            'request' => [
                'url'     => '/banking_accounts/' . $bankingAccount->getPublicId(),
                'method'  => 'PATCH',
                'content' => [
                    Entity::STATUS => $status,
                ],
            ],
            'response' => [
                'content'     => [],
                'status_code' => 200,
            ]
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);

        $balance = $this->getDbEntity('balance',
            [
                'merchant_id'    => '10000000000000',
                'channel'        => 'rbl',
                'account_type'   => 'direct',
            ]);

        $basd = $this->getDbEntity('banking_account_statement_details',
            [
                'merchant_id'    => '10000000000000',
                'channel'        => 'rbl',
                'balance_id'     => $balance->getId(),
            ]);

        $this->assertEquals('archived', $basd->getStatus());
    }

    public function testArchiveAccountForActivatedAccount()
    {
        $this->verifyArchiveAndTerminateForActivatedAccount(Status::ARCHIVED);
    }

    public function testTerminateAccountForActivatedAccount()
    {
        $this->verifyArchiveAndTerminateForActivatedAccount(Status::TERMINATED);
    }

    protected function enableRazorXTreatmentForXOnboarding($ledgerOnboardingValue = 'control',
                                                           $ledgerReverseShadowOnboardingValue = 'control')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) use ($ledgerOnboardingValue, $ledgerReverseShadowOnboardingValue)
                {
                    if ($feature == Merchant\RazorxTreatment::DA_LEDGER_ONBOARDING)
                    {
                        return $ledgerOnboardingValue;
                    }

                    if ($feature == Merchant\RazorxTreatment::DA_LEDGER_ONBOARDING_REVERSE_SHADOW)
                    {
                        return $ledgerReverseShadowOnboardingValue;
                    }

                    return 'off';
                }));
    }

    protected function mockAuthServiceCreateApplication(Merchant\Entity $merchant, array $response = [], $times = 1)
    {
        // Mock create application call to auth service
        $requestParams = $this->getDefaultParamsForAuthServiceRequest();

        $createParams = [
            'merchant_id' => $merchant->getId(),
            'name'        => $merchant->getName(),
            'website'     => $merchant->getWebsite() ?: 'https://www.razorpay.com',
            'type'        => 'partner',
        ];

        $requestParams = array_merge($requestParams, $createParams);

        $this->setAuthServiceMockDetail('applications', 'POST', $requestParams, $times, $response);
    }

    public function testBankLmsEndToEnd()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        // Attach Submerchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $this->startTest();
    }

    public function activateBankingAccount(BankingAccount\Entity $bankingAccount)
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        // ledger shadow experiment is NOT enabled
        $this->app->razorx->method('getTreatment')
                          ->willReturn('control');

        $this->mockLedgerSns(0);

        Mail::fake();

        //$this->mockRaven();

        $this->fixtures->edit('banking_account', $bankingAccount->getId(), [
            'account_number'        => '1234567890',
            'beneficiary_state'     => 'karnataka',
            'beneficiary_country'   => 'india',
            'status'                => 'processed',
            'sub_status'            => 'api_onboarding_in_progress'
        ]);

        $this->setupDataForActivation($bankingAccount);

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $request = [
            'url' => '/banking_accounts/' . $bankingAccount->getPublicId() . '/activate',
            'method'  => 'POST',
            'content' => [],
        ];

        $this->mockFundAccountService();

        $expectedHubspotCall = false;

        $this->mockHubspotAndAssertForChangeEvent($expectedHubspotCall);

        $this->mockCardVault(function ()
        {
            return [
                'success' => true,
                'token'   => 'random'
            ];
        });

        $mozartResponse = $this->getMozartMockedResponse(camel_case(Rbl\Action::ACCOUNT_BALANCE . '_' . Rbl\Status::SUCCESS));

        $this->setMozartMockResponse($mozartResponse);

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('activated', $response['status']);
    }

    public function testBankLmsEndToEndForFilters()
    {
        $response = $this->setupBankLMSTest();

        $user = $response['user'];

        $bankingAccount = $response['bankingAccount'];

        $partnerMerchant = $this->getDbEntityById('merchant', self::DefaultPartnerMerchantId);

        $this->assignBankPocUserToApplication($partnerMerchant, $user, $bankingAccount['id']);

        $this->testBankLmsEndToEndPatchLead($bankingAccount);

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account',
                'content' => [
                    'status'                => 'doc_collection',
                    'sub_status'            => 'visit_due',
                    'assignee_team'         => 'bank',
                    'bank_poc_user_id'      => $user->getId(),
                    'api_onboarding_ftnr'   => 0,
                    'account_opening_ftnr'  => 0,
                    'branch_code'           => '202',
                    'rm_name'               => 'Gopal',
                    'due_on'                => '1660847460',
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndRevivedLeadWithoutSentToBank()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount =  $this->createBankingAccount();

        $this->ba->adminAuth();

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::CREATED, Status::PICKED,
            null, null,
            null, null,
            $bankingAccount);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::ARCHIVED,
            null, null,
            null, null,
            $bankingAccount);

        $dataToReplace = [
            'url' => '/banking_accounts/'. $bankingAccount['id'],
            'method' => 'PATCH',
            'content' => [
                'status' => 'picked'
            ]
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);

        $additionalDetails = $response[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ADDITIONAL_DETAILS];

        $this->assertEquals(null, $additionalDetails[BankingAccount\Activation\Detail\Entity::REVIVED_LEAD]);

    }

    public function testBankLmsEndToEndRevivedLeadWithSentToBank()
    {
        $response = $this->setupBankLMSTest();

        $user = $response['user'];

        $bankingAccount = $response['bankingAccount'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $bankingAccount);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        // Bank updates RM Details
        $dataToReplace = [
            'url' => '/banking_accounts/rbl/lms/banking_account/' . $bankingAccount['id'],
            'method' => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'rm_name' => 'Test RM'
                ]
            ]
        ];

        $result = $this->makeRequestAndGetContent($dataToReplace);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED, Status::ARCHIVED,
            null, null,
            null, null,
            $bankingAccount);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::ARCHIVED, Status::PICKED,
            null, null,
            null, null,
            $bankingAccount);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        // Data is not reset at RZP Processing stage
        $this->assertEquals('Test RM', $bankingAccountActivationDetail[ActivationDetail\Entity::RM_NAME]);

        $additionalDetails = json_decode($bankingAccountActivationDetail[ActivationDetail\Entity::ADDITIONAL_DETAILS], true);

        $this->assertEquals(true, $additionalDetails[BankingAccount\Activation\Detail\Entity::REVIVED_LEAD]);

        // Now when we move it to Sent to Bank, data should be reset
        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'url' => '/banking_accounts/rbl/lms/banking_account/' . $bankingAccount['id'],
            'method' => 'PATCH',
            'content' => [
                'status' => 'initiated',
                'sub_status' => 'none'
            ]
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);

        $this->assertEquals(null, $response[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::RM_NAME]);

        $additionalDetails = json_decode($response[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ADDITIONAL_DETAILS], true);

        $this->assertEquals(true, $additionalDetails[BankingAccount\Activation\Detail\Entity::REVIVED_LEAD]);

    }


    public function testBankLmsEndToEndRevivedLeadWithSentToBankAndNotLinkedToPartner()
    {

        $response = $this->setupBankLMSTest();

        $user = $response['user'];

        $response = $response['bankingAccount'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED, Status::ARCHIVED,
            null, null,
            null, null,
            $response);

        $this->ba->adminAuth();

        // detach to check the behaviour of revived lead functionality when application is not attached to the bank partner
        // in case of application not attached to the bank partner detaching functionality shouldn't be triggered
        // there are some cases where attachment to bank partner didn't happened.

        (new BankingAccount\BankLms\Service())->detachCaApplicationMerchantFromBankPartner(['banking_account_id' => $response['id']]);

        $dataToReplace = [
            'url'     => '/banking_accounts/'. $response['id'],
            'method' => 'PATCH',
            'content' => [
                'status' => 'initiated'
            ]
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);

        $additionalDetails = $response[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ADDITIONAL_DETAILS];

        $this->assertEquals(true, $additionalDetails[BankingAccount\Activation\Detail\Entity::REVIVED_LEAD]);

    }

    public function testBankLmsEndToEndForRevivedLeadFilter()
    {

        $response = $this->setupBankLMSTest();

        $user = $response['user'];

        $response = $response['bankingAccount'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED, Status::ARCHIVED,
            null, null,
            null, null,
            $response);


        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'url' => '/banking_accounts/rbl/lms/banking_account/' . $response['id'],
            'method' => 'PATCH',
            'content' => [
                'status' => 'initiated'
            ]
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);

        $url = '/banking_accounts/rbl/lms/banking_account?revived_lead=yes';

        $dataToReplace = [
            'request' => [
                'url' => $url,
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndForFilterByBankPoc()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        // Assign Bank Poc User to Application
        (new BankingAccount\BankLms\Service())->assignBankPartnerPocToApplication($response['id'], ['bank_poc_user_id' => $user->getId()]);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account?bank_poc_user_id='. $user->getId(),
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndForBusinessCategory()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account?business_category=partnership',
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndForGreenChannel()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount('10000000000000', [
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY   => 'partnership',
                ActivationDetail\Entity::SALES_TEAM          => 'self_serve',
                ActivationDetail\Entity::ADDITIONAL_DETAILS  => [
                    'green_channel' =>  true,
                ],
            ]
        ]);


        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
           Status::PICKED, Status::INITIATED,
           null, null,
           null, null,
           $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $url = '/banking_accounts/rbl/lms/banking_account?is_green_channel=yes';

        $dataToReplace = [
            'request' => [
                'url'     => $url,
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndForFeetOnStreet()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount('10000000000000', [
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY   => 'partnership',
                ActivationDetail\Entity::SALES_TEAM          => 'self_serve',
                ActivationDetail\Entity::ADDITIONAL_DETAILS  => [
                    'feet_on_street' =>  true,
                ],
            ]
        ]);


        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $url = '/banking_accounts/rbl/lms/banking_account?feet_on_street=yes';

        $dataToReplace = [
            'request' => [
                'url'     => $url,
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndSortBySentToBankDate()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount('10000000000000', [
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY   => 'partnership',
                ActivationDetail\Entity::SALES_TEAM          => 'self_serve',
                ActivationDetail\Entity::ADDITIONAL_DETAILS  => [
                    'green_channel' =>  true,
                ],
            ]
        ]);


        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
           Status::PICKED, Status::INITIATED,
           null, null,
           null, null,
           $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        // Now for sent to bank date we consider last event when the lead was sent to bank
        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account?sort_sent_to_bank_date=desc',
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndForLeadReceivedDateFilters()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED, Status::ARCHIVED,
            null, null,
            null, null,
            $response);

        sleep(3);

        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::ARCHIVED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        $state_timestamp = $this->getDbLastEntity('banking_account_state');

        sleep(3);
        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED, Status::INITIATED,
            null, Status::BANK_PICKED_UP_DOCS,
            null, null,
            $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        // Tests already exist here
        $url = sprintf('/banking_accounts/rbl/lms/banking_account?lead_received_from_date=%s&lead_received_to_date=%s',
            $state_timestamp->getCreatedAt(),
            $state_timestamp->getCreatedAt()
        );

        $dataToReplace = [
            'request' => [
                'url'     => $url,
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsBankAccountFetchWithFromDocketEstimatedDeliveryDateFilter()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        $ba1 = $this->createMerchantAndApplyForCurrentAccountAndAttachToBankPartnerAndAssignBankPartnerPoc('10000000000111',$user->getId());

        $this->fixtures->edit('banking_account_activation_detail', $ba1['banking_account_activation_details']['id'],
            [
                'banking_account_id' => substr($ba1['id'],5),
                'additional_details'        => json_encode([
                    'docket_estimated_delivery_date' => '1667346201'
                ])
            ]);

        $ba2 = $this->createMerchantAndApplyForCurrentAccountAndAttachToBankPartnerAndAssignBankPartnerPoc('10000000000112',$user->getId());

        $this->fixtures->edit('banking_account_activation_detail', $ba2['banking_account_activation_details']['id'],
            [
                'banking_account_id' => substr($ba2['id'],5),
                'additional_details'        => json_encode([
                    'docket_estimated_delivery_date' => '1667346101'
                ])
            ]);

        $ba3 = $this->createMerchantAndApplyForCurrentAccountAndAttachToBankPartnerAndAssignBankPartnerPoc('10000000000113',$user->getId());

        $this->fixtures->edit('banking_account_activation_detail', $ba3['banking_account_activation_details']['id'],
            [
                'banking_account_id' => substr($ba3['id'],5),
                'additional_details'        => json_encode([
                    'docket_estimated_delivery_date' => '1667349200'
                ])
            ]);

        $this->createMerchantAndApplyForCurrentAccountAndAttachToBankPartnerAndAssignBankPartnerPoc('10000000000114',$user->getId());

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account?from_docket_estimated_delivery_date=1667346200&to_docket_estimated_delivery_date=1667348200',
            ]
        ];

        $this->startTest($dataToReplace);
    }

    protected function createMerchantAndApplyForCurrentAccountAndAttachToBankPartnerAndAssignBankPartnerPoc(string $merchantId, string $bankPocUserId): array
    {
        $this->fixtures->create('merchant', ['id' => $merchantId]);
        $this->fixtures->merchant_detail->createAssociateMerchant([
            'merchant_id' => $merchantId]);
        $this->fixtures->user->createBankingUserForMerchant($merchantId);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount($merchantId);

        (new BankingAccount\BankLms\Service())->attachCaApplicationMerchantToBankPartner(['banking_account_id' => $response['id']]);

        (new BankingAccount\BankLms\Service())->assignBankPartnerPocToApplication($response['id'], ['bank_poc_user_id' => $bankPocUserId]);

        return $response;
    }

    public function testBankLmsEndToEndChangeAssigneeByStatusSubStatusChange()
    {

        $response = $this->setupBankLMSTest();
        $response = $response['bankingAccount'];

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::DOC_COLLECTION, Status::DOC_COLLECTION,
            Status::VISIT_DUE, Status::CUSTOMER_NOT_RESPONDING,
            null, null,
            $response);

        $activationDetailId = $response[BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS]['id'];

        $result = $this->getDbEntityById(RZP\Constants\Entity::BANKING_ACCOUNT_ACTIVATION_DETAIL ,
            $activationDetailId
        );
        $this->assertEquals(BankingAccount\Entity::OPS_MX_POC, $result[ActivationDetail\Entity::ASSIGNEE_TEAM]);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::DOC_COLLECTION, Status::DOC_COLLECTION,
            null, Status::VISIT_RESCHEDULED,
            null, null,
            $response);

        $result = $this->getDbEntityById(RZP\Constants\Entity::BANKING_ACCOUNT_ACTIVATION_DETAIL ,
            $activationDetailId
        );
        $this->assertEquals(ActivationDetail\Entity::BANK, $result[ActivationDetail\Entity::ASSIGNEE_TEAM]);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::DOC_COLLECTION, Status::DOC_COLLECTION,
            Status::CUSTOMER_NOT_RESPONDING, Status::VISIT_RESCHEDULED,
            null, null,
            $response);

        $result = $this->getDbEntityById(RZP\Constants\Entity::BANKING_ACCOUNT_ACTIVATION_DETAIL ,
            $activationDetailId
        );
        $this->assertEquals(ActivationDetail\Entity::BANK, $result[ActivationDetail\Entity::ASSIGNEE_TEAM]);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::API_ONBOARDING, Status::API_ONBOARDING,
            Status::IN_REVIEW, Status::API_REGISTRATION_NOT_COMPLETE,
            null, null,
            $response);

        $result = $this->getDbEntityById(RZP\Constants\Entity::BANKING_ACCOUNT_ACTIVATION_DETAIL ,
            $activationDetailId
        );
        $this->assertEquals(ActivationDetail\Entity::BANK_OPS, $result[ActivationDetail\Entity::ASSIGNEE_TEAM]);

    }

    public function testBankLmsEndToEndPatchLead(array $bankingAccount = null)
    {
        if ($bankingAccount === null)
        {
            $response = $this->setupBankLMSTest();
            $bankingAccount = $response['bankingAccount'];
        }

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccount['id'],
                'method'  => 'PATCH',
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsStageWise()
    {
        $response = $this->setupBankLMSTest();
        $bankPocUser = $response['user'];
        $bankingAccount = $response['bankingAccount'];
        $bankingAccountId = $bankingAccount['id'];

        $partnerMerchant = $this->getDbEntityById('merchant', self::DefaultPartnerMerchantId);

        $bankingAccountResponse = $this->assignBankPocUserToApplication($partnerMerchant, $bankPocUser, $bankingAccountId);

        // Status should be moved to verification_call after assignment
        // but currently disabled that part since we will support that by experimentation

        $verificationCallRequest = [
            'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccountId,
            'method'  => 'PATCH',
            'content' => [
                Entity::STATUS => Status::VERIFICATION_CALL,
                Entity::SUB_STATUS => Status::ASSIGNED_TO_PCARM,
                Entity::ACTIVATION_DETAIL => [
                    ActivationDetail\Entity::CUSTOMER_APPOINTMENT_DATE => '1666204200',
                    ActivationDetail\Entity::BRANCH_CODE => '4',
                    ActivationDetail\Entity::RM_EMPLOYEE_CODE => '32326',
                    ActivationDetail\Entity::RM_ASSIGNMENT_TYPE => 'pcarm',
                    ActivationDetail\Entity::RM_NAME => 'Amit Chopra',
                    ActivationDetail\Entity::RM_PHONE_NUMBER => '8872581146',
                    ActivationDetail\Entity::RBL_ACTIVATION_DETAILS => [
                        ActivationDetail\Entity::LEAD_IR_NUMBER => 'IR1234ABCD',
                        ActivationDetail\Entity::PCARM_MANAGER_NAME => 'Umakant Vashishtha',
                        ActivationDetail\Entity::OFFICE_DIFFERENT_LOCATIONS => true,
                    ]
                ]
            ],
        ];

        $bankingAccountResponse = $this->makeRequestAndGetContent($verificationCallRequest);

        $this->assertEquals(Status::DOC_COLLECTION, $bankingAccountResponse[Entity::STATUS]);
        $this->assertEquals(Status::VISIT_DUE, $bankingAccountResponse[Entity::SUB_STATUS]);

        $activationDetailsResponse = $bankingAccountResponse[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];
        $this->assertEquals('Umakant Vashishtha',
            $activationDetailsResponse[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS][ActivationDetail\Entity::PCARM_MANAGER_NAME]);

        // Bank Due date based on Customer Appointment Date for Doc Collection Stage
        $activationDetailsResponse = $bankingAccountResponse[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];
        $bankDueDate = Status::getBankDueDate(Status::DOC_COLLECTION, $activationDetailsResponse[ActivationDetail\Entity::CUSTOMER_APPOINTMENT_DATE]);
        $this->assertEquals($bankDueDate, $activationDetailsResponse[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS][ActivationDetail\Entity::BANK_DUE_DATE]);

        $docCollectionRequest = [
            'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccountId,
            'method'  => 'PATCH',
            'content' => [
                Entity::SUB_STATUS => Status::CUSTOMER_NOT_RESPONDING,
            ],
        ];

        $bankingAccountResponse = $this->makeRequestAndGetContent($docCollectionRequest);

        $this->assertEquals(Status::DOC_COLLECTION, $bankingAccountResponse[Entity::STATUS]);
        $this->assertEquals(Status::CUSTOMER_NOT_RESPONDING, $bankingAccountResponse[Entity::SUB_STATUS]);

        // Bank Due date does not change since there is only sub-status change
        $activationDetailsResponse = $bankingAccountResponse[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];
        $bankDueDate = Status::getBankDueDate(Status::DOC_COLLECTION, $activationDetailsResponse[ActivationDetail\Entity::CUSTOMER_APPOINTMENT_DATE]);
        $this->assertEquals($bankDueDate, $activationDetailsResponse[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS][ActivationDetail\Entity::BANK_DUE_DATE]);

        // Automatic Assignee Team change
        $this->assertEquals(BankingAccount\Entity::OPS_MX_POC,
            $bankingAccountResponse[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ASSIGNEE_TEAM]);


        $docCollectionRequest = [
            'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccountId,
            'method'  => 'PATCH',
            'content' => [
                Entity::SUB_STATUS => Status::PICKED_UP_DOCS,
                Entity::ACTIVATION_DETAIL => [
                    ActivationDetail\Entity::DOC_COLLECTION_DATE => "1666290600",
                    ActivationDetail\Entity::RBL_ACTIVATION_DETAILS => [
                        ActivationDetail\Entity::IP_CHEQUE_VALUE => 34000,
                        ActivationDetail\Entity::API_DOCS_DELAY_REASON => "Reason for CA Docs delay",
                        ActivationDetail\Entity::API_DOCS_RECEIVED_WITH_CA_DOCS => false,
                    ]
                ],
            ],
        ];

        $bankingAccountResponse = $this->makeRequestAndGetContent($docCollectionRequest);

        $this->assertEquals(Status::ACCOUNT_OPENING, $bankingAccountResponse[Entity::STATUS]);
        $this->assertEquals(Status::IN_REVIEW, $bankingAccountResponse[Entity::SUB_STATUS]);

        // Bank Due date based on Doc Collection Date for Account Opening Stage
        // Doc Collection Date + 2 days = Sunday. 23rd Oct therefore due date will be 24th Oct, Monday
        $activationDetailsResponse = $bankingAccountResponse[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];
        $bankDueDate = Status::getBankDueDate(Status::ACCOUNT_OPENING, $activationDetailsResponse[ActivationDetail\Entity::DOC_COLLECTION_DATE]);
        $this->assertEquals($bankDueDate, $activationDetailsResponse[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS][ActivationDetail\Entity::BANK_DUE_DATE]);

        $accountOpeningRequest = [
            'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccountId,
            'method'  => 'PATCH',
            'content' => [
                Entity::STATUS => Status::API_ONBOARDING,
                Entity::SUB_STATUS => Status::IN_REVIEW,
                Entity::ACTIVATION_DETAIL => [
                    ActivationDetail\Entity::ACCOUNT_LOGIN_DATE => '1666290600',
                    ActivationDetail\Entity::ACCOUNT_OPEN_DATE => '1666549800',
                    ActivationDetail\Entity::ACCOUNT_OPENING_IR_CLOSE_DATE => '1666549800',
                    ActivationDetail\Entity::ACCOUNT_OPENING_FTNR => true,
                    ActivationDetail\Entity::ACCOUNT_OPENING_FTNR_REASONS => 'AO RRT/Attachment/Details Issue,AO Scanning Issue',
                    ActivationDetail\Entity::RBL_ACTIVATION_DETAILS => [
                        ActivationDetail\Entity::ACCOUNT_OPENING_IR_NUMBER => 'IR1212PQRS',
                        ActivationDetail\Entity::SR_NUMBER => '0989766',
                        ActivationDetail\Entity::CASE_LOGIN_DIFFERENT_LOCATIONS => true,
                        ActivationDetail\Entity::REVISED_DECLARATION => true
                    ]
                ],
            ]
        ];

        $bankingAccountResponse = $this->makeRequestAndGetContent($accountOpeningRequest);

        $this->assertEquals(Status::API_ONBOARDING, $bankingAccountResponse[Entity::STATUS]);
        $this->assertEquals(Status::IN_REVIEW, $bankingAccountResponse[Entity::SUB_STATUS]);

        Mail::assertQueued(ActivationMails\StatusChange::class, function ($mail)
        {
            $mail->build();

            $recipientEmails = array_column($mail->to,'address');

            $this->assertArraySelectiveEquals(['x-onboarding@razorpay.com','x-caonboarding@razorpay.com'],$recipientEmails);

            return true;
        });

        // Bank Due date based on Account Open Date for API Onboarding Stage
        $activationDetailsResponse = $bankingAccountResponse[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];
        $bankDueDate = Status::getBankDueDate(Status::API_ONBOARDING, $activationDetailsResponse[ActivationDetail\Entity::ACCOUNT_OPEN_DATE]);
        $this->assertEquals($bankDueDate, $activationDetailsResponse[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS][ActivationDetail\Entity::BANK_DUE_DATE]);

        $apiOnboardingRequest = [
            'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccountId,
            'method'  => 'PATCH',
            'content' => [
                Entity::SUB_STATUS => Status::API_IR_CLOSED,
                Entity::ACTIVATION_DETAIL => [
                    ActivationDetail\Entity::API_IR_CLOSED_DATE => '1666722600',
                    ActivationDetail\Entity::API_ONBOARDING_FTNR => false,
                    ActivationDetail\Entity::API_ONBOARDING_FTNR_REASONS => 'AO RRT/Attachment/Details Issue,AO Scanning Issue',
                    ActivationDetail\Entity::RBL_ACTIVATION_DETAILS => [
                        ActivationDetail\Entity::API_IR_NUMBER => 'IR090909'
                    ],
                    ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                        ActivationDetail\Entity::API_ONBOARDING_LOGIN_DATE => '1666549800'
                    ]
                ]
            ]
        ];

        $bankingAccountResponse = $this->makeRequestAndGetContent($apiOnboardingRequest);

        $this->assertEquals(Status::ACCOUNT_ACTIVATION, $bankingAccountResponse[Entity::STATUS]);
        $this->assertEquals(Status::IN_PROCESS, $bankingAccountResponse[Entity::SUB_STATUS]);

        // Bank Due date based on API IR Closed Date for Account Activation Stage
        $activationDetailsResponse = $bankingAccountResponse[Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS];
        $bankDueDate = Status::getBankDueDate(Status::ACCOUNT_ACTIVATION, $activationDetailsResponse[ActivationDetail\Entity::API_IR_CLOSED_DATE]);
        $this->assertEquals($bankDueDate, $activationDetailsResponse[ActivationDetail\Entity::RBL_ACTIVATION_DETAILS][ActivationDetail\Entity::BANK_DUE_DATE]);

    }

    public function testBankLmsEndToEndPartnerChangeAssignee()
    {
        $response = $this->setupBankLMSTest();
        $bankingAccount = $response['bankingAccount'];

        $this->ba->addXOriginHeader();

        $dataToReplace = [
            'url' => '/banking_accounts/rbl/lms/banking_account/' . $bankingAccount['id'],
            'method' => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'assignee_team' => 'bank',
                    'comment' => [
                        'source_team' => 'bank',
                        'added_at' => '1663065060',
                        'comment' => '<p>something</p>',
                        'source_team_type' => 'external',
                        'type' => 'external'
                    ]
                ]
            ]
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);
        $expectedComment = $this->getDbLastEntity('banking_account_comment');

        $this->assertEquals($response[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ASSIGNEE_TEAM], ActivationDetail\Entity::BANK);
        $this->assertEquals($expectedComment->comment, "<p>something</p>");
    }

    public function testBankLmsEndToEndForLeadReceivedDateFiltersNegativecase()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        // Attach Sub-merchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        sleep(3);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::INITIATED, Status::INITIATED,
            null, Status::BANK_PICKED_UP_DOCS,
            null, null,
            $response);

        $state_timestamp = $this->getDbLastEntity('banking_account_state');

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        // We should get zero results because we are searching with old sent to bank event
        $url = sprintf('/banking_accounts/rbl/lms/banking_account?lead_received_from_date=%s&lead_received_to_date=%s',
            $state_timestamp->getCreatedAt(),
            $state_timestamp->getCreatedAt()
        );

        $dataToReplace = [
            'request' => [
                'url'     => $url,
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndForFetchById()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // Attach Submerchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account/'. $response['id'],
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndForGetBranchList()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();


        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // Attach Submerchant to RBl Merchant
       $expected =  (new BankingAccount\BankLms\BranchMaster())->getBranches();

        $dataToReplace = [
                'url'     => '/banking_accounts/rbl/lms/bank_branches',
                'method'  => 'GET',

        ];

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();
        $response = $this->makeRequestAndGetContent($dataToReplace);
       // print_r($response);

       // $this->startTest($dataToReplace);
       $this->assertEquals(sizeof($expected), $response['count']);

    }

    public function testBankLmsEndToEndForGetRmList()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();


        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // Attach Submerchant to RBl Merchant
        $expected =  (new BankingAccount\BankLms\RmMaster())->getRms();

        $dataToReplace = [
                'url'     => '/banking_accounts/rbl/lms/bank_pocs',
                'method'  => 'GET',
        ];

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();
        $response = $this->makeRequestAndGetContent($dataToReplace);
        $this->assertEquals(sizeof($expected), $response['count']);
    }

    public function testBankLmsEndToEndRblActivationDetailPayload()
    {
        $response = $this->setupBankLMSTest();
        $user = $response['user'];
        $response = $response['bankingAccount'];

        $docCollectionDate = '1665567466';

        $apiIRClosedDate = '1665826666';

        $this->ba->addXOriginHeader();

        $this->ba->adminAuth();

        $dataToReplace = [
            'url' => '/banking_accounts/' . $response['id'],
            'method' => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'api_ir_closed_date' => $apiIRClosedDate,
                    'customer_appointment_date' => '1665481066',
                    'doc_collection_date' => $docCollectionDate,
                ],
            ],
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'url' => '/banking_accounts/rbl/lms/banking_account/' . $response['id'],
            'method' => 'PATCH',
            'content' => [
                'activation_detail' => [
                    ActivationDetail\Entity::RBL_ACTIVATION_DETAILS => [
                        ActivationDetail\Entity::API_SERVICE_FIRST_QUERY => 'API_SERVICE_FIRST_QUERY',
                        ActivationDetail\Entity::API_BEYOND_TAT => true,
                        ActivationDetail\Entity::API_BEYOND_TAT_DEPENDENCY => 'razorpay',
                        ActivationDetail\Entity::FIRST_CALLING_TIME => 'FIRST_CALLING_TIME',
                        ActivationDetail\Entity::SECOND_CALLING_TIME => 'SECOND_CALLING_TIME',
                        ActivationDetail\Entity::WA_MESSAGE_SENT_DATE => '1665567466',
                        ActivationDetail\Entity::WA_MESSAGE_RESPONSE_DATE => '1665567466',
                        ActivationDetail\Entity::API_DOCKET_RELATED_ISSUE => 'API_DOCKET_RELATED_ISSUE',
                        ActivationDetail\Entity::AOF_SHARED_WITH_MO => false,
                        ActivationDetail\Entity::AOF_SHARED_DISCREPANCY => false,
                        ActivationDetail\Entity::AOF_NOT_SHARED_REASON => 'AOF_NOT_SHARED_REASON',
                        ActivationDetail\Entity::CA_BEYOND_TAT_DEPENDENCY => 'client',
                        ActivationDetail\Entity::CA_BEYOND_TAT => false,
                        ActivationDetail\Entity::CA_SERVICE_FIRST_QUERY => 'CA_SERVICE_FIRST_QUERY',
                        ActivationDetail\Entity::LEAD_IR_STATUS => 'ir_raised',
                    ]
                ],
            ],
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);

        $customerAppointmentBookingDate = Carbon::createFromTimestamp((Carbon::now()->timestamp), Timezone::IST)->format('Y-m-d') ;

        $actualCustomerAppointmentBookingDate = $response[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::RBL_ACTIVATION_DETAILS][ActivationDetail\Entity::CUSTOMER_APPOINTMENT_BOOKING_DATE];

        $actualCustomerAppointmentBookingDate = Carbon::createFromTimestamp(($actualCustomerAppointmentBookingDate), Timezone::IST)->format('Y-m-d') ?? '';

        $this->assertEquals($customerAppointmentBookingDate,$actualCustomerAppointmentBookingDate );

        $this->assertEquals(ActivationDetail\Entity::hourDifferenceBetweenTimestamps($docCollectionDate, $apiIRClosedDate), $response[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::CUSTOMER_ONBOARDING_TAT]);

    }


    public function testBankLmsEndToEndParallelAssigneePartnerSide()
    {

        $response = $this->setupBankLMSTest();
        $user = $response['user'];
        $response = $response['bankingAccount'];


        $this->assertUpdateBankingAccountStatusFromTo(
            Status::DOC_COLLECTION, Status::DOC_COLLECTION,
            Status::VISIT_DUE, Status::CUSTOMER_NOT_RESPONDING,
            null, null,
            $response);

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'url' => '/banking_accounts/rbl/lms/banking_account/' . $response['id'],
            'method' => 'PATCH',
            'content' => [
                'status' => 'account_opening',
                'sub_status' => 'ir_raised',
            ]
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);
        $this->assertEquals(ActivationDetail\Entity::BANK, $response[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ASSIGNEE_TEAM]);
    }

    public function testBankLmsEndToEndParallelAssigneeRZPSide()
    {

        $attribute = ['activation_status' => 'activated'];
        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);
        $this->ba->addXOriginHeader();
        $bankingAccount =  $this->createBankingAccount();
        $this->ba->adminAuth();

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::DOC_COLLECTION, Status::DOC_COLLECTION,
            Status::VISIT_DUE, Status::CUSTOMER_NOT_RESPONDING,
            null, null,
            $bankingAccount);

        $dataToReplace = [
                'url'     => '/banking_accounts/'. $bankingAccount['id'],
                'method'  => 'PATCH',
                'content' => [
                    'status' => 'account_opening',
                    'sub_status' => 'ir_raised',
                ],
        ];
        $response = $this->makeRequestAndGetContent($dataToReplace);
        $this->assertEquals(ActivationDetail\Entity::OPS, $response[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ASSIGNEE_TEAM]);
    }

    public function testBankLmsEndToEndParallelAssigneeLDAPIDFilledRZPSide()
    {

        $attribute = ['activation_status' => 'activated'];
        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);
        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);
        $this->ba->addXOriginHeader();
        $bankingAccount =  $this->createBankingAccount();
        $this->ba->adminAuth();

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::ACCOUNT_OPENING, Status::ACCOUNT_OPENING,
            Status::IN_REVIEW, Status::IR_RAISED,
            null, null,
            $bankingAccount);

        $dataToReplace = [
            'url'     => '/banking_accounts/'. $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'ldap_id_mail_date' => 1661845404,
                ]
            ],
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);
        $this->assertEquals(ActivationDetail\Entity::BANK, $response[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ASSIGNEE_TEAM]);

        $dataToReplace = [
            'url'     => '/banking_accounts/'. $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                'activation_detail' => [
                    'assignee_team' => 'ops',
                    'comment' => [
                        'comment' => 'sample comment while changing assignee',
                        'source_team' => 'ops',
                        'source_team_type' => 'internal',
                        'type' => 'internal',
                        'added_at' => 1597217557
                    ]
                ]
            ],
        ];

        $response = $this->makeRequestAndGetContent($dataToReplace);
        $this->assertEquals(ActivationDetail\Entity::OPS, $response[ActivationDetail\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS][ActivationDetail\Entity::ASSIGNEE_TEAM]);
    }

    public function testBankLmsEndToEndCommentsCreate()
    {
        $response = $this->setupBankLMSTest();

        $bankingAccount = $response['bankingAccount'];

        $user = $response['user'];

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/activation/'. $bankingAccount['id'].'/comments',
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndCommentsFetch()
    {
        $response = $this->setupBankLMSTest();

        $bankingAccount = $response['bankingAccount'];

        $user = $response['user'];

        $this->testCreateBankingAccountActivationComment($bankingAccount);

        $this->testBankLmsEndToEndCommentsCreate();

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/activation/'. $bankingAccount['id'].'/comments',
            ]
        ];

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $this->startTest($dataToReplace);
    }

    public function testBankLmsEndToEndActivity()
    {
        $response = $this->setupBankLMSTest();

        $bankingAccount = $response['bankingAccount'];

        $user = $response['user'];

        $this->testCreateBankingAccountActivationComment($bankingAccount);

        $this->testBankLmsEndToEndCommentsCreate();

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/activation/'. $bankingAccount['id'].'/activity',
            ]
        ];

        $this->startTest($dataToReplace);

    }

    public function testAssignPartnerBulk()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // New Merchant Apply for Current Account
        $bankingAccount = $this->MerchantApplyForCurrentAccount('10000000000000');

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/rbl/lms/banking_account/assign_partner',
                'content' => [
                    'banking_account_ids' => [
                        $bankingAccount['id']
                    ]
                ],
            ]
        ];

        $this->ba->adminAuth();

        $this->startTest($dataToReplace);
    }

    public function setupBankLMSTest(): array
    {
        if ($this->bankLMSSetupComplete === true)
        {
            return $this->bankLMSSetupResponse;
        }

        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $invitation = $this->inviteNewUserToJoinRBLMerchant(self::DefaultPartnerMerchantId, 'random@rbl.com', BankingRole::BANK_MID_OFFICE_MANAGER);

        // Accept invitation
        $response = $this->acceptInvitation();

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        Mail::fake();

        // Attach Submerchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        Mail::assertNotQueued(ActivationMails\BankPartnerAssigned::class, function ($mail) use ($user)
        {
            $mail->build();
            return $mail->hasTo($user->getEmail());
        });

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $setupResponse = [
            'bankingAccount' => $response,
            'user' => $user,
        ];

        $this->bankLMSSetupComplete = true;
        $this->bankLMSSetupResponse = $setupResponse;

        return $setupResponse;
    }

    public function testBankingAccountLeadsMISRequestByBank()
    {
        $this->setupBankLMSTest();

        $this->startTest();

        Mail::assertNotQueued(ActivationMails\BankPartnerAssigned::class);
        Mail::assertNotQueued(BankingAccountMails\Reports\LeadMisReport::class);
    }

    public function testCustomerAppointmentDateOptions()
    {
        $this->ba->adminAuth();

        $city = 'ghaziabad';

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts/customer_appointment_dates/'.$city,
            ],
        ];

        $now = Carbon::create(2023, 01, 19);

        Carbon::setTestNow($now);

        $this->startTest($dataToReplace);
    }

    public function testBankingAccountLeadsMISDownloadByBank()
    {
        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant();

        // Accept invitation
        $response = $this->acceptInvitation();

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccount();

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        $this->prepareActivationDetail([
            ActivationDetail\Entity::ASSIGNEE_TEAM => 'bank',
            ActivationDetail\Entity::COMMENT => 'Sample comment',
            ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
            ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'Financial Consultant',
            ActivationDetail\Entity::MERCHANT_POC_EMAIL => 'sample@sample.com',
            ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER => '9876556789',
            ActivationDetail\Entity::ACCOUNT_TYPE => 'insignia',
            ActivationDetail\Entity::EXPECTED_MONTHLY_GMV => 40000,
            ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE => 0,
            ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
            ActivationDetail\Entity::INITIAL_CHEQUE_VALUE => 222,
            ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                ActivationDetail\Entity::GREEN_CHANNEL => true,
                ActivationDetail\Entity::FEET_ON_STREET => true,
                ActivationDetail\Entity::DOCKET_DELIVERED_DATE => '1665308266',
                ActivationDetail\Entity::API_ONBOARDING_LOGIN_DATE => '1665740266',
                ActivationDetail\Entity::API_ONBOARDED_DATE => '1665826666',
            ],
        ]);

        // Attach Submerchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $bankingAccount);

        $spocId = DB::table('admin_audit_map')->where('entity_id','=',$bankingAccount['id'])->value('admin_id');

        $stateLog = $this->fixtures->create('banking_account_state', [
            BankingAccount\State\Entity::BANKING_ACCOUNT_ID => $bankingAccountEntity->getId(),
            BankingAccount\State\Entity::STATUS => Status::INITIATED,
            BankingAccount\State\Entity::ADMIN_ID => $spocId
        ]);

        $docCollectionDate = '1665567466';

        $apiIrClosedDate = '1665826666';

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        (new BankingAccount\BankLms\Service())->assignBankPartnerPocToApplication($bankingAccount['id'], ['bank_poc_user_id' => $user->getId()]);

        $request = [
            'url'     => '/banking_accounts/rbl/lms/banking_account/'. $bankingAccount['id'],
            'method'  => 'PATCH',
            'content' => [
                BankingAccount\Entity::ACTIVATION_DETAIL => [
                    'comment' => [
                        'comment' => '<p>Sample comment</p>',
                        'source_team' => 'bank',
                        'source_team_type' => 'internal',
                        'type' => 'external',
                        'added_at' => 1597217557
                    ],
                    ActivationDetail\Entity::RBL_ACTIVATION_DETAILS => [
                        ActivationDetail\Entity::LEAD_REFERRED_BY_RBL_STAFF => true,
                        ActivationDetail\Entity::OFFICE_DIFFERENT_LOCATIONS => false,
                        ActivationDetail\Entity::LEAD_IR_NUMBER => 'IR101',
                        ActivationDetail\Entity::SR_NUMBER => 'SR101',
                        ActivationDetail\Entity::PROMO_CODE => 'RZP123',
                        ActivationDetail\Entity::API_DOCS_RECEIVED_WITH_CA_DOCS => false,
                        ActivationDetail\Entity::API_DOCS_DELAY_REASON => 'For some XYZ Reason',
                        ActivationDetail\Entity::REVISED_DECLARATION => false,
                        ActivationDetail\Entity::ACCOUNT_OPENING_IR_NUMBER => 'IR102',
                        ActivationDetail\Entity::API_IR_NUMBER => 'IR103',
                        ActivationDetail\Entity::ACCOUNT_OPENING_TAT_EXCEPTION => true,
                        ActivationDetail\Entity::ACCOUNT_OPENING_TAT_EXCEPTION_REASON => 'ACCOUNT_OPENING_TAT_EXCEPTION_REASON',
                        ActivationDetail\Entity::API_ONBOARDING_TAT_EXCEPTION => true,
                        ActivationDetail\Entity::API_ONBOARDING_TAT_EXCEPTION_REASON => 'API_ONBOARDING_TAT_EXCEPTION_REASON',
                        ActivationDetail\Entity::UPI_CREDENTIAL_NOT_DONE_REMARKS => 'UPI_CREDENTIAL_NOT_DONE_REMARKS',
                        ActivationDetail\Entity::API_SERVICE_FIRST_QUERY => 'API_SERVICE_FIRST_QUERY',
                        ActivationDetail\Entity::API_BEYOND_TAT => true,
                        ActivationDetail\Entity::API_BEYOND_TAT_DEPENDENCY => 'razorpay',
                        ActivationDetail\Entity::FIRST_CALLING_TIME => 'FIRST_CALLING_TIME',
                        ActivationDetail\Entity::SECOND_CALLING_TIME => 'SECOND_CALLING_TIME',
                        ActivationDetail\Entity::WA_MESSAGE_SENT_DATE => '1665567466',
                        ActivationDetail\Entity::WA_MESSAGE_RESPONSE_DATE => '1665567466',
                        ActivationDetail\Entity::API_DOCKET_RELATED_ISSUE => 'API_DOCKET_RELATED_ISSUE',
                        ActivationDetail\Entity::AOF_SHARED_WITH_MO => false,
                        ActivationDetail\Entity::AOF_SHARED_DISCREPANCY => false,
                        ActivationDetail\Entity::AOF_NOT_SHARED_REASON => 'AOF_NOT_SHARED_REASON',
                        ActivationDetail\Entity::CA_BEYOND_TAT_DEPENDENCY => 'client',
                        ActivationDetail\Entity::CA_BEYOND_TAT => false,
                        ActivationDetail\Entity::CA_SERVICE_FIRST_QUERY => 'CA_SERVICE_FIRST_QUERY',
                        ActivationDetail\Entity::LEAD_IR_STATUS => 'ir_raised',
                    ],
                    ActivationDetail\Entity::RM_EMPLOYEE_CODE => '24128',
                    ActivationDetail\Entity::RM_NAME => 'Sachin s',
                    ActivationDetail\Entity::RM_PHONE_NUMBER => '9535362154',
                    ActivationDetail\Entity::BRANCH_CODE => '195',
                    ActivationDetail\Entity::ACCOUNT_OPENING_FTNR => true,
                    ActivationDetail\Entity::ACCOUNT_OPENING_FTNR_REASONS => 'Reason 1,Reason 2',
                    ActivationDetail\Entity::API_ONBOARDING_FTNR => true,
                    ActivationDetail\Entity::API_ONBOARDING_FTNR_REASONS => 'Reason 3,Reason 4',
                    ActivationDetail\Entity::CUSTOMER_APPOINTMENT_DATE => '1665481066',
                    ActivationDetail\Entity::DOC_COLLECTION_DATE => $docCollectionDate,
                    ActivationDetail\Entity::ACCOUNT_LOGIN_DATE => '1665567466',
                    ActivationDetail\Entity::ACCOUNT_OPENING_IR_CLOSE_DATE => '1665740266',
                    ActivationDetail\Entity::API_IR_CLOSED_DATE => $apiIrClosedDate,
                    ActivationDetail\Entity::ACCOUNT_OPEN_DATE => '1665481066',
                ]
            ],
        ];

        $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $this->makeRequestAndGetContent($request);

        $this->fixtures->edit('user', $user->getId(), ['name' => 'Umakant Vashishtha']);

        $misProcessor = new MIS\Leads(
            [
                RZP\Models\BankingAccount\BankLms\Entity::BANK_POC_USER_ID => $user->getId()
            ],
            'banking_account_bank_lms'
        );

        $rows = $misProcessor->getFileInput();

        $this->assertCount(100, $rows, 'Count should be limited to 100');

        // Assigning first value of array to fileinput to test with the input we created (latest)
        $fileInput[0] = $rows[0];

        $sentToBankTimestamp = $stateLog['created_at'];

        $sentToBankDate = Carbon::createFromTimestamp($sentToBankTimestamp, Timezone::IST)->format('Y-m-d') ?? '';
        $sentToBankTime = Carbon::createFromTimestamp($sentToBankTimestamp, Timezone::IST)->format('h:i A') ?? '';;

        $customerAppointmentBookingDate = Carbon::createFromTimestamp((Carbon::now()->timestamp), Timezone::IST)->format('Y-m-d') ;

        $customerOnboardingTat = ActivationDetail\Entity::hourDifferenceBetweenTimestamps($docCollectionDate, $apiIrClosedDate, true);

        if (is_double($customerOnboardingTat) === true)
        {
            $customerOnboardingTat = round($customerOnboardingTat / 24);
        }

        $expectedFileInput = [
            [
                Leads::RZP_REF_NO                         => '10000',
                Leads::MERCHANT_NAME                      =>  $bankingAccountEntity->merchant->name,
                Leads::MERCHANT_POC_NAME                  => 'Sample Name',
                Leads::MERCHANT_POC_DESIGNATION           => 'Financial Consultant',
                Leads::MERCHANT_POC_EMAIL                 => 'sample@sample.com',
                Leads::MERCHANT_POC_PHONE                 => '9876556789',
                Leads::PINCODE                            => $bankingAccountEntity->getPincode(),
                Leads::MERCHANT_CITY                      => 'Bangalore',
                Leads::CONSTITUTION_TYPE                  => 'Partnership',
                Leads::MERCHANT_ICV                       => 222,
                Leads::APPLICATION_SUBMISSION_DATE        => $sentToBankDate,
                Leads::TIMESTAMP                          => $sentToBankTime,
                Leads::BUSINESS_MODEL                     => null,
                Leads::ACCOUNT_TYPE                       => 'Insignia',
                Leads::COMMENT                            => 'Sample comment',
                Leads::EXPECTED_MONTHLY_GMV               => 40000,
                Leads::SALES_POC                          => $bankingAccountEntity->spocs()->first()->name,
                Leads::SALES_POC_PHONE_NUMBER             => $bankingAccountEntity->bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER],
                Leads::GREEN_CHANNEL                      => 'Yes',
                Leads::FOS                                => 'Yes',
                Leads::REVIVED_LEAD                       => '',
                Leads::OPS_POC_NAME                       => '',
                Leads::OPS_POC_EMAIL                      => '',
                Leads::DOCKET_DELIVERY_DATE               => '2022-10-09',
                Leads::STATUS                             => 'VerificationCall',
                Leads::SUB_STATUS                         => 'In Processing',
                Leads::ASSIGNEE                           => 'Bank',
                Leads::MID_OFFICE_POC                     => 'Umakant Vashishtha',
                Leads::LEAD_REFERRED_BY_RBL_STAFF         => 'Yes',
                Leads::OFFICE_AT_DIFFERENT_LOCATIONS      => 'No',
                Leads::CUSTOMER_APPOINTMENT_DATE          => '2022-10-11',
                Leads::APPOINTMENT_TAT                    => null,
                Leads::LEAD_IR_NO                         => 'IR101',
                Leads::RM_NAME                            => 'Sachin s',
                Leads::RM_MOBILE_NO                       => '9535362154',
                Leads::BRANCH_CODE                        => '195',
                Leads::BRANCH_NAME                        => 'Jp Nagar',
                Leads::BM                                 => 'Venkatathri B',
                Leads::BM_MOBILE_NO                       => 8291282195,
                Leads::TL                                 => 'Manjunath R',
                Leads::CLUSTER                            => 'Blr 2',
                Leads::REGION                             => 'South & Goa Region',
                Leads::DOC_COLLECTION_DATE                => '2022-10-12',
                Leads::DOC_COLLECTION_TAT                 => 1.0,
                Leads::IP_CHEQUE_VALUE                    => null,
                Leads::API_DOCS_RECEIVED_WITH_CA_DOCS     => 'No',
                Leads::API_DOC_DELAY_REASON               => 'For some XYZ Reason',
                Leads::REVISED_DECLARATION                => 'No',
                Leads::ACCOUNT_IR_NO                      => 'IR102',
                Leads::ACCT_LOGIN_DATE                    => '2022-10-12',
                Leads::IR_LOGIN_TAT                       => 0.0,
                Leads::PROMO_CODE                         => 'RZP123',
                Leads::CASE_LOGIN                         => '',
                Leads::SR_NO                              => 'SR101',
                Leads::ACCOUNT_OPEN_DATE                  => '2019-07-10',
                Leads::ACCOUNT_IR_CLOSED_DATE             => '2022-10-11',
                Leads::AO_FTNR                            => 'Yes',
                Leads::AO_FTNR_REASONS                    => 'Reason 1,Reason 2',
                Leads::AO_TAT_EXCEPTION                   => 'Yes',
                Leads::AO_TAT_EXCEPTION_REASON            => 'ACCOUNT_OPENING_TAT_EXCEPTION_REASON',
                Leads::API_IR_NO                          => 'IR103',
                Leads::API_IR_LOGIN_DATE                  => '2022-10-14',
                Leads::LDAP_ID_MAIL_DATE                  => '',
                Leads::API_REQUEST_TAT                    => 852.0,
                Leads::API_IR_CLOSED_DATE                 => '2022-10-15',
                Leads::API_REQUEST_PROCESSING_TAT         => 1.0,
                Leads::API_FTNR                           => 'Yes',
                Leads::API_FTNR_REASONS                   => 'Reason 3,Reason 4',
                Leads::API_TAT_EXCEPTION                  => 'Yes',
                Leads::API_TAT_EXCEPTION_REASON           => 'API_ONBOARDING_TAT_EXCEPTION_REASON',
                Leads::CORP_ID_MAIL_DATE                  => '2022-10-15',
                Leads::RZP_CA_ACTIVATED_DATE              => '',
                Leads::UPI_CREDENTIALS_DATE               => '',
                Leads::UPI_CREDENTIALS_NOT_DONE_REMARKS   => 'UPI_CREDENTIAL_NOT_DONE_REMARKS',
                Leads::DROP_OFF_DATE                      => '',
                Leads::API_SERVICE_FIRST_QUERY            => 'API_SERVICE_FIRST_QUERY',
                Leads::API_BEYOND_TAT                     => 'Yes',
                Leads::API_BEYOND_TAT_DEPENDENCY          => 'razorpay',
                Leads::FIRST_CALLING_TIME                 => 'FIRST_CALLING_TIME',
                Leads::SECOND_CALLING_TIME                => 'SECOND_CALLING_TIME',
                Leads::WA_MESSAGE_SENT_DATE               => '2022-10-12',
                Leads::WA_MESSAGE_RESPONSE_DATE           => '2022-10-12',
                Leads::API_DOCKET_RELATED_ISSUE           => 'API_DOCKET_RELATED_ISSUE',
                Leads::AOF_SHARED_WITH_MO                 => 'No',
                Leads::AOF_SHARED_DISCREPANCY             => 'No',
                Leads::AOF_NOT_SHARED_REASON              => 'AOF_NOT_SHARED_REASON',
                Leads::CA_BEYOND_TAT_DEPENDENCY           => 'client',
                Leads::CA_BEYOND_TAT                      => 'No',
                Leads::CA_SERVICE_FIRST_QUERY             => 'CA_SERVICE_FIRST_QUERY',
                Leads::LEAD_IR_STATUS                     => 'ir_raised',
                Leads::CUSTOMER_APPOINTMENT_BOOKING_DATE  => $customerAppointmentBookingDate,
                Leads::CUSTOMER_ONBOARDING_TAT            => $customerOnboardingTat,
            ]
        ];

        $this->assertEquals($expectedFileInput, $fileInput);

        $this->assertEquals([
            Leads::RZP_REF_NO                            => '40123',
            Leads::MERCHANT_NAME                         => 'Z-AXIS GROUP OF INDUSTRIES',
            Leads::MERCHANT_POC_NAME                     => 'aditya',
            Leads::MERCHANT_POC_DESIGNATION              => 'Proprietor',
            Leads::MERCHANT_POC_EMAIL                    => 'yaxisgroupofindustries@gmail.com',
            Leads::MERCHANT_POC_PHONE                    => '+919560569604',
            Leads::PINCODE                               => '110093',
            Leads::MERCHANT_CITY                         => 'eastdelhi',
            Leads::CONSTITUTION_TYPE                     => 'One Person Company',
            Leads::MERCHANT_ICV                          => 20000,
            Leads::APPLICATION_SUBMISSION_DATE           => '2023-05-01',
            Leads::TIMESTAMP                             => '06:54 PM',
            Leads::BUSINESS_MODEL                        => 'ECOMMERCE',
            Leads::ACCOUNT_TYPE                          => 'Business Plus',
            Leads::COMMENT                               => 'Bank\'s Inernal Comment',
            Leads::EXPECTED_MONTHLY_GMV                  => 500000,
            Leads::SALES_POC                             => 'Sales person',
            Leads::SALES_POC_PHONE_NUMBER                => '8989898988',
            Leads::GREEN_CHANNEL                         => 'Yes',
            Leads::FOS                                   => 'Yes',
            Leads::REVIVED_LEAD                          => 'No',
            Leads::OPS_POC_NAME                          => 'Ops person',
            Leads::OPS_POC_EMAIL                         => 'ops.person@razorpay.com',
            Leads::DOCKET_DELIVERY_DATE                  => '',
            Leads::STATUS                                => 'RazorpayProcessing',
            Leads::SUB_STATUS                            => 'None',
            Leads::ASSIGNEE                              => 'RZP',
            Leads::MID_OFFICE_POC                        => 'Bank POC person',
            Leads::RM_NAME                               => 'Pankaj Mishra',
            Leads::RM_MOBILE_NO                          => '9315383526',
            Leads::BRANCH_CODE                           => '213',
            Leads::BRANCH_NAME                           => 'Jaipur',
            Leads::BM                                    => 'Abhishek Chaturvedi',
            Leads::BM_MOBILE_NO                          => 9414641077,
            Leads::TL                                    => 'Avijit Shrivastava',
            Leads::CLUSTER                               => 'Rajasthan',
            Leads::REGION                                => 'North & East Region',
            Leads::LEAD_REFERRED_BY_RBL_STAFF            => 'Yes',
            Leads::OFFICE_AT_DIFFERENT_LOCATIONS         => 'Yes',
            Leads::CUSTOMER_APPOINTMENT_DATE             => '2023-03-10',
            Leads::APPOINTMENT_TAT                       => null,
            Leads::LEAD_IR_NO                            => 'IR 01234',
            Leads::DOCKET_DELIVERY_DATE                  => '',
            Leads::DOC_COLLECTION_TAT                    => null,
            Leads::IP_CHEQUE_VALUE                       => 20000,
            Leads::API_DOCS_RECEIVED_WITH_CA_DOCS        => 'Yes',
            Leads::API_DOC_DELAY_REASON                  => 'That\'s how we roll.',
            Leads::REVISED_DECLARATION                   => 'No',
            Leads::ACCOUNT_IR_NO                         => 'IR00022515189',
            Leads::ACCT_LOGIN_DATE                       => '',
            Leads::IR_LOGIN_TAT                          => null,
            Leads::PROMO_CODE                            => 'RZPAY',
            Leads::CASE_LOGIN                            => 'No',
            Leads::SR_NO                                 => 'SR_NUMBER',
            Leads::ACCOUNT_OPEN_DATE                     => '2023-03-15',
            Leads::ACCOUNT_IR_CLOSED_DATE                => '',
            Leads::AO_FTNR                               => '',
            Leads::AO_FTNR_REASONS                       => 'AO Negative List/Compliance/Legal/CIBIL',
            Leads::AO_TAT_EXCEPTION                      => 'No',
            Leads::AO_TAT_EXCEPTION_REASON               => 'compliance Issue',
            Leads::API_IR_NO                             => 'API_IR_1234',
            Leads::API_IR_LOGIN_DATE                     => '',
            Leads::LDAP_ID_MAIL_DATE                     => '2023-03-09',
            Leads::API_REQUEST_TAT                       => null,
            Leads::API_IR_CLOSED_DATE                    => '2023-03-09',
            Leads::API_REQUEST_PROCESSING_TAT            => null,
            Leads::API_FTNR                              => '',
            Leads::API_FTNR_REASONS                      => 'Reason 1, Reason 2',
            Leads::API_TAT_EXCEPTION                     => 'No',
            Leads::API_TAT_EXCEPTION_REASON              => 'This time for Africa',
            Leads::CORP_ID_MAIL_DATE                     => '',
            Leads::RZP_CA_ACTIVATED_DATE                 => '2023-03-09',
            Leads::UPI_CREDENTIALS_DATE                  => '2023-03-09',
            Leads::UPI_CREDENTIALS_NOT_DONE_REMARKS      => 'Something',
            Leads::DROP_OFF_DATE                         => '',
            Leads::API_SERVICE_FIRST_QUERY               => null,
            Leads::API_BEYOND_TAT                        => '',
            Leads::API_BEYOND_TAT_DEPENDENCY             => null,
            Leads::FIRST_CALLING_TIME                    => '5 to 6',
            Leads::SECOND_CALLING_TIME                   => null,
            Leads::WA_MESSAGE_SENT_DATE                  => '2023-03-09',
            Leads::WA_MESSAGE_RESPONSE_DATE              => '2023-03-09',
            Leads::API_DOCKET_RELATED_ISSUE              => null,
            Leads::AOF_SHARED_WITH_MO                    => 'No',
            Leads::AOF_SHARED_DISCREPANCY                => '',
            Leads::AOF_NOT_SHARED_REASON                 => 'Already Login',
            Leads::CA_BEYOND_TAT_DEPENDENCY              => '',
            Leads::CA_BEYOND_TAT                         => 'No',
            Leads::CA_SERVICE_FIRST_QUERY                => '1.on rrt high risk rating by compliance is not mentioned. APPLICANT FOUND IN NEGATIVE LIST ODG452595087230310202422178-ADIL',
            Leads::CUSTOMER_APPOINTMENT_BOOKING_DATE     => '2023-04-21',
            Leads::CUSTOMER_ONBOARDING_TAT               => 1.0,
            Leads::LEAD_IR_STATUS                        => null,
            Leads::DOC_COLLECTION_DATE                   => '2023-03-09',
        ], $rows[1]);
    }

    public function testBankLmsEndToEndForNotifyingMidOfficeManager()
    {
        Mail::fake();

        // Make merchant as Bank CA Onboarding Partner
        $response = $this->makeMerchantAsBankCAOnboardingPartner();

        // Add Feature to the Merchant
        $response = $this->addBankLmsFeatureToTheMerchant();

        // Invite new user to join RBL merchant
        $this->inviteNewUserToJoinRBLMerchant(self::DefaultPartnerMerchantId, 'random@rbl.com', BankingRole::BANK_MID_OFFICE_MANAGER);

        // Accept invitation
        $response = $this->acceptInvitation();

        // New Merchant Apply for Current Account
        $response = $this->MerchantApplyForCurrentAccount();

        $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);

        // Attach Submerchant to RBl Merchant
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED, Status::INITIATED,
            null, null,
            null, null,
            $response);

        Mail::assertNotQueued(ActivationMails\BankPartnerAssigned::class);
    }

    //public function testBankLmsEndToEndAfterDetachingSubMerchant()
    //{
    //    // Make merchant as Bank CA Onboarding Partner
    //    $response = $this->makeMerchantAsBankCAOnboardingPartner();
    //
    //    // Add Feature to the Merchant
    //    $response = $this->addBankLmsFeatureToTheMerchant();
    //
    //    // Invite new user to join RBL merchant
    //    //$this->inviteNewUserToJoinRBLMerchant();
    //
    //    // Accept invitation
    //    //$response = $this->acceptInvitation();
    //
    //    // New Merchant Apply for Current Account
    //    $response = $this->MerchantApplyForCurrentAccount();
    //
    //    // Attach Submerchant to RBl Merchant
    //    $this->assertUpdateBankingAccountStatusFromTo(
    //        Status::PICKED, Status::INITIATED,
    //        null, null,
    //        null, null,
    //        $response);
    //
    //    $user = $this->getDbEntity('user', ['email' => 'random@rbl.com']);
    //
    //    // Detach Submerchant to RBl Merchant
    //    $bankingAccount = $this->getDbLastEntity('banking_account');
    //
    //    $this->activateBankingAccount($bankingAccount);
    //
    //    //$this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $user->getId());
    //
    //    $partnerOwnerUser = $this->fixtures->user->createBankingUserForMerchant(self::DefaultPartnerMerchantId);
    //
    //    $this->ba->proxyAuth('rzp_test_' . self::DefaultPartnerMerchantId, $partnerOwnerUser->getId());
    //
    //    $this->ba->addXBankLMSOriginHeader();
    //
    //    $this->startTest();
    //}

    public function testMetroPublishForBankingAccountUpdate()
    {
        Queue::fake();

        $attribute = ['activation_status' => 'activated'];

        $this->fixtures->edit('merchant_detail', self::DefaultMerchantId, $attribute);

        $ba = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba->getId(),
            'business_category'         => 'partnership',
            'sales_team'                => 'self_serve',
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'booking_date_and_time'     => strtotime('17-Nov-2021 11:30:00'),
        ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/bacc_' . $ba->getId(),
                'method'  => 'PATCH',
            ],
        ];

        $this->ba->proxyAuth('rzp_test_' . self::DefaultMerchantId);

        $this->ba->addXOriginHeader();

        $expectedMetroMessage = [
            "data" => json_encode([
                "application" => [
                    "id" => $ba->getId(),
                ]
            ])
        ];

        $this->startTest($dataToReplace);

        Queue::assertPushed(BankingAccountNotifyMob::class);
    }

    public function testPreventMetroPublishForBankingAccountUpdate()
    {
        Queue::fake();

        $attribute = ['activation_status' => 'activated'];

        $this->fixtures->edit('merchant_detail', self::DefaultMerchantId, $attribute);

        $this->testData[__FUNCTION__] = $this->testData['testMetroPublishForBankingAccountUpdate'];

        $ba = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba->getId(),
            'business_category'         => 'partnership',
            'sales_team'                => 'self_serve',
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'booking_date_and_time'     => strtotime('17-Nov-2021 11:30:00'),
        ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/bacc_' . $ba->getId(),
                'method'  => 'PATCH',
            ],
        ];

        $metroMock = \Mockery::mock('RZP\Metro\MetroHandler');

        $metroMock->shouldNotReceive("publish");

        $this->app->instance('metro', $metroMock);

        $this->ba->mobAppAuthForProxyRoutes();

        $this->startTest($dataToReplace);

        Queue::assertNotPushed(BankingAccountNotifyMob::class);

        $this->fixtures->edit('banking_account',$ba->getId(), [
            'account_type' => 'nodal'
        ]);

        $this->ba->proxyAuth();

        $metroMock->shouldNotReceive("publish");

        $this->startTest($dataToReplace);

        Queue::assertNotPushed(BankingAccountNotifyMob::class);
    }

    /**
     * @param string $merchantId
     *
     * @return array
     * @throws BindingResolutionException
     */
    private function makeMerchantAsBankCAOnboardingPartner(string $merchantId = self::DefaultPartnerMerchantId): array
    {
        $merchant = $this->getDbEntityById('merchant', $merchantId);

        $app = ['id'=>'8ckeirnw84ifke'];

        $this->fixtures->merchant->createDummyPartnerApp(['partner_type' => Merchant\Constants::BANK_CA_ONBOARDING_PARTNER , 'merchant_id' => $merchantId]);

        $this->mockAuthServiceCreateApplication($merchant, $app);

        $request = [
            'url'     => '/banking_accounts/rbl/lms/merchant/admin/partner_type',
            'method'  => 'PATCH',
            'content' => [
                'merchant_id'  => $merchantId,
                'partner_type' => 'bank_ca_onboarding_partner',
            ],
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('bank_ca_onboarding_partner', $response['partner_type']);

        return $response;
    }

    /**
     * @param string $merchantId
     *
     * @return array
     */
    private function addBankLmsFeatureToTheMerchant(string $merchantId = self::DefaultPartnerMerchantId): array
    {
        $request = [
            'method'  => 'post',
            'url'     => '/features',
            'content' => [
                'names'       => [RZP\Models\Feature\Constants::RBL_BANK_LMS_DASHBOARD],
                'entity_type' => 'merchant',
                'entity_id'   => $merchantId
            ]
        ];

        $this->ba->adminAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(RZP\Models\Feature\Constants::RBL_BANK_LMS_DASHBOARD, $response[0]['name']);

        return $response;
    }

    /**
     * @param string $merchantId
     * @param string $userEmail
     * @param string $role
     *
     * @return void
     */
    private function inviteNewUserToJoinRBLMerchant(string $merchantId = self::DefaultPartnerMerchantId, string $userEmail = 'random@rbl.com', string $role = BankingRole::BANK_MID_OFFICE_POC)
    {
        $invitation = $this->fixtures->create('invitation', [
            'email'       => $userEmail,
            'merchant_id' => $merchantId,
            'role'        => $role,
            'product'     => 'banking',
        ]);

        return $invitation;
    }

    /**
     * @param string $userEmail
     *
     * @return mixed
     */
    private function inviteNewUserToJoinRBLMerchantAdmin(string $userEmail = 'random@rbl.com')
    {
        $request = [
            'url'     => '/banking_accounts/rbl/lms/merchant/admin/invitation',
            'method'  => 'POST',
            'content' => [
                'email'       => $userEmail,
                'role'           => BankingRole::BANK_MID_OFFICE_POC,
            ],
        ];

        $this->ba->adminAuth();

        return $this->makeRequestAndGetContent($request);
    }

    /**
     * @param string $userEmail
     *
     * @return array
     */
    private function acceptInvitation(string $userEmail = 'random@rbl.com'): array
    {
        $invite = $this->getDbLastEntity('invitation');

        $request = [
            'url'     => '/users/register',
            'method'  => 'POST',
            'content' => [
                'email'                 => $userEmail,
                'password'              => 'hello123',
                'password_confirmation' => 'hello123',
                'captcha_disable'       => 'DISABLE_THE_CAPTCHA_YOU_SHALL',
                'invitation'            => $invite->getToken()
            ],
        ];

        $this->ba->addXBankLMSOriginHeader();

        $this->ba->dashboardGuestAppAuth();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertTrue($response['login']);

        return  $response;
    }

    /**
     * @return mixed
     */
    private function MerchantApplyForCurrentAccount(string $submerchantId = '10000000000000', array $input = [])
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', $submerchantId, $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $data = [
            Entity::PINCODE     => '560030',
            Entity::CHANNEL     => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'self_serve',
            ]
        ];

        $data = array_merge($data, $input);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_dashboard',
            'content' => $data
        ];

        Mail::fake();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotEmpty($response['id']);

        return $response;
    }

    private function AdminApplyForCurrentAccount(string $submerchantId = '10000000000000', array $input = [])
    {

        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $data = [
            Entity::PINCODE     => '560030',
            Entity::CHANNEL     => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'sme',
                ActivationDetail\Entity::COMMENT           => 'first comment on lead',
                ActivationDetail\Entity::SALES_POC_ID      => 'admin_'. Org::SUPER_ADMIN,
                ActivationDetail\Entity::SALES_POC_PHONE_NUMBER     => '1234554321',
                ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
                ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'Financial Consultant',
                ActivationDetail\Entity::MERCHANT_POC_EMAIL         => 'sample@sample.com',
                ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER  => '9876556789',
                ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'x, y, z',
                ActivationDetail\Entity::INITIAL_CHEQUE_VALUE => 100,
                ActivationDetail\Entity::ACCOUNT_TYPE => 'insignia',
                ActivationDetail\Entity::MERCHANT_CITY => 'Bangalore',
                ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => true,
                ActivationDetail\Entity::MERCHANT_REGION => 'South',
                ActivationDetail\Entity::EXPECTED_MONTHLY_GMV => 10000,
                ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE => 0

            ]
        ];

        $data = array_merge($data, $input);

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_admin',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => $data
        ];

        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotEmpty($response['id']);

        return $response;
    }

    protected function addFasterDocCollectionAttribute(string $merchantId = '10000000000000', string $value = 'active')
    {
        return $this->createMerchantAttribute($merchantId, 'banking', 'x_merchant_current_accounts', 'ca_onboarding_faster_doc_collection', $value);
    }

    public function testSkipMidOfficeCallFromLMS()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->addFasterDocCollectionAttribute();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $data = [
            Entity::PINCODE     => '560030',
            Entity::CHANNEL     => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'sme',
                ActivationDetail\Entity::COMMENT           => 'first comment on lead',
                ActivationDetail\Entity::SALES_POC_ID      => 'admin_'. Org::SUPER_ADMIN,
                ActivationDetail\Entity::SALES_POC_PHONE_NUMBER     => '1234554321',
                ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
                ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'Financial Consultant',
                ActivationDetail\Entity::MERCHANT_POC_EMAIL         => 'sample@sample.com',
                ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER  => '9876556789',
                ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'x, y, z',
                ActivationDetail\Entity::INITIAL_CHEQUE_VALUE => 100,
                ActivationDetail\Entity::ACCOUNT_TYPE => 'insignia',
                ActivationDetail\Entity::MERCHANT_CITY => 'Bangalore',
                ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => true,
                ActivationDetail\Entity::MERCHANT_REGION => 'South',
                ActivationDetail\Entity::EXPECTED_MONTHLY_GMV => 10000,
                ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE => 0,
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::PROOF_OF_ENTITY => [
                        'status' => 'verified',
                        'source' => 'gstin'
                    ],
                    ActivationDetail\Entity::PROOF_OF_ADDRESS => [
                        'status' => 'verified',
                        'source' => 'llpin'
                    ],
                    ActivationDetail\Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS => [
                        ActivationDetail\Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS => 1,
                        ActivationDetail\Entity::SEAL_AVAILABLE => 0,
                        ActivationDetail\Entity::SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS => 1,
                        ActivationDetail\Entity::SIGNBOARD_AVAILABLE => 0,
                    ],
                ],
            ]
        ];

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_admin',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => $data
        ];

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotEmpty($bankingAccount['id']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $additionalDetails = json_decode($bankingAccountActivationDetail->getAdditionalDetails(), true);

        $this->assertEquals(ActivationDetail\Entity::SALES, $additionalDetails[ActivationDetail\Entity::APPOINTMENT_SOURCE]);

        $this->assertEquals(1, $additionalDetails[ActivationDetail\Entity::SKIP_MID_OFFICE_CALL]);

    }

    public function testSkipMidOfficeCallFromLMSSalesPitchCompleted()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->addFasterDocCollectionAttribute();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->mockPincodeSearchForCity('Aligarh', 'Uttar Pradesh');

        $data = [
            Entity::PINCODE     => '202122',
            Entity::CHANNEL     => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'sme',
                ActivationDetail\Entity::COMMENT           => 'first comment on lead',
                ActivationDetail\Entity::SALES_POC_ID      => 'admin_'. Org::SUPER_ADMIN,
                ActivationDetail\Entity::SALES_POC_PHONE_NUMBER     => '1234554321',
                ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
                ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'Financial Consultant',
                ActivationDetail\Entity::MERCHANT_POC_EMAIL         => 'sample@sample.com',
                ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER  => '9876556789',
                ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'x, y, z',
                ActivationDetail\Entity::INITIAL_CHEQUE_VALUE => 100,
                ActivationDetail\Entity::ACCOUNT_TYPE => 'insignia',
                ActivationDetail\Entity::MERCHANT_CITY => 'Bangalore',
                ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => true,
                ActivationDetail\Entity::MERCHANT_REGION => 'South',
                ActivationDetail\Entity::EXPECTED_MONTHLY_GMV => 10000,
                ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE => 0,
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::PROOF_OF_ENTITY => [
                        'status' => 'verified',
                        'source' => 'gstin'
                    ],
                    ActivationDetail\Entity::PROOF_OF_ADDRESS => [
                        'status' => 'verified',
                        'source' => 'llpin'
                    ],
                ],
            ]
        ];

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_admin',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => $data
        ];

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotEmpty($bankingAccount['id']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $additionalDetails = json_decode($bankingAccountActivationDetail->getAdditionalDetails(), true);

        $this->assertEquals(ActivationDetail\Entity::MID_OFFICE, $additionalDetails[ActivationDetail\Entity::APPOINTMENT_SOURCE]);

        $this->assertEquals(0, $additionalDetails[ActivationDetail\Entity::SKIP_MID_OFFICE_CALL]);

        $request  = [
            'method'  => 'PATCH',
            'url'     => '/banking_accounts/activation/' . $bankingAccount->getPublicId() . '/details',
            'content' => [
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::SALES_PITCH_COMPLETED => 1,
                ],
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $request  = [
            'method'  => 'PATCH',
            'url'     => '/banking_accounts/activation/' . $bankingAccount->getPublicId() . '/details',
            'content' => [
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS => [
                        ActivationDetail\Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS => 1,
                        ActivationDetail\Entity::SEAL_AVAILABLE => 0,
                        ActivationDetail\Entity::SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS => 1,
                        ActivationDetail\Entity::SIGNBOARD_AVAILABLE => 0,
                    ],
                ],
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotEmpty($bankingAccount['id']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $additionalDetails = json_decode($bankingAccountActivationDetail->getAdditionalDetails(), true);

        $this->assertEquals(ActivationDetail\Entity::MID_OFFICE, $additionalDetails[ActivationDetail\Entity::APPOINTMENT_SOURCE]);

        $this->assertEquals(0, $additionalDetails[ActivationDetail\Entity::SKIP_MID_OFFICE_CALL]);
    }

    public function testSkipMidOfficeCallDashboardDecisionChange()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        $this->addFasterDocCollectionAttribute();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->mockPincodeSearchForCity('Aligarh', 'Uttar Pradesh');

        $data = [
            Entity::PINCODE     => '202122',
            Entity::CHANNEL     => 'rbl',
            'activation_detail' => [
                ActivationDetail\Entity::BUSINESS_CATEGORY => 'partnership',
                ActivationDetail\Entity::SALES_TEAM        => 'sme',
                ActivationDetail\Entity::COMMENT           => 'first comment on lead',
                ActivationDetail\Entity::SALES_POC_ID      => 'admin_'. Org::SUPER_ADMIN,
                ActivationDetail\Entity::SALES_POC_PHONE_NUMBER     => '1234554321',
                ActivationDetail\Entity::MERCHANT_POC_NAME => 'Sample Name',
                ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'Financial Consultant',
                ActivationDetail\Entity::MERCHANT_POC_EMAIL         => 'sample@sample.com',
                ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER  => '9876556789',
                ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'x, y, z',
                ActivationDetail\Entity::INITIAL_CHEQUE_VALUE => 100,
                ActivationDetail\Entity::ACCOUNT_TYPE => 'insignia',
                ActivationDetail\Entity::MERCHANT_CITY => 'Bangalore',
                ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => true,
                ActivationDetail\Entity::MERCHANT_REGION => 'South',
                ActivationDetail\Entity::EXPECTED_MONTHLY_GMV => 10000,
                ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE => 0,
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::PROOF_OF_ENTITY => [
                        'status' => 'verified',
                        'source' => 'gstin'
                    ],
                    ActivationDetail\Entity::PROOF_OF_ADDRESS => [
                        'status' => 'verified',
                        'source' => 'llpin'
                    ],
                ],
            ]
        ];

        $request = [
            'method'  => 'post',
            'url'     => '/banking_accounts_admin',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => $data
        ];

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotEmpty($bankingAccount['id']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $additionalDetails = json_decode($bankingAccountActivationDetail->getAdditionalDetails(), true);

        $this->assertEquals(ActivationDetail\Entity::MID_OFFICE, $additionalDetails[ActivationDetail\Entity::APPOINTMENT_SOURCE]);

        $this->assertEquals(0, $additionalDetails[ActivationDetail\Entity::SKIP_MID_OFFICE_CALL]);

        $request  = [
            'method'  => 'PATCH',
            'url'     => '/banking_accounts/activation/' . $bankingAccount->getPublicId() . '/details',
            'content' => [
                ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                    ActivationDetail\Entity::RBL_NEW_ONBOARDING_FLOW_DECLARATIONS => [
                        ActivationDetail\Entity::AVAILABLE_AT_PREFERRED_ADDRESS_TO_COLLECT_DOCS => 1,
                        ActivationDetail\Entity::SEAL_AVAILABLE => 0,
                        ActivationDetail\Entity::SIGNATORIES_AVAILABLE_AT_PREFERRED_ADDRESS => 1,
                        ActivationDetail\Entity::SIGNBOARD_AVAILABLE => 0,
                    ],
                ],
            ]
        ];

        $this->makeRequestAndGetContent($request);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertNotEmpty($bankingAccount['id']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $additionalDetails = json_decode($bankingAccountActivationDetail->getAdditionalDetails(), true);

        $this->assertEquals(ActivationDetail\Entity::SALES, $additionalDetails[ActivationDetail\Entity::APPOINTMENT_SOURCE]);

        $this->assertEquals(1, $additionalDetails[ActivationDetail\Entity::SKIP_MID_OFFICE_CALL]);
    }

    /**
     * @param $merchant
     * @param $user
     * @param $bankingAccountId
     *
     * @return array
     */
    private function assignBankPocUserToApplication($merchant, $user, $bankingAccountId): array
    {
        $this->ba->proxyAuth('rzp_test_' . $merchant->getId(), $user->getId());

        $this->ba->addXBankLMSOriginHeader();

        $user->setName('RBL MO POC User');

        $user->saveOrFail();

        $data = [
            ActivationDetail\Entity::BANK_POC_USER_ID => $user->getId()
        ];

        $request = [
            'method'  => 'patch',
            'url'     => '/banking_accounts/rbl/lms/activation/' . $bankingAccountId . '/bank_poc',
            'content' => $data
        ];

        Mail::fake();

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($user->getId(), $response["banking_account_activation_details"]["bank_poc_user_id"]);
        // $this->assertEquals($user->getName(), $response["banking_account_activation_details"]["bank_poc_name"]);

        Mail::assertQueued(ActivationMails\BankPartnerPocAssigned::class, function ($mail) use ($user)
        {
            $mail->build();
            return $mail->hasTo($user->getEmail());
        });

        return $response;
    }

    public function testCreateBankingAccountWithActivationDetailFromMOB()
    {
        Queue::fake();

        $this->ba->mobAppAuthForInternalRoutes();

        $admin = $this->fixtures->create('admin', ['org_id' => Org::RZP_ORG, 'email' => 'abc@razorpay.com']);

        $dataToReplace = [
            'request'  => [
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail(),
                ]
            ],
        ];

        $this->startTest($dataToReplace);

        Queue::assertNotPushed(BankingAccountNotifyMob::class);
    }

    public function testUpdateBankingAccountWithActivationDetailFromMOB()
    {
        Queue::fake();

        $bankingAccount = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $admin = $this->fixtures->create('admin', ['org_id' => Org::RZP_ORG, 'email' => 'abc@razorpay.com']);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_lms_mob/' . $bankingAccount->getPublicId(),
                'method'  => 'PATCH',
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail()
                ]
            ],
        ];

        $this->ba->mobAppAuthForInternalRoutes();

        $this->startTest($dataToReplace);

        Queue::assertNotPushed(BankingAccountNotifyMob::class);
    }

    public function testUpdateBankingAccountActivationDetailsViaMOB()
    {
        Queue::fake();

        $bankingAccount = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $admin = $this->fixtures->create('admin', ['org_id' => Org::RZP_ORG, 'email' => 'abc@razorpay.com']);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_internal/activation/' . $bankingAccount->getPublicId() . '/details',
                'method'  => 'PATCH',
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail(),
                ]
            ],
        ];

        $this->ba->mobAppAuthForInternalRoutes();

        $this->startTest($dataToReplace);

        Queue::assertNotPushed(BankingAccountNotifyMob::class);
    }

    public function verifySkipDwtComputeAndSave(array $additonalDetailsInput, string $skipDwtEligble, int $skipDwtValue)
    {
        $this->testData[__FUNCTION__] = $this->testData['testSkipDwtComputeAndSave'];

        $bankingAccount = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $bankingAccount->getId(),
            'additional_details'        => json_encode($additonalDetailsInput)
        ]);

        $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'skip_dwt_eligible', $skipDwtEligble);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_internal/activation/' . $bankingAccount->getPublicId() . '/details',
            ],
            'response' => [
                'content' => [
                ],
            ],
        ];

        $this->ba->mobAppAuthForInternalRoutes();

        $response = $this->startTest($dataToReplace);

        $additionalDetailsResp = json_decode($response['additional_details'], true);

        $this->assertEquals($skipDwtValue,$additionalDetailsResp['skip_dwt']);

        return $this->startTest($dataToReplace);

    }

    public function testSkipDwtComputeAndSaveSkipDwtTrue()
    {
        $additionalDetailsInput = [
            'gstin_prefilled_address' => 1,
            'rbl_new_onboarding_flow_declarations' => [
                'available_at_preferred_address_to_collect_docs' => 1,
                'seal_available' => 0,
                'signatories_available_at_preferred_address' => 1,
                'signboard_available' => 0
            ]
        ];

        $response = $this->verifySkipDwtComputeAndSave($additionalDetailsInput,'enabled',1);

        $bankingAccount = $this->getDbEntity('banking_account', [
            'id' => $response['banking_account_id']
        ])->toArray();

        $this->assertEquals(Status::INITIATE_DOCKET,$bankingAccount['sub_status']);
    }

    public function testSkipDwtComputeAndSaveExpDisabled()
    {
        $additionalDetailsInput = [
            'gstin_prefilled_address' => 1,
            'rbl_new_onboarding_flow_declarations' => [
                'available_at_preferred_address_to_collect_docs' => 1,
                'seal_available' => 1,
                'signatories_available_at_preferred_address' => 1,
                'signboard_available' => 1
            ]
        ];

        $this->verifySkipDwtComputeAndSave($additionalDetailsInput,'disabled',0);
    }

    public function testSkipDwtComputeAndSaveExpEnabledAndFalseValueInDeclaration()
    {
        $additionalDetailsInput = [
            'gstin_prefilled_address' => 0,
            'rbl_new_onboarding_flow_declarations' => [
                'available_at_preferred_address_to_collect_docs' => 1,
                'seal_available' => 1,
                'signatories_available_at_preferred_address' => 0,
                'signboard_available' => 1
            ]
        ];

        $response = $this->verifySkipDwtComputeAndSave($additionalDetailsInput,'enabled',0);

        $bankingAccount = $this->getDbEntity('banking_account', [
            'id' => $response['banking_account_id']
        ])->toArray();

        $this->assertEquals(Status::DWT_REQUIRED,$bankingAccount['sub_status']);
    }

    public function testUpdateBankingAccountActivationDetailsShouldMoveSubstatusToInitiateDocketIfSkipDwtExpAndDwtComplete()
    {
        $bankingAccount = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'picked',
            'sub_status'            => 'dwt_required',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $admin = $this->fixtures->create('admin', ['org_id' => Org::RZP_ORG, 'email' => 'abc@razorpay.com']);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_internal/activation/' . $bankingAccount->getPublicId() . '/details',
                'method'  => 'PATCH',
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail(),
                ]
            ],
        ];

        $this->ba->mobAppAuthForInternalRoutes();

        $response = $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbEntity('banking_account', [
            'id' => $response['banking_account_id']
        ])->toArray();

        $this->assertEquals(Status::INITIATE_DOCKET,$bankingAccount['sub_status']);
    }

    public function testPreventMetroCallbackForGatewayBalanceFetch()
    {
        $attribute = ['activation_status' => 'activated'];

        $this->fixtures->edit('merchant_detail', self::DefaultMerchantId, $attribute);

        $balance = $this->fixtures->create('balance',
        [
            'merchant_id'       => self::DefaultMerchantId,
            'type'              => 'banking',
            'account_type'      => 'direct',
            'account_number'    => '2224440041626905',
            'balance'           => 200,
        ]);

        $ba = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'activated',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
            'balance_id'            => $balance->getId()
        ]);

        $metroMock = \Mockery::mock('RZP\Metro\MetroHandler');

        $metroMock->shouldNotReceive("publish");

        $this->mockMozartResponseForFetchingBalanceFromRblGateway(50000);

        $processor = new Rbl\Processor([
            Entity::MERCHANT_ID => $ba->getMerchantId(),
            Entity::CHANNEL     => 'rbl'
        ]);

        $processor->fetchGatewayBalance();
    }

    protected function mockMozartResponseForFetchingBalanceFromRblGateway(int $amount): void
    {
        $mozartServiceMock = $this->getMockBuilder(Mozart::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['sendMozartRequest'])
            ->getMock();

        $mozartServiceMock->method('sendMozartRequest')
            ->willReturn([
                'data' => [
                    'success' => true,
                    Rbl\Fields::GET_ACCOUNT_BALANCE => [
                        Rbl\Fields::BODY => [
                            Rbl\Fields::BAL_AMOUNT => [
                                Rbl\Fields::AMOUNT_VALUE => $amount
                            ]
                        ]
                    ]
                ]
            ]);

        $this->app->instance('mozart', $mozartServiceMock);
    }

    public function testCreateBankingAccountWithActivationDetailWithBusinessTypeAsTrust()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $merchantId = $bankingAccount->merchant->getId();

        $this->createMerchantDetail([
                'merchant_id' => $merchantId,
                'business_name' => 'CA Business']
        );

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        $this->assertEquals('trust', $activationDetailEntity['business_category']);

        Mail::assertQueued(XProActivation::class);

        return $bankingAccount;
    }

    public function testCreateBankingAccountWithActivationDetailWithBusinessTypeAsSociety()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();

        // Turn on the 'allow_all_merchants' feature for admin
        DB::table('admins')->update(['allow_all_merchants' => 1]);

        Mail::fake();

        $this->ba->adminAuth();

        $this->startTest();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $merchantId = $bankingAccount->merchant->getId();

        $this->createMerchantDetail([
                'merchant_id' => $merchantId,
                'business_name' => 'CA Business']
        );

        $this->assertEquals(AccountType::CURRENT, $bankingAccount->getAccountType());

        $this->assertEquals(null, $bankingAccount['last_statement_attempt_at']);

        $activationDetailEntity = $this->getDbEntity('banking_account_activation_detail', [
            'banking_account_id' => $bankingAccount->getId()
        ]);

        $this->assertNotNull($activationDetailEntity);

        $this->assertEquals('society', $activationDetailEntity['business_category']);

        Mail::assertQueued(XProActivation::class);

        return $bankingAccount;
    }

    public function testPreventFreshDeskTicketCreationForNonSalesLedFromMOB()
    {
        $this->ba->mobAppAuthForProxyRoutes();

        Mail::fake();


        $this->assertFreshDeskTicketCreatedEventFired(false,[]);

        $this->startTest();

        Mail::assertNotQueued(XProActivation::class);
    }

    public function testFilterOpsFollowUpDate()
    {
        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name' => 'Aryan',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'business_category' => 'limited_liability_partnership',
                'merchant_documents_address' => 'x, y, z',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'assignee_team' => 'sales',
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'additional_details' => [
                    'ops_follow_up_date' => '1667347200',
                ],
            ]
        ];

        $bankingAccount = $this->createBankingAccountFromDashboard($bankingAccount);

        $this->ba->adminAuth();

        $dataToReplace = [
            'request' => [
                'url'     => '/admin/banking_account?count=20&skip=0&account_type=current&from_ops_follow_up_date=1667346200&to_ops_follow_up_date=1667348200',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'banking_account_activation_details' => [
                                'additional_details' => [
                                    'ops_follow_up_date' => '1667347200'
    ]
    ]
                        ]
                    ],
                ],
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testFilterOpsFollowUpDateNegativeCase()
    {
        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name' => 'Aryan',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'business_category' => 'limited_liability_partnership',
                'merchant_documents_address' => 'x, y, z',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'assignee_team' => 'sales',
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'additional_details' => [
                    'ops_follow_up_date' => '1667347200',
                ],
            ]
        ];

        $bankingAccount = $this->createBankingAccountFromDashboard($bankingAccount);

        $this->ba->adminAuth();

        $dataToReplace = [
            'request' => [
                'url'     => '/admin/banking_account?count=20&skip=0&account_type=current&from_ops_follow_up_date=1667346200&to_ops_follow_up_date=1667347100',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count' => 0,
                    'items' => [
                    ],
                ],
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testFilterOpsFollowUpDateDoNotReturnRowsWithEmptyOpsFollowUpDate()
    {
        $bankingAccount = [
            'activation_detail' => [
                'merchant_poc_name' => 'Aryan',
                'merchant_poc_designation' => 'Financial Consultant',
                'merchant_poc_email' => 'sample@sample.com',
                'merchant_poc_phone_number' => '9876556789',
                'business_category' => 'limited_liability_partnership',
                'merchant_documents_address' => 'x, y, z',
                'sales_team' => 'sme',
                'sales_poc_id' => 'admin_'. Org::SUPER_ADMIN,
                'assignee_team' => 'sales',
                'initial_cheque_value' => 100,
                'account_type' => 'insignia',
                'merchant_city' => 'Bangalore',
                'is_documents_walkthrough_complete' => true,
                'merchant_region' => 'South',
                'expected_monthly_gmv' => 10000,
                'average_monthly_balance' => 0,
                'additional_details' => [
                    'ops_follow_up_date' => '',
                ],
            ]
        ];

        $bankingAccount = $this->createBankingAccountFromDashboard($bankingAccount);

        $this->ba->adminAuth();

        $dataToReplace = [
            'request' => [
                'url'     => '/admin/banking_account?count=20&skip=0&account_type=current&to_ops_follow_up_date=1667347100',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count' => 0,
                    'items' => [
                    ],
                ],
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function testFilterSkipDwt()
    {

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '2224440041626905',
            'account_type'          => 'current',
            'merchant_id'           => self::DefaultMerchantId,
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
            'additional_details'        => json_encode([
                'skip_dwt' => 1
            ])
        ]);

        $ba2 = $this->fixtures->create('banking_account', [
            'id'                    => '01234567890125',
            'account_number'        => '2224440041626906',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000001',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '560038',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba2->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
        ]);

        $this->ba->adminAuth();

        $dataToReplace = [
            'request' => [
                'url'     => '/admin/banking_account?skip_dwt=1',
                'method'  => 'GET',
                'content' => [
                    'expand' => ['merchant','merchant.merchantDetail'],
                ],
            ],
            'response' => [
                'content' => [
                    'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                    ],
                ],
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function verifyFreshDeskTicketCreationBehaviourForSalesLed(string $baId, string $baActivationDetailId, array $activationDetail, array $reqContent, Admin\Admin\Entity $admin)
    {
        $this->fixtures->edit('banking_account', $baId, ['status' => 'created']);

        $this->fixtures->edit('banking_account_activation_detail', $baActivationDetailId, $activationDetail);

        $this->ba->mobAppAuthForInternalRoutes();

        $this->testData[__FUNCTION__] = $this->testData['testFreshDeskTicketCreationBehaviourForSalesLed'];

        Mail::fake();

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts_internal/activation/bacc_'. $baId .'/details',
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail(),
                ],
                'content' => $reqContent
            ]
        ];

        $this->assertFreshDeskTicketCreatedEventFired(true,[
            'banking_account_id' => $baId,
            'status'             => 'picked',
        ]);

        $this->startTest($dataToReplace);

        Mail::assertQueued(XProActivation::class, 1);

        // calling update once more with all details
        $content = array_merge($activationDetail, $reqContent);

        $dataToReplace = [
            'request' => [
                'url'     => '/banking_accounts_internal/activation/bacc_'. $baId .'/details',
                'server'  => [
                    'HTTP_X-Admin-Email' => $admin->getEmail(),
                ],
                'content' => $content
            ]
        ];

        Mail::fake();

        $this->startTest($dataToReplace);

        // Mail should not be queued again
        Mail::assertQueued(XProActivation::class, 0);
    }

    public function testFreshDeskTicketCreationBehaviourForSalesLed()
    {
        $attribute = ['activation_status' => 'activated'];

        $this->fixtures->edit('merchant_detail', self::DefaultMerchantId, $attribute);

        $this->createMerchantAttribute(self::DefaultMerchantId, 'banking', 'x_merchant_current_accounts', 'ca_onboarding_flow', 'SALES_LED');

        $ba = $this->fixtures->create('banking_account', [
            'account_number' => '2224440041626905',
            'account_type' => 'current',
            'merchant_id' => self::DefaultMerchantId,
            'channel' => 'rbl',
            'status' => 'created',
            'pincode' => '560038',
            'bank_reference_number' => '',
            'account_ifsc' => 'RATN0000156',
        ]);

        $baActivationDetail = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id' => $ba->getId(),
        ]);

        $admin = $this->fixtures->create('admin', ['org_id' => Org::RZP_ORG, 'email' => 'abc@razorpay.com']);

        $activationDetail = [
            ActivationDetail\Entity::MERCHANT_POC_NAME => 'name',
            ActivationDetail\Entity::MERCHANT_POC_DESIGNATION => 'designation',
            ActivationDetail\Entity::MERCHANT_POC_EMAIL => 'email@gmail.com',
            ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER => '1234567890',
            ActivationDetail\Entity::MERCHANT_DOCUMENTS_ADDRESS => 'doc address',
            ActivationDetail\Entity::MERCHANT_CITY => 'Bengaluru',
            ActivationDetail\Entity::MERCHANT_REGION => 'East',
            ActivationDetail\Entity::COMMENT => 'Sample comment',
            ActivationDetail\Entity::SALES_TEAM => 'sme',
            ActivationDetail\Entity::BUSINESS_NAME => 'businessname',
            ActivationDetail\Entity::BUSINESS_CATEGORY => 'sole_proprietorship',
            ActivationDetail\Entity::ACCOUNT_TYPE => 'insignia',
            ActivationDetail\Entity::SALES_POC_PHONE_NUMBER => '1234567890',
            ActivationDetail\Entity::EXPECTED_MONTHLY_GMV => '123456',
            ActivationDetail\Entity::AVERAGE_MONTHLY_BALANCE => '123456',
            ActivationDetail\Entity::INITIAL_CHEQUE_VALUE => '123456',
            ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => true,
            ActivationDetail\Entity::ADDITIONAL_DETAILS => [
                ActivationDetail\Entity::GREEN_CHANNEL => false
            ],
        ];

        // test all activation details except sales_poc_id
        $keys = array_keys($activationDetail);
        for ($index = 0; $index < count($keys); $index++)
        {
            $unsetAttr = $keys[$index];

            $unsetAttrVal = $activationDetail[$unsetAttr];

            unset($activationDetail[$unsetAttr]);

            $reqContent = [
                ActivationDetail\Entity::SALES_POC_ID => 'admin_' . ORG::SUPER_ADMIN,
                $unsetAttr => $unsetAttrVal,
            ];
            $this->verifyFreshDeskTicketCreationBehaviourForSalesLed($ba->getId(), $baActivationDetail->getId(), $activationDetail, $reqContent, $admin);

            $activationDetail[$unsetAttr] = $unsetAttrVal;
        }

        // test sales_poc already assigned
        $core = new BankingAccountCore();

        $core->addSalesPOCToBankingAccount($ba, 'admin_' . ORG::SUPER_ADMIN);

        $this->verifyFreshDeskTicketCreationBehaviourForSalesLed($ba->getId(), $baActivationDetail->getId(), [], $activationDetail, $admin);
    }

    private function assertFreshDeskTicketCreatedEventFired(bool $shouldFireEvent, array $payload): EventCode|\Mockery\MockInterface|\Mockery\LegacyMockInterface
    {
        $diagMock = $this->createAndReturnDiagMock();

        if ($shouldFireEvent)
        {
            $diagMock->shouldReceive('trackOnboardingEvent')
                ->once()
                ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($payload) {
                    $this->assertEquals($payload, $actualData);
                    $this->assertEquals(EventCode::X_CA_ONBOARDING_FRESHDESK_TICKET_CREATE, $eventData);
                    return true;
                })
                ->andReturnNull();
        } else
        {
            $diagMock->shouldNotReceive('trackOnboardingEvent');
        }

        return $diagMock;
    }

    public function testGetOpsMxPocsList()
    {
        $this->ba->adminAuth();

        $admin = $this->fixtures->create('admin', [
            Admin\Admin\Entity::ID => 'randomMxPocsId',
            Admin\Admin\Entity::ORG_ID => Org::RZP_ORG,
            Admin\Admin\Entity::EMAIL => 'nuhaid.pasha@cnx.razorpay.com',
        ]);

        $this->startTest();
    }

    public function testAssignOpsMxPocToBankingAccount()
    {
        $this->ba->adminAuth();

        $admin = $this->fixtures->create('admin', [
            Admin\Admin\Entity::ID => 'randomMxPocsId',
            Admin\Admin\Entity::ORG_ID => Org::RZP_ORG,
            Admin\Admin\Entity::EMAIL => 'randomcnxemail@cnx.razorpay.com',
        ]);

        $adminId = 'admin_' . $admin[Admin\Admin\Entity::ID];

        $bankingAccount = $this->testCreateBankingAccountWithActivationDetail();

        $bankingAccountData = [
            'activation_detail' => [
                BankingAccount\Entity::OPS_MX_POC_ID => $adminId,
            ],
            BankingAccount\Entity::STATUS     => Status::ARCHIVED,
            BankingAccount\Entity::SUB_STATUS => Status::IN_PROCESS,
        ];

        $this->updateBankingAccount($bankingAccount, $bankingAccountData);

        $request = [
            'request' => [
                'url' => '/admin/banking_account/' . 'bacc_' . $bankingAccount->getId(),
            ],
            'response' => [
                'content' => [
                    'id' => 'bacc_' . $bankingAccount->getId(),
                    'banking_account_activation_details' => [
                        'assignee_team' => BankingAccount\Entity::OPS_MX_POC
                    ]
                ],
            ]
        ];

        $this->startTest($request);
    }

    public function testListBankingAccounts()
    {
        [$balance, $bankingAccount, $masterDirectBankingAccount] = $this->fixtureSetupForSubMerchant();

        $testData = $this->testData['testFetchBankingAccountForPayoutService'];

        $response = $this->makeRequestAndGetContent($testData['request']);

        $expectedResponse = [
            'count' => 1,
            'items' => [
                [
                    'id'      => $bankingAccount->getPublicId(),
                    'balance' => [
                        'id' => $balance->getId(),
                    ],
                ],
            ],
        ];

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        // master_banking_account key should NOT be present in the response
        $this->assertArrayNotHasKey('master_banking_account', $expectedResponse['items'][0]);

        $this->fixtures->merchant->addFeatures([Feature\Constants::ASSUME_SUB_ACCOUNT]);

        // master_banking_account key SHOULD be present in the response
        $expectedResponse['items'][0]['master_banking_account'] = [
            'id'             => $masterDirectBankingAccount->getId(),
            'account_number' => mask_except_last4($masterDirectBankingAccount->getAccountNumber()),
            'status'         => 'activated',
            'is_upi_allowed' => false,
            'name'           => 'Master Merchant'
        ];

        $response = $this->makeRequestAndGetContent($testData['request']);

        $this->assertArrayHasKey('master_banking_account', $expectedResponse['items'][0]);

        $this->assertArraySelectiveEquals($expectedResponse, $response);

        $this->fixtures->create('feature', [
            'name'        => 'rbl_ca_upi',
            'entity_id'   => '20000000000000',
            'entity_type' => 'merchant',
        ]);

        /* Since rbl_ca_upi feature is enabled on master merchant, is_upi_allowed should be true */
        $expectedResponse['items'][0]['master_banking_account']['is_upi_allowed'] = true;

        $response = $this->makeRequestAndGetContent($testData['request']);

        $this->assertArraySelectiveEquals($expectedResponse, $response);
    }

    public function fixtureSetupForSubMerchant()
    {
        $balance = $this->fixtures->create('balance', [
            'id'             => 'subBalance0000',
            'type'           => 'banking',
            'account_type'   => 'shared',
            'merchant_id'    => '10000000000000',
            'account_number' => '34341234567890',
        ]);

        $bankingAccount = $this->fixtures->create('banking_account', [
            'id'           => 'subBacc0000000',
            'balance_id'   => $balance->getId(),
            'merchant_id'  => '10000000000000',
            'account_type' => 'nodal',
            'status'       => 'created'
        ]);

        $masterMerchant = $this->fixtures->create('merchant', [
            'id' => '20000000000000',
            'display_name' => 'Master Merchant',
        ]);

        $masterDirectBalance = $this->fixtures->create('balance', [
            'merchant_id'    => $masterMerchant->getId(),
            'type'           => 'banking',
            'account_type'   => 'direct',
            'channel'        => 'rbl',
            'account_number' => '300400500600'
        ]);

        $masterDirectBankingAccount = $this->fixtures->create('banking_account', [
            'merchant_id'    => $masterMerchant->getId(),
            'channel'        => 'rbl',
            'balance_id'     => $masterDirectBalance->getId(),
            'account_number' => '300400500600',
            'status'         => 'activated',
            'account_type'   => 'current'
        ]);

        $this->fixtures->create('sub_virtual_account', [
            'master_merchant_id'    => '20000000000000',
            'master_balance_id'     => $masterDirectBalance->getId(),
            'name'                  => 'Sub VA 1',
            'master_account_number' => $masterDirectBalance->getAccountNumber(),
            'sub_account_type'      => 'sub_direct_account',
            'sub_account_number'    => $balance->getAccountNumber(),
        ]);

        return [$balance, $bankingAccount, $masterDirectBankingAccount];
    }

    public function testExcludeTerminatedAccountsBankingAccountList()
    {
        $this->ba->proxyAuth();

        $this->createBankingAccountFromDashboard();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->updateBankingAccount($bankingAccount, [
            'status' => Status::TERMINATED,
        ]);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testExcludeTerminatedAccountsQueryParamAdminFetch()
    {
        $this->ba->proxyAuth();

        $this->createBankingAccountFromDashboard();

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testUpdateTerminatedBankingAccount()
    {
        $this->ba->proxyAuth();

        $this->createBankingAccountFromDashboard();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->updateBankingAccount($bankingAccount, [
            'status' => Status::TERMINATED,
        ]);

        $request = [
            'url'       => '/banking_accounts/' . $bankingAccount->getPublicId(),
            'method'    => 'PATCH',
            'content'   => [
                'status' => 'picked',
            ],
        ];

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('BAD_REQUEST_BANKING_ACCOUNT_UPDATE_NOT_PERMITTED', $response['error']['internal_error_code']);
    }

    public function testUpdateTerminatedBankingAccountActivationDetails()
    {
        $this->ba->proxyAuth();

        $this->createBankingAccountFromDashboard();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->updateBankingAccount($bankingAccount, [
            'status' => Status::TERMINATED,
        ]);

        $request = [
            'url'       => '/banking_accounts/activation/' . $bankingAccount->getPublicId() . '/details',
            'method'    => 'PATCH',
            'content'   => [
                'status' => 'picked',
            ],
        ];

        $this->expectException(\RZP\Exception\BadRequestException::class);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals('BAD_REQUEST_BANKING_ACCOUNT_ACTIVATION_DETAILS_UPDATE_NOT_ALLOWED', $response['error']['internal_error_code']);
    }

    private function getXSegmentMock()
    {
        $xsegmentMock = $this->getMockBuilder(XSegmentClient::class)
            ->setMethods(['pushIdentifyandTrackEvent'])
            ->getMock();
        $this->app->instance('x-segment', $xsegmentMock);

        return $xsegmentMock;
    }


    // =========== RBL on BAS Tests ========== //

    // Test Get Single Application
    public function testApiToBasDtoAdapter()
    {
        $testData = $this->testData['testApiToBasDtoAdapter'];

        $input = $testData['apiInput'];

        $input[BankingAccount\Entity::ACTIVATION_DETAIL][ActivationDetail\Entity::SALES_POC_ID] = 'admin_'.Org::SUPER_ADMIN;

        $basInput = (new BasDtoAdapter)->fromApiInputToBasInput($input);

        $expectedOutput = $testData['expectedBasInput'];

        $expectedOutput['account_managers']['sales_poc']['name'] = 'test admin';
        $expectedOutput['account_managers']['sales_poc']['email'] = 'superadmin@razorpay.com';

        $this->assertArraySelectiveEquals($expectedOutput, $basInput);
    }

    // By default uses internal auth
    // if merchant is passed, then it uses merchant auth
    public function testGetRblApplicationFromMob($merchant = null)
    {
        $this->ba->mobAppAuthForInternalRoutes();

        if (empty($merchant) == false)
        {
            $this->ba->setMerchant($merchant);
        }

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_internal/bacc_JuLWj2OnFAcg72',
            ],
        ];

        $this->startTest($dataToReplace);
    }

    public function testGetRblApplicationFromMerchantDashboard()
    {
        $merchant = $this->getDbEntity('merchant', [
            'id' => self::DefaultMerchantId,
        ]);

        $this->testGetRblApplicationFromMob($merchant);
    }

    public function testGetRblApplicationFromAdminLms()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetRblApplicationFromPartnerLms()
    {
        $this->setupBankLMSTest();

        $this->startTest();
    }

    // Test Fetch Multiple Applications
    public function testFetchRblApplicationsFromAdminLms()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    // Test Fetch Multiple Applications
    public function testFetchRblApplicationsFromPartnerLms()
    {
        $response = $this->setupBankLMSTest();

        $dataToReplace = [
            'response' => [
                'content' => [
                    'items' => [
                        [
                            'id' => $response['bankingAccount']['id'],
                        ],
                        [
                            'id' => 'bacc_JuLWj2OnFAcg72'
                        ],
                    ]
                ]
            ]
        ];

        $this->startTest($dataToReplace);
    }

    // Test Update Application
    private function mockPatchRblApplicationComposite(array $response, array $expectedArgs)
    {
        $basMock = $this->bankingAccountServiceMock;

        $basMock->shouldReceive('patchRBLApplicationComposite')
                ->once()
                ->withArgs(function($applicationId, $payload) use ($expectedArgs) {
                    $this->assertEquals($expectedArgs['applicationId'], $applicationId);
                    $this->assertArraySelectiveEquals($expectedArgs['payload'], $payload);
                    return true;
                })
                ->andReturns($response);

        $this->app->instance('banking_account_service', $basMock);
    }

    public function testRblOnBasUpdateFromBatch()
    {
        $content = [
            'bank_reference_number' => '1234',
            'comment' => 'sample comment from batch',
            'source_team' => 'bank',
            'source_team_type' => 'external',
            'added_at' => 1594800229,
            'assignee_team' => 'sales',
            'account_open_date' => '23-Jun-2020',
            'account_login_date' => '23-Jun-2020'
        ];

        $this->mockPatchRblApplicationComposite([
            'business' => [
                'id' => 'LVFoXUXt8aLGQt',
            ],
            'person' => [
                'email_id' => 'yaxisgroupofindustries@gmail.com',
            ],
        ], [
            'applicationId' => '1234',
            'payload' => [
                'banking_account_application' => [
                    'assignee_team' => 'sales',
                    'metadata' => [
                        'account_login_date' => 1592850600,
                    ]
                ],
                'partner_bank_application'  => [
                    'account_opening_details'   => [
                        'account_open_date' => 1592850600
                    ]
                ]
            ]
        ]);

        $this->assertUpdateViaBatch($content);
    }

    public function testRblOnBasUpdateFromAdminLms()
    {
        $this->ba->adminAuth();

        $this->mockPatchRblApplicationComposite([
            'banking_account_application' => [
                'id' => 'JuLWj2OnFAcg72',
                'application_number' => '203128886',
                'application_status' => 'picked',
                'sub_status' => 'none',
            ],
        ], [
            'applicationId' => 'JuLWj2OnFAcg72',
            'payload' => [
                'banking_account_application' => [
                    'application_status' => 'picked',
                    'sub_status' => 'none',
                ]
            ]
        ]);

        $this->startTest();
    }

    public function testRblOnBasUpdateFromMerchantDashboard()
    {
        $this->mockPatchRblApplicationComposite([
            'business' => [
                'id' => 'LVFoXUXt8aLGQt',
            ],
            'person' => [
                'email_id' => 'yaxisgroupofindustries@gmail.com',
            ],
            'banking_account_application' => [
                'id' => 'JuLWj2OnFAcg72',
                'application_status' => 'created',
            ],
        ], [
            'payload' => [
                'banking_account_application' => [
                    'metadata' => [
                        'additional_details' => [
                            'agree_to_allocated_bank_and_amb' => 1,
                        ],
                    ]
                ],
            ],
            'applicationId' => 'JuLWj2OnFAcg72',
        ]);

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts_dashboard/bacc_JuLWj2OnFAcg72',
            ],
        ];

        $this->startTest($dataToReplace);
    }

    public function rblOnBasUpdate($dataToReplace, $status = Status::PICKED, $subStatus = Status::NONE)
    {
        $dataToReplace['response']['content']['status'] = $status;
        $dataToReplace['response']['content']['sub_status'] = $subStatus;

        $dataToReplace['request']['content']['status'] = $status;
        $dataToReplace['request']['content']['sub_status'] = $subStatus;

        $this->mockPatchRblApplicationComposite([
            'business' => [
                'id' => 'LVFoXUXt8aLGQt',
            ],
            'person' => [
                'email_id' => 'yaxisgroupofindustries@gmail.com',
            ],
            'banking_account_application' => [
                'id' => 'JuLWj2OnFAcg72',
                'application_status' => $status,
                'sub_status' => $subStatus,
                'metadata' => [
                    'additional_details' => [
                        'docket_delivered_date' => '1666204200',
                    ]
                ]
            ],
        ], [
            'payload' => [
                'banking_account_application' => [
                    'application_status' => $status,
                    'sub_status' => $subStatus,
                    'metadata' => [
                        'additional_details' => [
                            'docket_delivered_date' => '1666204200',
                        ]
                    ],
                ],
            ],
            'applicationId' => 'JuLWj2OnFAcg72',
        ]);

        $this->startTest($dataToReplace);
    }

    public function testRblOnBasUpdateFromPartnerLms()
    {
        $this->setupBankLMSTest();

        $dataToReplace = [
            'request' => [
                'url' => '/banking_accounts/rbl/lms/banking_account/bacc_JuLWj2OnFAcg72',
            ],
        ];

        $this->rblOnBasUpdate($dataToReplace, Status::VERIFICATION_CALL, Status::IN_PROCESSING);
    }

    public function testRblonBasAssignBankPoc()
    {
        $response = $this->setupBankLMSTest();

        $user = $response['user'];

        $assertInput = [
            'bank_poc_user_id'      => $user->getId(),
            'bank_poc_name'         => $user->getName(),
            'bank_poc_phone_number' => $user->getContactMobile(),
            'bank_poc_email'        => $user->getEmail(),
        ];

        $basMock = $this->bankingAccountServiceMock;

        $basMock->shouldReceive('assignBankPocForRblPartnerLms')
                ->once()
                ->withArgs(function($businessId, $applicationId, $basInput) use ($assertInput) {
                    $this->assertEquals('_', $businessId);
                    $this->assertEquals('JuLWj2OnFAcg72', $applicationId);
                    $this->assertEquals($assertInput, $basInput);
                    return true;
                })
                ->andReturns($basMock->getApplicationForRblPartnerLms('_', 'JuLWj2OnFAcg72'));

        $this->app->instance('banking_account_service', $basMock);

        $dataToReplace = [
            'request' => [
                'content' => [
                    'bank_poc_user_id' => $user->getId()
                ],
            ]
        ];

        $this->startTest($dataToReplace);
    }

    public function mockBankingAccountProcessRblAccountOpeningWebhook($status = 'Success')
    {
        $basMock = $this->bankingAccountServiceMock;

        $response = [
            RblGateway\Fields::RZP_ALERT_NOTIFICATION_RESPONSE => [
                RblGateway\Fields::HEADER => [
                    RblGateway\Fields::TRAN_ID => '12345',
                ],
                RblGateway\Fields::BODY   =>[
                    RblGateway\Fields::STATUS => $status
                ]
            ],
        ];

        $basMock->shouldReceive('processRblAccountOpeningWebhook')
                ->once()
                ->andReturns($response);

        $this->app->instance('banking_account_service', $basMock);
    }

    public function testRblOnBasWebhook()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testActivateRblApplication()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

}
