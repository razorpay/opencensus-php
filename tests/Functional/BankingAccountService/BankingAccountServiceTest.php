<?php

namespace RZP\Tests\Functional\BankingAccountService;

use App;
use Carbon\Carbon;

use RZP\Models\Schedule;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Models\BankingAccountService\Constants;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Tests\Functional\Helpers\TestsBusinessBanking;

class BankingAccountServiceTest extends TestCase
{
    use DbEntityFetchTrait;
    use TestsBusinessBanking;
    use RequestResponseFlowTrait;

    protected $config;

    public function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BankingAccountServiceTestData.php';

        parent::setUp();

        $this->ba->bankingAccountServiceAppAuth();

        $this->app['config']->set('applications.banking_account_service.mock', true);

        $this->config = App::getFacadeRoot()['config'];
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
                                          'bas_business_id'  => '10000000000011',
                                      ]);

        $this->assertNotNull($merchantDetail);
    }

    public function testCron()
    {
        $this->ba->cronAuth();

        $response = $this->startTest();

        $this->assertEquals('ACTIVE', $response['data']['status']);
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
}
