<?php

use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Models\Contact;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\BankingAccount;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Services\HubspotClient;
use RZP\Models\Admin\Permission;
use RZP\Tests\Functional\TestCase;
use Illuminate\Support\Facades\Mail;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Status;
use RZP\Models\BankingAccount\Gateway\Rbl;
use RZP\Models\BankingAccount\AccountType;
use RZP\Mail\BankingAccount\XProActivation;
use RZP\Models\BankingAccount\Activation\MIS;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Fixtures\Entity\User;
use RZP\Mail\BankingAccount\UpdatesForAuditor;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\P2p\Service\Base\Traits\EventsTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Mail\BankingAccount\StatusNotifications\Created;
use RZP\Mail\BankingAccount\StatusNotifications\Rejected;
use RZP\Mail\BankingAccount\StatusNotifications\Processed;
use RZP\Mail\BankingAccount\StatusNotifications\Cancelled;
use RZP\Mail\BankingAccount\StatusNotifications\Activated;
use RZP\Mail\BankingAccount\Activation as ActivationMails;
use RZP\Mail\BankingAccount\StatusNotifications\Processing;
use RZP\Models\BankingAccountStatement\Details as BasDetails;
use RZP\Mail\BankingAccount\StatusNotifications\Unserviceable;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;
use RZP\Models\BankingAccount\Gateway\Rbl\Processor as RblProcessor;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\DiscrepancyInDoc;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantNotAvailable;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantPreparingDoc;
use RZP\Mail\BankingAccount\StatusNotifications\Factory as StatusUpdateMailerFactory;

class BankingAccountTest extends TestCase
{
    use PaymentTrait;
    use DbEntityFetchTrait;
    use EventsTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/Helpers/BankingAccountTestData.php';

        parent::setUp();

        // storing the below in redis for purpose of test cases.
        $pincodeList = ['560030', '560034'];

        $this->app['redis']->sadd('rbl_pincode_set', $pincodeList);

        $this->app['config']->set('applications.banking_account.mock', true);

        $this->ba->proxyAuth();

        $this->ba->addXOriginHeader();

        $this->fixtures->on('live')->create('merchant_detail:sane', ['merchant_id'=>'10000000000000']);
        $this->fixtures->on('test')->create('merchant_detail:sane', ['merchant_id'=>'10000000000000']);
    }

    public function detachAdminPermission(string $permissionName)
    {
        $admin = $this->ba->getAdmin();

        $role = $admin->roles()->get()[0];

        $permissionId = (new Permission\Repository)->retrieveIdsByNames([$permissionName])[0];

        $role->permissions()->detach($permissionId);
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

    public function testCreateBankingAccountFormMerchantDashboard()
    {
        $attribute = ['activation_status' => 'activated'];

        $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

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
        $this->assertEquals('processed', $logs['items'][1]['status']);
        $this->assertEquals('api_onboarding_pending', $logs['items'][1]['sub_status']);
        $this->assertEquals('closed', $logs['items'][1]['bank_status']);

        $bankingAccountActivationDetail = $this->getDbLastEntity('banking_account_activation_detail');

        $this->assertEquals('ops', $bankingAccountActivationDetail['assignee_team']);

        return $response;
    }

    public function testValidateAccountOpeningDateInWebhook()
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

            return ($mail->subject === ActivationMails\AccountOpeningWebhookDataAmbiguity::SUBJECT && $mail->to[0]['address'] === 'x-onboarding@razorpay.com');
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

            return ($mail->subject === ActivationMails\AccountOpeningWebhookDataAmbiguity::SUBJECT && $mail->to[0]['address'] === 'x-onboarding@razorpay.com');
        });
    }

    public function testAccountOpeningWebhookWithExistingAccountNumber()
    {
        $this->createAccountOpeningSuccessfulWebhook();

        $bankingAccount = $this->setAuthAndCreateBankingAccount('1cXSLlUU8V9sXl');

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
        $this->testUpdatedStatusFromInitiatedToProcessing();

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->updateBankingAccount($bankingAccount, [
            Entity::STATUS                         => Status::PROCESSED,
            Entity::SUB_STATUS                     => Status::API_ONBOARDING_IN_PROGRESS,
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

        $this->startTest($resetWebhookDataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $statusChangeLogs = $this->getStatusChangeLog($bankingAccount);

        $this->assertNull($bankingAccount->getAccountNumber());

        $this->assertNull($bankingAccount->getBeneficiaryName());

        $this->assertEquals(RZP\Models\BankingAccount\Status::PROCESSING, $bankingAccount->getStatus());

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

        $this->assertEquals('processed', $bankingAccount->getStatus());

        $this->assertEquals('Success', $response['RZPAlertNotiRes']['Body']['Status']);
    }

    public function testFailedBankAccountInfoNotification()
    {
        $this->ba->appAuth('rzp_test', 'RANDOM_RBL_SECRET');

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

        $this->assertEquals(null, $bankingAccountActivationDetail['assignee_team']);

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
        $this->assertEquals(null, $logs['items'][1]['sub_status']);

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

        Mail::assertQueued(Activated::class);

        Mail::assertQueued(ActivationMails\StatusChange::class, function ($mail) use($bankingAccount)
        {
            $mail->build();

            return $mail->hasTo($bankingAccount->spocs()->first()['email']);
        });
    }

    public function testMerchantHasKeyAccessWithCaActivatedAndWithoutKyc()
    {
        Mail::fake();

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

        $this->assertEquals(null, $bankingAccountActivationDetail['assignee_team']);

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
        $this->assertEquals(null, $logs['items'][1]['sub_status']);

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

        Mail::assertQueued(Activated::class);

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
                                                              array $bankingAccount = null)
    {
        Mail::fake();

        if ($bankingAccount === null)
        {
            $attribute = ['activation_status' => 'activated'];

            $merchantDetail = $this->fixtures->edit('merchant_detail', '10000000000000', $attribute);

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

        $this->fixtures->edit('banking_account',
            $bankingAccount['id'],
            [
                'status'               => $initialStatus,
                'sub_status'           => $initialSubStatus,
                'bank_internal_status' => $initialBankStatus
            ]);

        $this->startTest($dataToReplace);

        $updatedBankingAccount = $this->getDbEntityById('banking_account', $bankingAccount['id']);

        $bankingAccountStateUpdate = $this->getDbLastEntity('banking_account_state');

        $this->assertEquals($bankingAccount['id'], $bankingAccountStateUpdate->bankingAccount->getPublicId());

        $this->assertEquals($finalStatus, $bankingAccountStateUpdate['status']);

        $this->assertEquals('10000000000000', $bankingAccountStateUpdate['merchant_id']);

        $this->assertEquals($finalSubStatus, $bankingAccountStateUpdate['sub_status']);

        $this->assertEquals($finalBankStatus, $bankingAccountStateUpdate['bank_status']);

        if (($finalStatus !== $initialStatus)
            and (in_array($finalStatus, [Status::INITIATED, Status::PICKED, Status::ARCHIVED]) === false))
        {
            $mailableClass = RZP\Mail\BankingAccount\StatusNotifications\Factory::getMailer($updatedBankingAccount);

            Mail::assertQueued(get_class($mailableClass));
        }
        else
        {
            Mail::assertNothingSent();
        }
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
            Status::PICKED);
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
            Status::ARCHIVED);
    }

    public function testUpdateBankingAccountStatusArchivedToProcessed()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::ARCHIVED,
            Status::PROCESSED);
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

    public function testUpdateBankingAccountSubStatusForDocsWalkThrough()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            null,
            Status::DOCS_WALK_THROUGH_PENDING);
    }

    public function testUpdateBankingAccountSubStatusForNeedClarification()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::PICKED,
            null,
            Status::NEEDS_CLARIFICATION_FROM_SALES);
    }

    public function testUpdateBankingAccountStatusWithSubStatus()
    {
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
            Status::MERCHANT_NOT_AVAILABLE);
    }

    public function testUpdateBankingAccountStatusWithBlockedSubstatusMapping()
    {
        $this->expectException(BadRequestValidationFailureException::class);

        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::ARCHIVED,
            Status::READY_TO_SEND_TO_BANK);
    }

    public function testUpdateBankingAccountStatusWithNoneSubStatus()
    {
        $this->assertUpdateBankingAccountStatusFromTo(
            Status::PICKED,
            Status::INITIATED,
            Status::MERCHANT_NOT_AVAILABLE,
            Status::NONE);
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

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
            ->setMethods(['pushTrackEvent'])
            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
            ->method('pushTrackEvent')
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

        (new BankingAccount\Core)->notifyIfStatusChanged($bankingAccount,true,false);

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

        Mail::assertQueued(Processed::class);

        $bankingAccountEntity = $this->getDbLastEntity('banking_account');

        Mail::assertQueued(ActivationMails\StatusChange::class, function ($mail) use($bankingAccountEntity)
        {
            $mail->build();

            return $mail->hasTo($bankingAccountEntity->spocs()->first()['email']);
        });
        $this->assertTrue($expectedHubspotCall);
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

        Mail::assertQueued(Unserviceable::class);
    }

    public function testUpdateBankingAccountToInitiated()
    {
        $this->fixtures->terminal->createBankAccountTerminalForBusinessBanking();
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

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail->merchant['id']);

        $this->ba->addXOriginHeader();

        $bankingAccount = $this->createBankingAccountFromDashboard($activationDetails);

        $this->ba->proxyAuth();

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/' . $bankingAccount['id'],
                'method'  => 'GET',
            ],
        ];

        $this->expectException(\RZP\Exception\BadRequestException::class);

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

    protected function createBankingAccountFromDashboard(array $attributes = [])
    {
        $data = [
            Entity::PINCODE => '560030',
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

        $hubspotClient = $this->mockHubSpotClient('trackHubspotEvent');

        $hubspotClient->expects($this->atLeast(1))
                      ->method('trackHubspotEvent');


        $response = $this->makeRequestAndGetContent($request);

        Mail::assertNotQueued(XProActivation::class);

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

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $ba['id'];
        $this->testData[__FUNCTION__]['response']['content']['items'][0]['banking_account_activation_details'][ActivationDetail\Entity::MERCHANT_CITY] = $dbName;

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBankingAccountFetchForDocsWalkthrough(bool $dbValue = true, bool $searchValue = true)
    {
        $ba = $this->testCreateActivationDetail([
            ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE => $dbValue
        ]);

        $this->testData[__FUNCTION__]['request']['content'][ActivationDetail\Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE] = $searchValue;

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $ba['id'];
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

        $this->testData[__FUNCTION__]['response']['content']['items'][0]['id'] = $ba['id'];
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

        Mail::assertQueued(Created::class);
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

        Mail::assertQueued(Cancelled::class);
    }

    public function testUpdatedStatusFromInitiatedToProcessing()
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
                                  'status' => 'initiated',
                              ]);

        $this->startTest($dataToReplace);

        $bankingAccount = $this->getDbLastEntity('banking_account');

        $this->assertEquals(RZP\Models\BankingAccount\Status::PROCESSING, $bankingAccount->getStatus());

        Mail::assertQueued(Processing::class);
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

        Mail::assertQueued(Processed::class);
    }

    public function testUpdateOnDiffBankInternalStatus()
    {
        $bankingAccount = $this->createBankingAccount();

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
            Rbl\Status::MERCHANT_PREPARING_DOCS);
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

        Mail::assertQueued(Rejected::class);
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
        $this->testData[__FUNCTION__]['request']['content']['banking_account_ids'][1]     = 'bacc_wrongCurAccId2';

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


        $comment = "Sample comment";

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
            $dataToReplace['request']['content'] = $comment;
            $dataToReplace['response']['content'] = $comment;
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
                    ActivationDetail\Entity::BUSINESS_CATEGORY     => 'partnership',
                    ActivationDetail\Entity::SALES_TEAM            => 'self_serve',
                    ActivationDetail\Entity::BUSINESS_PAN          => 'RZPD38493L',
                    ActivationDetail\Entity::BUSINESS_NAME         => 'ABC pvt',
                    ActivationDetail\Entity::DECLARATION_STEP      => 1,
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
            ActivationDetail\Entity::ADDITIONAL_DETAILS => json_encode(["green_channel" => false])
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
//        $this->createMerchantDetail();

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

        $bankingAccount = $this->createBankingAccount();

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
        ]);

        $misProcessor = new MIS\Leads([]);

        // Assigning first value of array to fileinput to test with the input we created
        $fileInput[0] = $misProcessor->getFileInput()[0];

        // voluntarily mis-aligned to assert new line
        // TODO: Assert cleanly

        $expectedFileInput = [
            [
                'Customer Name' =>  $bankingAccountEntity->merchant->name,
                'Customer Reference Number' => '10000',
                'POC Name' => 'Sample Name',
                'POC Designation' => 'Financial Consultant',
                'Customer email' => 'sample@sample.com',
                'Customer phone number' => '9876556789',
                'Pincode Where CA is to be Opened' => $bankingAccountEntity->getPincode(),
                'Constitution Type' => 'Partnership',
                'ICV' => 222,
                'Application Submission Date' => date('Y-m-d'),
                'Timestamp' => Carbon::createFromTimestamp(time(), Timezone::IST)->format('h:i A'),
                'Business Model' => null,
                'Account Type' => 'Insignia',
                'Comments' => 'Sample comment',
                'GMV' => 40000,
                'Razorpay POC Name' =>  $bankingAccountEntity->spocs()->first()->name,
                'Razorpay POC Number' =>  $bankingAccountEntity->bankingAccountActivationDetails[ActivationDetail\Entity::SALES_POC_PHONE_NUMBER],
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

    public function testVerifyOtpForContact()
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

        $dataToReplace = [
            'request'  => [
                'url'     => '/banking_accounts/verify_otp/' . $bankingAccount['id'],
                'method'  => 'POST',
            ],
        ];

        $this->startTest($dataToReplace);
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
}
