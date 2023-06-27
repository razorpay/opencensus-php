<?php

namespace RZP\Tests\Functional\BankingAccountService;

use App;
use Carbon\Carbon;

use Illuminate\Support\Facades\Mail;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Mail\BankingAccount\Activation\StatusChange;
use RZP\Models\Admin;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\Merchant;
use RZP\Mail\BankingAccount\CurrentAccount;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Services\SalesForceClient;
use RZP\Exception\DbQueryException;
use RZP\Models\BankingAccount\Status;
use RZP\Exception\BadRequestException;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Models\Settlement\Channel;
use RZP\Tests\Functional\TestCase;
use RZP\Mail\BankingAccount\XProActivation;
use RZP\Models\BankingAccountService\Constants;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\BankingAccount\Activation\Detail\Validator;
use RZP\Mail\BankingAccount\StatusNotificationsToSPOC\MerchantNotAvailable;
use RZP\Exception\BadRequestValidationFailureException;

class BankingAccountServiceTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use MocksDiagTrait;
    use RequestResponseFlowTrait;

    protected $config;

    protected $sfLeadsTimeStamp;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankingAccountServiceTestData.php';

        parent::setUp();

        $this->ba->bankingAccountServiceAppAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->config = App::getFacadeRoot()['config'];

        $this->sfLeadsTimeStamp = (int) $this->config['applications.banking_account_service.rbl_leads_sf_time_filter'];
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

    public function testCreateBankingEntities($bank = 'icici')
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

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $this->ba->bankingAccountServiceAppAuth();

        $dataToReplace = [
            'request'  => [
                'content' => [
                    Constants::CHANNEL        => $bank,
                ]
            ]
        ];

        $response = $this->startTest($dataToReplace);

        $balance = $this->getDbEntity('balance',
                                      [
                                              'merchant_id'    => '10000000000000',
                                              'channel'        => $bank,
                                              'account_type'   => 'direct',
                                              'account_number' => '12345678903833',
                                          ]);

        $this->assertNotNull($balance);

        $this->assertEquals($balance->getId(), $response['balance_id']);

        $basd = $this->getDbEntity('banking_account_statement_details',
                                       [
                                           'merchant_id'    => '10000000000000',
                                           'channel'        => $bank,
                                           'balance_id'     => $balance->getId(),
                                           'account_number' => '12345678903833',
                                       ]);

        $this->assertNotNull($basd);

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        // Every activated merchant should have a default schedule task for fee recovery purposes.
        $this->assertEquals(10000000000000, $scheduleTask['merchant_id']);
        $this->assertEquals($balance->getId(), $scheduleTask['entity_id']);
        $this->assertEquals('balance', $scheduleTask['entity_type']);
        $this->assertEquals($schedule['id'], $scheduleTask['schedule_id']);

        $feature = $this->getLastEntity('feature', true);

        $this->assertEquals($feature['name'], 'enable_ip_whitelist');
        $this->assertEquals($feature['entity_id'], '10000000000000');

        return $response;
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

    public function testArchiveAndCreateNewAccount()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'ICICI');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_proceeded_bank', 'ICICI');

        $response = $this->testCreateBankingEntities();

        $balance_id1 = $response['balance_id'];

        $dataToReplace = [
            'request' => [
                'url'     => '/bas/archive_banking_account_dependencies',
                'content' => [
                    "balance_id" => $balance_id1,
                    "merchant_id" => 10000000000000,
                ]
            ]
        ];

        $this->ba->bankingAccountServiceAppAuth();

        $response = $this->startTest($dataToReplace);

        $merchant_detail = $this->getDbEntity('merchant_detail',
                                                    [
                                                        'merchant_id'    => '10000000000000',
                                                    ]);

        $this->assertNull($merchant_detail->getBasBusinessId());

        $request = [
            'url'     => '/bas/merchant/10000000000000/banking_accounts',
            'method'  => 'POST',
            'content' => [
                Constants::ACCOUNT_NUMBER => '12345678903834',
                Constants::CHANNEL        => 'icici',
            ]
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals($balance_id1, $response['balance_id']);
    }

    public function testArchive()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'ICICI');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_proceeded_bank', 'ICICI');

        $response = $this->testCreateBankingEntities();

        $balance_id1 = $response['balance_id'];

        $dataToReplace = [
            'request' => [
                'url'     => '/bas/archive_banking_account_dependencies',
                'content' => [
                    "balance_id" => $balance_id1,
                    "merchant_id" => 10000000000000,
                ]
            ]
        ];

        $this->ba->bankingAccountServiceAppAuth();

        $response = $this->startTest($dataToReplace);

        $merchant_detail = $this->getDbEntity('merchant_detail',
                                              [
                                                  'merchant_id'    => '10000000000000',
                                              ]);

        $this->assertNull($merchant_detail->getBasBusinessId());
    }

    public function testGetMerchantAttributes()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'RBL');
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_proceeded_bank', 'RBL');

        $this->startTest();
    }

    public function testArchiveRbl()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'RBL');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_proceeded_bank', 'RBL');

        $response = $this->testCreateBankingEntities('rbl');

        $balance_id1 = $response['balance_id'];

        $dataToReplace = [
            'request' => [
                'url'     => '/bas/archive_banking_account_dependencies',
                'content' => [
                    Constants::BALANCE_ID => $balance_id1,
                    Constants::MERCHANT_ID => 10000000000000,
                ]
            ]
        ];

        $this->ba->bankingAccountServiceAppAuth();

        $response = $this->startTest($dataToReplace);

        $merchant_detail = $this->getDbEntity('merchant_detail',
                                              [
                                                  'merchant_id'    => '10000000000000',
                                              ]);

        $this->assertNull($merchant_detail->getBasBusinessId());
    }

    public function testArchiveAndUnArchive()
    {
        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_allocated_bank', 'ICICI');

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'ca_proceeded_bank', 'ICICI');

        $response = $this->testCreateBankingEntities();

        $balance_id1 = $response['balance_id'];

        $dataToReplace = [
            'request' => [
                'url'     => '/bas/archive_banking_account_dependencies',
                'content' => [
                    "balance_id" => $balance_id1,
                    "merchant_id" => 10000000000000,
                ]
            ]
        ];

        $this->ba->bankingAccountServiceAppAuth();

        $response = $this->startTest($dataToReplace);

        $merchant_detail = $this->getDbEntity('merchant_detail',
                                              [
                                                  'merchant_id'    => '10000000000000',
                                              ]);

        $this->assertNull($merchant_detail->getBasBusinessId());

        $dataToReplace = [
            'request' => [
                'url'     => '/bas/unarchive_banking_account_dependencies',
                'content' => [
                    "business_id" => '23sdasfr34454',
                    "merchant_id" => 10000000000000,
                    Constants::PARTNER_BANK => Constants::ICICI,
                ]
            ]
        ];

        $this->ba->bankingAccountServiceAppAuth();

        $response = $this->startTest($dataToReplace);

        $merchant_attributes = $this->getDbEntities('merchant_attribute',
                                                    [
                                                        'merchant_id'    => '10000000000000',
                                                    ]);

        $this->assertEquals('ICICI', $merchant_attributes[0]->getValue());

        $this->assertEquals('ICICI', $merchant_attributes[1]->getValue());

        $merchant_detail = $this->getDbEntity('merchant_detail',
                                              [
                                                  'merchant_id'    => '10000000000000',
                                              ]);

        $this->assertNotNull($merchant_detail->getBasBusinessId());
    }

    public function testCreateBankingEntitiesWithLedgerShadow()
    {
        $this->enableRazorXTreatmentForXOnboarding('on', 'off');

        $ledgerSnsPayloadArray = [];
        $this->mockLedgerSns(1, $ledgerSnsPayloadArray);

        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $this->ba->bankingAccountServiceAppAuth();

        $response = $this->startTest();

        $balance = $this->getDbEntity('balance',
            [
                'merchant_id'    => '10000000000000',
                'channel'        => 'icici',
                'account_type'   => 'direct',
                'account_number' => '12345678903833',
            ]);

        $this->assertNotNull($balance);

        $this->assertEquals($balance->getId(), $response['balance_id']);

        $bankingAccountStmtDetails = $this->getDbEntity('banking_account_statement_details',
            [
                'merchant_id'    => '10000000000000',
                'channel'        => 'icici',
                'balance_id'     => $balance->getId(),
                'account_number' => '12345678903833',
            ]);

        $this->assertNotNull($bankingAccountStmtDetails);

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        // Every activated merchant should have a default schedule task for fee recovery purposes.
        $this->assertEquals(10000000000000, $scheduleTask['merchant_id']);
        $this->assertEquals($balance->getId(), $scheduleTask['entity_id']);
        $this->assertEquals('balance', $scheduleTask['entity_type']);
        $this->assertEquals($schedule['id'], $scheduleTask['schedule_id']);

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

    public function testInitiateBVSValidationForPersonalPan()
    {
        $this->ba->bankingAccountServiceAppAuth();

        $this->app['config']->set('applications.kyc.mock', true);
        $this->app['config']->set('services.bvs.mock', true);
        $this->app['config']->set('services.bvs.response', 'success');

        $this->startTest();
    }

    public function testInitiateBVSValidationForBusinessPan()
    {
        $this->ba->bankingAccountServiceAppAuth();

        $this->app['config']->set('applications.kyc.mock', true);
        $this->app['config']->set('services.bvs.mock', true);
        $this->app['config']->set('services.bvs.response', 'success');

        $this->startTest();
    }

    public function testCreateBankingEntitiesAndAddPayoutFeatureAndAllowHasKeyAccess()
    {
        $schedule = $this->setupDefaultScheduleForFeeRecovery();

        $attributes = [
            'bas_business_id'   => '10000000000000',
            'activation_status' => 'deactivated',
            'business_website' => 'www.businesswebsite.com'
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $feature = $this->getDbEntity('feature',
            [
                'entity_id'    => '10000000000000',
                'name'         => 'payout',
                'entity_type'  => 'merchant'
            ]);

        $this->assertNull($feature);

        $testMerchant = $this->getDbEntity('merchant',
            [
                'id'    => '10000000000000',
            ]);

        $liveMerchant = $this->getDbEntity('merchant',
            [
                'id'    => '10000000000000',
            ], 'live');

        $this->assertEquals(0, $testMerchant['has_key_access']);

        $this->assertEquals(0, $liveMerchant['has_key_access']);

        $this->ba->bankingAccountServiceAppAuth();

        $response = $this->startTest();

        $balance = $this->getDbEntity('balance',
            [
                'merchant_id'    => '10000000000000',
                'channel'        => 'icici',
                'account_type'   => 'direct',
                'account_number' => '12345678903833',
            ]);

        $this->assertNotNull($balance);

        $this->assertEquals($balance->getId(), $response['balance_id']);

        $basd = $this->getDbEntity('banking_account_statement_details',
            [
                'merchant_id'    => '10000000000000',
                'channel'        => 'icici',
                'balance_id'     => $balance->getId(),
                'account_number' => '12345678903833',
            ]);

        $this->assertNotNull($basd);

        $feature = $this->getDbEntity('feature',
            [
                'entity_id'    => '10000000000000',
                'name'         => 'payout',
                'entity_type'  => 'merchant'
            ]);

        $this->assertEquals('payout', $feature['name']);

        $testMerchant = $this->getDbEntity('merchant',
            [
                'id'    => '10000000000000',
            ]);

        $liveMerchant = $this->getDbEntity('merchant',
            [
                'id'    => '10000000000000',
            ], 'live');

        $this->assertEquals(1, $testMerchant['has_key_access']);

        $this->assertEquals(1, $liveMerchant['has_key_access']);

        $scheduleTask = $this->getDbLastEntity('schedule_task')->toArray();

        // Every activated merchant should have a default schedule task for fee recovery purposes.
        $this->assertEquals(10000000000000, $scheduleTask['merchant_id']);
        $this->assertEquals($balance->getId(), $scheduleTask['entity_id']);
        $this->assertEquals('balance', $scheduleTask['entity_type']);
        $this->assertEquals($schedule['id'], $scheduleTask['schedule_id']);
    }

    public function testCreateBusinessId()
    {
        $this->ba->proxyAuth();

        $this->createMerchantDetailWithBusinessId();

        $this->startTest();

        $merchantDetail = $this->getDbEntity('merchant_detail',
                                      [
                                          'bas_business_id'  => '30000000000888',
                                      ]);

        $this->assertNotNull($merchantDetail);
    }

    public function testCreateBusinessWithIndividualConstitution()
    {
        $this->ba->proxyAuth();

        $this->createMerchantDetailWithBusinessId();

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_BANKING_ACCOUNT_CONSTITUTION_NOT_SUPPORTED);

        $this->expectException(BadRequestException::class);

        $this->startTest();
    }

    public function testCron()
    {
        $this->ba->cronAuth();

        $response = $this->startTest();

        $this->assertEquals('ACTIVE', $response['data']['status']);
    }

    public function testLmsAll()
    {
        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertEquals('30000000000888', $response['data']['id']);
    }

    public function testBusinessIdAssigmentInLMSWhileApplyToBankingAccount()
    {
        $this->ba->adminAuth();

        $this->createMerchantDetailWithBusinessId();

        $response = $this->startTest();

        $this->assertEquals('10000000000000', $response['data']['business_id']);
    }

    public function testBusinessIdAssigmentInLMSWhileApplyToBankingAccountForMob()
    {
        $this->ba->mobAppAuthForInternalRoutes();

        $this->createMerchantDetailWithBusinessId();

        $this->testData[__FUNCTION__] = $this->testData['testBusinessIdAssigmentInLMSWhileApplyToBankingAccount'];

        $this->testData[__FUNCTION__]['request']['url'] = '/bas_internal/lms/admin/apply';

        $response = $this->startTest();

        $this->assertEquals('10000000000000', $response['data']['business_id']);
    }

    public function testLmsErrorFromBas()
    {
        $this->ba->adminAuth();

        $this->expectException(BadRequestException::class);

        $this->startTest();
    }

    public function testLmsOps()
    {
        $this->ba->adminAuth();

        $response = $this->startTest();

        $this->assertEquals('30000000000888', $response['data']['id']);
    }

    public function testVendorPaymentCompositeExpands()
    {
        $this->ba->appAuthTest($this->config['applications.vendor_payments.secret']);

        $this->fixtures->create('user', ['id' => '10000000000000', 'name' => 'test-me']);

        $this->fixtures->create(
            'fund_account',
            [
                'id'           => '100000000000fa',
                'source_id'    => '1000001contact',
                'source_type'  => 'contact',
                'account_type' => 'bank_account',
                'account_id'   => '1000000lcustba'
            ]);

        $this->setUpMerchantForBusinessBanking(false, 10000000);

        $this->fixtures->create('contact', ['id' => 'Dsp92d4N1Mmm6Q', 'name' => 'test_contact']);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUT',
                                    'source_type' => 'contact',
                                    'source_id'   => 'Dsp92d4N1Mmm6Q',
                                    'merchant_id' => '10000000000000'
                                ]);

        $this->fixtures->create('fund_account:bank_account',
                                [
                                    'id'          => 'D6Z9Jfir2egAUD',
                                    'source_type' => 'contact',
                                    'source_id'   => 'Dsp92d4N1Mmm6Q',
                                    'merchant_id' => '10000000000000'
                                ]);

        $this->fixtures->create('payout', ['id' => 'DuuYxmO7Yegu3x', 'fund_account_id' => 'D6Z9Jfir2egAUT','pricing_rule_id' => '1nvp2XPMmaRLxb']);

        //overwriting the balance entity to match conditions required to call mocked bas method.
        $this->fixtures->edit('balance', '10000000000000',
                              [
                                  'balance'      => 1000,
                                  'type'         => 'banking',
                                  'account_type' => 'direct',
                                  'channel'      => 'icici',
                              ]);

        $this->startTest();
    }

    public function testBusinessApplicationSignatories()
    {
        $this->ba->proxyAuth();

        $attributes = [
            'bas_business_id'   => '10000000000000',
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $response = $this->startTest();

        $this->assertEquals('20000000000000', $response['person_id']);

        $this->assertEquals('30000000000000', $response['data']['id']);

        $this->assertEquals('AUTHORIZED_SIGNATORY', $response['data']['signatories'][0]['signatory_type']);

        $this->assertEquals('20000000000000', $response['data']['signatories'][0]['person_id']);

        $applicationSpecificFields = $response['data']['application_specific_fields'];

        $this->assertEquals('N', $applicationSpecificFields['isBusinessGovtBodyOrLiasedOnUnrecognisedStockOrInternationalOrg']);

        $this->assertEquals('Y', $applicationSpecificFields['isIndianFinancialInstitution']);

        $this->assertEquals('N', $applicationSpecificFields['isOwnerNotIndianCitizen']);

        $this->assertEquals('Y', $applicationSpecificFields['isTaxResidentOutsideIndia']);

        $this->assertEquals('ACCOUNTANT', $applicationSpecificFields['role_in_business']);
    }

    public function testBusinessApplicationSignatoriesWithMobAuth()
    {
        $this->testData[__FUNCTION__] = $this->testData['testBusinessApplicationSignatories'];

        $this->ba->mobAppAuthForProxyRoutes();

        $attributes = [
            'bas_business_id' => '10000000000000',
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $request = &$this->testData[__FUNCTION__]['request'];

        $response = $this->startTest();

        $this->assertEquals('20000000000000', $response['person_id']);

        $this->assertEquals('30000000000000', $response['data']['id']);

        $this->assertEquals('AUTHORIZED_SIGNATORY', $response['data']['signatories'][0]['signatory_type']);

        $this->assertEquals('20000000000000', $response['data']['signatories'][0]['person_id']);

        $applicationSpecificFields = $response['data']['application_specific_fields'];

        $this->assertEquals('N', $applicationSpecificFields['isBusinessGovtBodyOrLiasedOnUnrecognisedStockOrInternationalOrg']);

        $this->assertEquals('Y', $applicationSpecificFields['isIndianFinancialInstitution']);

        $this->assertEquals('N', $applicationSpecificFields['isOwnerNotIndianCitizen']);

        $this->assertEquals('Y', $applicationSpecificFields['isTaxResidentOutsideIndia']);

        $this->assertEquals('ACCOUNTANT', $applicationSpecificFields['role_in_business']);
    }

    public function testBusinessApplicationSignatoriesWithDocCollectionDetails()
    {
        $this->ba->proxyAuth();

        $attributes = [
            'bas_business_id'   => '10000000000000',
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $response = $this->startTest();

        $this->assertEquals('20000000000000', $response['person_id']);

        $this->assertEquals('30000000000000', $response['data']['id']);

        $this->assertEquals('AUTHORIZED_SIGNATORY', $response['data']['signatories'][0]['signatory_type']);

        $this->assertEquals('20000000000000', $response['data']['signatories'][0]['person_id']);

        $this->assertEquals($request['content']['application_specific_fields']['business_document_mapping'], $response['data']['application_specific_fields']['business_document_mapping']);

        $this->assertEquals($request['content']['signatories']['document'], $response['data']['application_specific_fields']['persons_document_mapping']['20000000000000']);

    }

    public function createMerchantDetailWithBusinessId(array $attributes = [])
    {
        $default = [
            'activation_status' => 'activated',
            'merchant_id'       => '10000000000000',
            'business_type'     => '2',
        ];

        $attributes = array_merge($default, $attributes);

        return $this->fixtures->create('merchant_detail', $attributes);
    }

    public function testFetchMerchantInfo()
    {
        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/merchants_internal/' . '10000000000000';

        $response = $this->startTest();

        $merchant = $this->getDbEntity('merchant',
                                             [
                                                 'id'  => '10000000000000',
                                             ]);

        $this->assertEquals($merchant->getName(), $response['name']);
    }

    public function testFetchMerchantDetailsInfo()
    {
        $request = & $this->testData[__FUNCTION__]['request'];

        $request['url'] = '/internal/merchants/10000000000000';

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000', 'contact_email' => 'test@razorpay.com', 'contact_mobile' => '9876543210']);

        $response = $this->startTest();

        $merchant = $this->getDbEntity('merchant',
            [
                'id'  => '10000000000000',
            ]);

        $this->assertEquals($merchant->getEmail(), $response['merchant_detail']['contact_email']);
    }

    public function testPinCodeServiceabilityForIcici()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(true, $response['data']['serviceable']);
    }

    public function testPinCodeServiceabilityBulk()
    {
        $this->ba->directAuth();

        $response = $this->startTest();

    }

    public function testSlotBookingForBankingAccount()
    {
        $this->ba->proxyAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'bas_business_id'            => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
        ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['id'] = $ba1->getPublicId();

        $response = $this->startTest();

        $this->assertEquals('#TE-00038', $response['bookingDetails']['bookingId']);
    }

    public function testSlotBookingForBankingAccountForMerchantWithClarityContext()
    {
        Mail::fake();

        $this->ba->proxyAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'bas_business_id'            => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
        ]);

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_current_accounts', 'clarity_context', 'enabled');

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['id'] = $ba1->getPublicId();

        $response = $this->startTest();

        $this->assertEquals('#TE-00038', $response['bookingDetails']['bookingId']);

        Mail::assertQueued(XProActivation::class);
    }

    public function testSlotRescheduleForBankingAccount()
    {
        $this->ba->proxyAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'bas_business_id'            => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
            'booking_date_and_time'     => strtotime('17-Nov-2021 11:30:00'),
            "additional_details"        => json_encode(["booking_id" => "#TE-00037"])
        ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['id'] = $ba1->getPublicId();

        $response = $this->startTest();

        $this->assertEquals('#TE-00038', $response['bookingDetails']['bookingId']);
    }

    public function testSlotRescheduleForBankingAccountIfDateAndTimeOfBookingIsSame()
    {
        $this->ba->proxyAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'bas_business_id'            => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
            'booking_date_and_time'     => strtotime('17-Nov-2021 14:30:00'),
            "additional_details"        => json_encode(["booking_id" => "#TE-00037"])
        ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['id'] = $ba1->getPublicId();

        $response = $this->startTest();

        $this->assertEquals('Failure', $response['status']);

        $this->assertEquals('Slot is already booked for the same date and time, it cannot be booked again', $response['ErrorDetail']['errorReason']);
    }

    public function testSlotRescheduleForBankingAccountIfAdditionalDetailsIsEmpty()
    {
        $this->ba->proxyAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'bas_business_id'            => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
        ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['id'] = $ba1->getPublicId();

        $response = $this->startTest();

        $this->assertEquals('Failure', $response['status']);

        $this->assertEquals('Slot is not booked previously, so you cannot reschedule it, as bookingId is empty, Please book the slot first', $response['ErrorDetail']['errorReason']);
    }

    public function testAvailableSlotsForBankingAccount()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertIsArray($response['data']);
    }

    public function testRecentAvailableSlotsForBankingAccount()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertIsArray($response['data']);
    }

    public function testSlotBookingForBankingAccountIfThatSlotIsAlreadyBooked()
    {
        $this->ba->proxyAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'bas_business_id'            => '10000000000000',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
            'booking_date_and_time'     => strtotime('17-Nov-2021 11:30:00')
        ]);

        $request = & $this->testData[__FUNCTION__]['request'];

        $request['content']['id'] = $ba1->getPublicId();

        $response = $this->startTest();

        $this->assertEquals('Failure', $response['status']);
    }

    public function testDeleteSignatory()
    {
        $this->ba->proxyAuth();

        $attributes = [
            'bas_business_id'   => '10000000000000',
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $response = $this->startTest();

        $this->assertEquals(true, $response['deleted']);
    }

    public function testUpdateSignatory()
    {
        $this->ba->proxyAuth();

        $attributes = [
            'bas_business_id'   => '10000000000000',
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $response = $this->startTest();

        $this->assertEquals('20000000000000', $response['person_id']);

        $this->assertEquals('30000000000000', $response['data']['id']);

        $this->assertEquals('AUTHORIZED_SIGNATORY', $response['data']['signatories'][0]['signatory_type']);

        $this->assertEquals('20000000000000', $response['data']['signatories'][0]['person_id']);

        $this->assertEquals($request['content']['application_specific_fields']['business_document_mapping'], $response['data']['application_specific_fields']['business_document_mapping']);

        $this->assertEquals($request['content']['signatories']['document'], $response['data']['application_specific_fields']['persons_document_mapping']['20000000000000']);
    }

    public function testCreateSignatory()
    {
        $this->ba->proxyAuth();

        $attributes = [
            'bas_business_id'   => '10000000000000',
        ];

        $this->createMerchantDetailWithBusinessId($attributes);

        $request = & $this->testData[__FUNCTION__]['request'];

        $response = $this->startTest();

        $this->assertEquals('20000000000000', $response['person_id']);

        $this->assertEquals('30000000000000', $response['data']['id']);

        $this->assertEquals('AUTHORIZED_SIGNATORY', $response['data']['signatories'][0]['signatory_type']);

        $this->assertEquals('20000000000000', $response['data']['signatories'][0]['person_id']);

        $this->assertEquals($request['content']['application_specific_fields']['business_document_mapping'], $response['data']['application_specific_fields']['business_document_mapping']);

        $this->assertEquals($request['content']['signatories']['document'], $response['data']['application_specific_fields']['persons_document_mapping']['20000000000000']);
    }

    public function testSendCaLeadToSalesForce()
    {
        $this->ba->bankingAccountServiceAppAuth();

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'x_signup_platform', 'x_mobile');

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test1@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'contact_mobile'             => '1234567890',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $this->mockSalesForce('sendCaLeadDetails', 1);

        $this->startTest();
    }


    public function testSendCaLeadStatusToSalesForce()
    {
        $this->ba->bankingAccountServiceAppAuth();

        $this->createMerchantAttribute('10000000000000', 'banking', 'x_merchant_preferences', 'x_signup_platform', 'x_mobile');

        $this->mockSalesForce('sendLeadStatusUpdate', 1);

        $this->startTest();
    }

    public function testSendCaLeadToFreshDesk()
    {
        $this->ba->bankingAccountServiceAppAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test1@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated',
            'contact_mobile'             => '1234567890',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $expectedPayload = [
            'merchant_id'               => '10000000000000',
            'CA_Preferred_Phone'        => '33322323',
            'CA_Preferred_Email'        => 'abc@def.com',
            'merchant_name'             => 'test merchant',
            'merchant_email'            => 'test@test.com',
            'merchant_phone'            => '929292929',
            'constitution'              => 'PRIVATE_LIMITED',
            'pincode'                   => '332332',
            'sales_team'                => 'SELF_SERVE',
            'account_manager_name'      => 'test_name',
            'account_manager_email'     => 'testemail@test.com',
            'account_manager_phone'     => '33332222',
        ];

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('trackOnboardingEvent')
            ->once()
            ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                $this->assertEquals($expectedPayload, $actualData);
                $this->assertEquals(EventCode::X_CA_ONBOARDING_FRESHDESK_TICKET_CREATE_ICICI, $eventData);
                return true;
            })
            ->andReturnNull();

        Mail::fake();

        $this->startTest();

        Mail::assertQueued(CurrentAccount::class, function ($mail) {
            $mail->build();
            return $mail->subject === 'RazorpayX | Current Account [10000000000000 | test merchant]';
        });

    }

    public function testSendIciciVideoKycLeadToFreshDesk()
    {
        $this->ba->bankingAccountServiceAppAuth();

        $merchantDetailArray = [
            'contact_name'                     => 'rzp',
            'contact_email'                    => 'test1@rzp.com',
            'merchant_id'                      => '10000000000000',
            'business_operation_address'       => 'Koramangala',
            'business_operation_state'         => 'KARNATAKA',
            'business_operation_pin'           => 560034,
            'business_dba'                     => 'test',
            'business_name'                    => 'INTERNET BANKING CA',
            'business_operation_city'          => 'Bangalore',
            'activation_status'                => 'activated',
            'contact_mobile'                   => '1234567890',
        ];

        $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $expectedPayload = [
            'merchant_id'               => '10000000000000',
            'CA_Preferred_Phone'        => '33322323',
            'CA_Preferred_Email'        => 'abc@def.com',
            'merchant_name'             => 'test merchant',
            'merchant_email'            => 'test@test.com',
            'merchant_phone'            => '929292929',
            'constitution'              => 'PRIVATE_LIMITED',
            'pincode'                   => '332332',
            'sales_team'                => 'SELF_SERVE',
            'account_manager_name'      => 'test_name',
            'account_manager_email'     => 'testemail@test.com',
            'account_manager_phone'     => '33332222',
            'banking_account_application_type'   => 'ICICI_VIDEO_KYC_APPLICATION'
        ];

        $diagMock = $this->createAndReturnDiagMock();

        $diagMock->shouldReceive('trackOnboardingEvent')
            ->once()
            ->withArgs(function($eventData, $merchant, $ex, $actualData) use ($expectedPayload) {
                $this->assertEquals($expectedPayload, $actualData);
                $this->assertEquals(EventCode::X_CA_ONBOARDING_FRESHDESK_TICKET_CREATE_ICICI, $eventData);
                return true;
            })
            ->andReturnNull();

        Mail::fake();

        $this->startTest();

        Mail::assertQueued(CurrentAccount::class, function ($mail) {
            $mail->build();
            return $mail->subject === 'RazorpayX | Current Account [10000000000000 | test merchant] ICICI Video KYC';
        });
    }

    public function testSendRblApplicationInProgressLeadsToSalesForce()
    {
        $this->ba->cronAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'id'                    => 'randomBaAccId8',
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baad1 = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
        ]);

        $ba2 = $this->fixtures->create('banking_account', [
            'id'                    => 'randomBaAccId7',
            'account_number'        => '47839346831',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000001',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baad2 = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba2->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'declaration_step'          => 1,
        ]);

        $this->fixtures->edit('banking_account_activation_detail',
                              $baad1->getId(),
                              [
                                  'created_at' => Carbon::now()->subHours(6)->getTimestamp(),
                              ]);

        $this->fixtures->edit('banking_account_activation_detail',
                              $baad2->getId(),
                              [
                                  'created_at' => Carbon::now()->addDays(5)->getTimestamp(),
                              ]);

        //only one application since $baad2 application is fully submitted
        $this->mockSalesForce('sendCaLeadDetails', 1);

        $this->startTest();

        $baad1 = $this->getDbEntity('banking_account_activation_detail',
                                      [
                                          'id'    => $baad1->getId(),
                                      ]);

        $this->assertEquals('sme', $baad1->getSalesTeam());
    }

    public function testSendRblCreatedLeadsFilledNotSubmittedWithin24hrs()
    {
        $this->ba->cronAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'id'                    => 'randomBaAccId8',
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baad1 = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
        ]);

        $this->fixtures->edit('banking_account_activation_detail',
                              $baad1->getId(),
                              [
                                  'created_at' => Carbon::now()->subHours(3)->getTimestamp(),
                              ]);

        //only one application since $baad2 application is fully submitted
        $this->mockSalesForce('sendCaLeadDetails', 0);

        $this->startTest();

        $baad1 = $this->getDbEntity('banking_account_activation_detail',
                                    [
                                        'id'    => $baad1->getId(),
                                    ]);

        $this->assertEquals(Validator::SELF_SERVE, $baad1->getSalesTeam());
    }


    public function testSendRblCreatedLeadsFilledNotSubmittedWithin24hrsForNitro()
    {
        $this->ba->cronAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'id'                    => 'randomBaAccId8',
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => 'created',
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baad1 = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SELF_SERVE,
        ]);

        $this->fixtures->create('merchant_attribute', [
           'merchant_id'    =>  '10000000000000',
           'type'           =>  'ca_onboarding_flow',
           'value'          =>  'NITRO',
           'group'          =>  'x_merchant_current_accounts',
           'product'        =>  'banking',
        ]);

        $this->fixtures->create('merchant_attribute', [
            'merchant_id'    =>  '10000000000000',
            'type'           =>  'ca_campaign_id',
            'value'          =>  'RZPCA2233',
            'group'          =>  'x_merchant_current_accounts',
            'product'        =>  'banking',
        ]);

        $this->fixtures->edit('banking_account_activation_detail',
                              $baad1->getId(),
                              [
                                  'created_at' => Carbon::now()->subHours(35)->getTimestamp(),
                              ]);

        //only one application since $baad2 application is fully submitted
        $this->mockSalesForce('sendCaLeadDetails', 1);

        $this->startTest();

        $baad1 = $this->getDbEntity('banking_account_activation_detail',
                                    [
                                        'id'    => $baad1->getId(),
                                    ]);

        $this->assertEquals(Validator::SME, $baad1->getSalesTeam());
    }

    //application other than created state
    public function testSendRblApplicationLeadsToSalesForce()
    {
        $this->ba->cronAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'id'                    => 'randomBaAccId8',
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => Status::PICKED,
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baad1 = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
        ]);

        $this->fixtures->edit('banking_account_activation_detail',
                              $baad1->getId(),
                              [
                                  'created_at' => Carbon::now()->subDays(3)->getTimestamp(),
                              ]);

        $this->mockSalesForce('sendCaLeadDetails', 0);

        $this->startTest();

        $baad1 = $this->getDbEntity('banking_account_activation_detail',
                                    [
                                        'id'    => $baad1->getId(),
                                    ]);

        $this->assertNull($baad1->getSalesTeam());
    }

    public function testSendRblApplicationLeadsHavingXSMEStateToSalesForce()
    {
        $this->ba->cronAuth();

        $merchantDetailArray = [
            'contact_name'               => 'rzp',
            'contact_email'              => 'test@rzp.com',
            'merchant_id'                => '10000000000000',
            'business_operation_address' => 'Koramangala',
            'business_operation_state'   => 'KARNATAKA',
            'business_operation_pin'     => 560034,
            'business_dba'               => 'test',
            'business_name'              => 'INTERNET BANKING CA',
            'business_operation_city'    => 'Bangalore',
            'activation_status'          => 'activated'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailArray);

        $ba1 = $this->fixtures->create('banking_account', [
            'id'                    => 'randomBaAccId8',
            'account_number'        => '567890123',
            'account_type'          => 'current',
            'merchant_id'           => '10000000000000',
            'channel'               => 'rbl',
            'status'                => Status::CREATED,
            'pincode'               => '1',
            'bank_reference_number' => '',
            'account_ifsc'          => 'RATN0000156',
        ]);

        $baad1 = $this->fixtures->create('banking_account_activation_detail', [
            'banking_account_id'        => $ba1->getId(),
            'merchant_poc_email'        => 'rzp@gmail.com',
            'merchant_poc_phone_number' => '9177278079',
            'sales_team'                => Validator::SME,
        ]);

        $this->fixtures->edit('banking_account_activation_detail',
                              $baad1->getId(),
                              [
                                  'created_at' => Carbon::now()->addDays(3)->getTimestamp(),
                              ]);

        $this->mockSalesForce('sendCaLeadDetails', 0);

        $this->startTest();

        $baad1 = $this->getDbEntity('banking_account_activation_detail',
                                    [
                                        'id'    => $baad1->getId(),
                                    ]);

        $this->assertEquals(Validator::SME, $baad1->getSalesTeam());
    }

    public function testTokenizeValueViaVault()
    {
        $this->ba->bankingAccountServiceAppAuth();

        $this->mockCardVault(function ()
        {
            return [
                'success' => true,
                'token'   => 'dummy-token'
            ];
        });

        $this->startTest();
    }

    public function testBasNotifyXProActivation()
    {
        Mail::fake();

        $this->ba->bankingAccountServiceAppAuth();

        $this->mockSalesForce('sendLeadStatusUpdate', 2);

        $this->startTest();

        Mail::hasQueued(XProActivation::class);

    }

    public function testBasNotifyStatusChange()
    {

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'business_name' => 'Foo',
        ]);

        $this->ba->bankingAccountServiceAppAuth();

        $allStatuses = Status::getAll();

        foreach ($allStatuses as $status)
        {
            Mail::fake();

            $testData=$this->testData[__FUNCTION__];

            $testData['request']['content'][0]['banking_account']['status'] = $status;

            $this->mockSalesForce('sendLeadStatusUpdate', 1);

            $fnName = __FUNCTION__;
            print "Starting $fnName with status: $status\n";

            $this->startTest($testData);

            $this->assertNotificationsForStatusChange($testData['request']['content'][0]['banking_account'],$status);
        }
    }

    public function testBasNotifySubStatusChange()
    {

        $this->fixtures->create('merchant_detail', [
            'merchant_id'   => '10000000000000',
            'business_name' => 'Foo',
        ]);

        $this->ba->bankingAccountServiceAppAuth();

        $allStatuses = Status::getAll();

        $statusToSubStatusMap = Status::getAllStatusToSubStatusMap();

        $contactVerifiedValues = [0,1];

        foreach ($contactVerifiedValues as $contactVerifiedValue)
        {
            $testData=$this->testData[__FUNCTION__];

            $testData['request']['content'][0]['banking_account']['banking_account_activation_details']
                ['contact_verified'] = $contactVerifiedValue;

            foreach ($allStatuses as $status)
            {

                $testData['request']['content'][0]['banking_account']['status'] = $status;

                $allSubStatuses = $statusToSubStatusMap[$status];

                array_push($allSubStatuses,null);

                foreach ($allSubStatuses as $subStatus)
                {
                    Mail::fake();

                    $testData['request']['content'][0]['banking_account']['sub_status'] = $subStatus;

                    $sfCount = $contactVerifiedValue === 0 ? 1 : 0;

                    $this->mockSalesForce('sendLeadStatusUpdate', $sfCount);

                    $fnName = __FUNCTION__;
                    print "Starting $fnName with sub_status: $subStatus\n";

                    $this->startTest($testData);

                    if($subStatus === Status::MERCHANT_NOT_AVAILABLE && $contactVerifiedValue === 0)
                    {
                        Mail::assertQueued(MerchantNotAvailable::class);
                    } else {
                        Mail::assertNothingQueued();
                    }

                }
            }
        }
    }

    public function mockSalesForce(string $method, int $count)
    {
        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
                                     ->setConstructorArgs([$this->app])
                                     ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        $salesforceClientMock->expects($this->exactly($count))->method($method);
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

    public function testOnboardCapitalCorpCardForPayouts()
    {
        $this->ba->capitalCardsClientAppAuth();
        $response = $this->startTest();

        $balance  = $this->getDbEntity('balance',
                                       [
                                           'merchant_id'    => '10000000000000',
                                           'channel'        => 'm2p',
                                           'account_type'   => 'corp_card',
                                           'account_number' => '30091673424181',
                                       ], 'live');

        $this->assertNotNull($balance);
        $this->assertEquals($balance->getId(), $response['balance_id']);

        $bankingAccounts = $this->getDbEntity('banking_account',
                                              [
                                                  'merchant_id'    => '10000000000000',
                                                  'channel'        => 'm2p',
                                                  'account_type'   => 'corp_card',
                                                  'account_number' => '30091673424181',
                                                  'balance_id'     => $response['balance_id']
                                              ], 'live');

        $this->assertNotNull($bankingAccounts);

        return $balance;
    }

    public function testDuplicateOnboardCapitalCorpCardForPayouts()
    {
        $balance  = $this->testOnboardCapitalCorpCardForPayouts();
        $response = $this->startTest();

        $this->assertNotNull($response);
        $this->assertEquals($balance->getId(), $response['balance_id']);
    }

    public function testInvalidMerchantIdOnboardCapitalCorpCardForPayouts()
    {
        $this->ba->capitalCardsClientAppAuth();
        $this->expectException(DbQueryException::class);
        $this->startTest();
    }

    public function testNot14CharMerchantIdOnboardCapitalCorpCardForPayouts()
    {
        $this->ba->capitalCardsClientAppAuth();

        $this->startTest();
    }

    public function testNot14CharAccountNumberOnboardCapitalCorpCardForPayouts()
    {
        $this->ba->capitalCardsClientAppAuth();

        $this->startTest();
    }


    private function assertNotificationsForStatusChange(array $bankingAccount, string $status)
    {
        switch ($status)
        {
            case Status::API_ONBOARDING:
            case Status::ACCOUNT_ACTIVATION:
            case Status::PROCESSED:
                $body = sprintf('This is to notify that Current Account for Merchant Foo has been %s',
                    studly_case(Status::transformFromInternalToExternal($status)));
                $subject = sprintf('RazorpayX LMS | Foo\'s CA has been %s',
                    studly_case(Status::transformFromInternalToExternal($status)));
                $this->assertStatusChangeNotificationEmail($bankingAccount, $body, $subject,2,true);

                break;
            case Status::REJECTED:
            case Status::ARCHIVED:
            case Status::ACTIVATED:
                $body = sprintf('This is to notify that Current Account for Merchant Foo has been %s',
                    studly_case(Status::transformFromInternalToExternal($status)));
                $subject = sprintf('RazorpayX LMS | Foo\'s CA has been %s',
                    studly_case(Status::transformFromInternalToExternal($status)));
                $this->assertStatusChangeNotificationEmail($bankingAccount, $body, $subject,1,false);

                break;

            default:
                Mail::assertNothingQueued();
        }
    }

    private function assertStatusChangeNotificationEmail(array $bankingAccount, string $body, string $subject, int $expectedCount, bool $opsNotify)
    {
        Mail::assertQueued(StatusChange::class,$expectedCount);

        $containsSpocEmail = false;
        $containsReviewerEmail = false;
        for ($i = 0; $i < $expectedCount; $i++)
        {

            Mail::assertQueued(StatusChange::class, function ($mail) use ($bankingAccount,&$containsSpocEmail,
                &$containsReviewerEmail,$body,$subject)
            {
                $mail->build();
                $this->assertArraySelectiveEquals([
                    'admin_dashboard_link'      => 'https://dashboard.razorpay.com/admin#/app/banking-accounts/bacc_10000000000000',
                    'body'                      => $body,
                    'internal_reference_number' => $bankingAccount[Entity::BANK_REFERENCE_NUMBER],
                    'merchant_id'               => $bankingAccount[Entity::MERCHANT_ID]
                ],$mail->viewData);
                $mail->hasSubject($subject);
                $containsReviewerEmail = $containsReviewerEmail || $mail->hasTo($bankingAccount[Entity::REVIEWERS][0]['email']);
                $containsSpocEmail = $containsSpocEmail || $mail->hasTo($bankingAccount[Entity::SPOCS][0]['email']);
                return true;
            });
        }
        $this->assertTrue($containsSpocEmail);
        if($opsNotify)
        {
            $this->assertTrue($containsReviewerEmail);
        }
    }
}
