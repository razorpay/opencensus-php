<?php

namespace RZP\Tests\Functional\BankingAccountService;

use App;
use Carbon\Carbon;

use RZP\Error\ErrorCode;
use RZP\Services\SalesForceClient;
use RZP\Models\BankingAccount\Status;
use RZP\Exception\BadRequestException;
use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccountService\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;
use RZP\Models\BankingAccount\Activation\Detail\Validator;

class BankingAccountServiceTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
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

    public function testCreateBankingEntities()
    {
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

        $basd = $this->getDbEntity('banking_account_statement_details',
                                       [
                                           'merchant_id'    => '10000000000000',
                                           'channel'        => 'icici',
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

        $merchant = $this->getDbEntity('merchant',
            [
                'id'    => '10000000000000',
            ]);

        $this->assertEquals(0, $merchant['has_key_access']);

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

        $merchant = $this->getDbEntity('merchant',
            [
                'id'    => '10000000000000',
            ]);

        $this->assertEquals(1, $merchant['has_key_access']);

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

    public function testPinCodeServiceabilityForIcici()
    {
        $this->ba->proxyAuth();

        $response = $this->startTest();

        $this->assertEquals(true, $response['data']['serviceable']);
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

    public function testSendCaLeadToSalesForce()
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

        $this->mockSalesForce('sendCaLeadDetails', 1);

        $this->startTest();
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
                                  'created_at' => Carbon::now()->subDays(2)->getTimestamp(),
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
                                  'created_at' => Carbon::now()->subHours(5)->getTimestamp(),
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

    public function mockSalesForce(string $method, int $count)
    {
        $salesforceClientMock = $this->getMockBuilder(SalesForceClient::class)
                                     ->setConstructorArgs([$this->app])
                                     ->getMock();

        $this->app->instance('salesforce', $salesforceClientMock);

        $salesforceClientMock->expects($this->exactly($count))->method($method);
    }
}
