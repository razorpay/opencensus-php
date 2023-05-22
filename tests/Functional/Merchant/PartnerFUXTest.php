<?php


namespace Functional\Merchant;


use Carbon\Carbon;
use RZP\Constants\Timezone;
use Razorpay\OAuth\Application;
use RZP\Tests\Traits\MocksSplitz;
use RZP\Tests\Functional\OAuth\OAuthTestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Traits\MocksPartnershipsService;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class PartnerFUXTest extends OAuthTestCase
{
    use PartnerTrait;

    use RequestResponseFlowTrait;
    use MocksPartnershipsService;
    use MocksSplitz;

    const DEFAULT_MERCHANT_ID    = '10000000000000';
    const DEFAULT_SUBMERCHANT_ID = '10000000000009';

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PartnerFUXTestData.php';

        parent::setUp();

        $this->authServiceMock = $this->createAuthServiceMock(['sendRequest']);
    }

    public function testPartnerFUXDetailsAfterSignUp()
    {
        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $this->createResellerPartner();

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPartnerFUXDetailsWithPartnerMigrationFlagDisabled()
    {
        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>['partner_id'=> '']]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $this->createResellerPartner();

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPartnerFUXDetailsWithPartnershipServiceError()
    {
        $expectedResponse = ['status_code'=> 401, 'response'=> ['error'=>[]]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $this->createResellerPartner();

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPartnerFUXDetailsAfterSubmerchantsAreAdded()
    {
        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $this->createResellerPartnerSubmerchant(['email' => 'testing@example.com']);

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPartnerFUXDetailsAfterSubmerchantsAreLive()
    {
        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $this->createResellerPartnerSubmerchant(
            [
                'email' => 'testing@example.com',
                'live'         => true
            ]
        );

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testAggregatorPartnerFUXDetailsWhenIntegratedWithApi()
    {
        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], '10000000000000', 'aggregator');

        $payment = $this->fixtures->create('payment:authorized');

        $this->fixtures->create(
            'entity_origin',
            [
                'entity_type'     => 'payment',
                'entity_id'       => $payment->getId(),
                'origin_type'     => 'application',
                'origin_id'       => $client->getApplicationId(),
            ]
        );

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testFullyManagedPartnerFUXDetailsWhenIntegratedWithApi()
    {
        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $client = $this->setUpPartnerMerchantAppAndGetClient();

        $payment = $this->fixtures->create('payment:authorized');

        $this->fixtures->create(
            'entity_origin',
            [
                'entity_type'     => 'payment',
                'entity_id'       => $payment->getId(),
                'origin_type'     => 'application',
                'origin_id'       => $client->getApplicationId(),
            ]
        );

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testPurePlatformPartnerFUXDetailsWhenIntegratedWithApi()
    {
        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id'=>'10000000000000'];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], '10000000000000', 'pure_platform');

        $payment = $this->fixtures->create('payment:authorized');

        $this->fixtures->create(
            'entity_origin',
            [
                'entity_type'     => 'payment',
                'entity_id'       => $payment->getId(),
                'origin_type'     => 'application',
                'origin_id'       => $client->getApplicationId(),
            ]
        );

        $this->ba->proxyAuth();

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testResellerPartnerFUXDetailsAfterEarningsAreGenerated()
    {
        list($partner, $config, $payment) = $this->createPartnerWithPaymentBySubmerchant(
            ['partner_type' => 'reseller'], ['live' => true]
        );

        $commissionAttributes = [
            'source_id'         => $payment->getId(),
            'partner_id'        => $partner->getId(),
            'partner_config_id' => $config->getId()
        ];

        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id' => $partner->getId()];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();

        $this->fixtures->create('commission:commission_and_sync_es', $commissionAttributes);

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function testAggregatorPartnerFUXDetailsAfterEarningsAreGenerated()
    {
        list($partner, $config, $payment) = $this->createPartnerWithPaymentBySubmerchant([], ['live' => true]);

        $commissionAttributes = [
            'source_id'         => $payment->getId(),
            'partner_id'        => $partner->getId(),
            'partner_config_id' => $config->getId()
        ];

        $expectedResponse = ['status_code'=> 200, 'response'=> ['partner_migration'=>[]]];

        $input = ['partner_id' => $partner->getId()];

        $this->mockPartnershipsServiceTreatment($input, $expectedResponse, 'getLastPartnerMigration');

        $this->mockAllSplitzTreatment();
        
        $this->fixtures->create('commission:commission_and_sync_es', $commissionAttributes);

        $this->ba->proxyAuth('rzp_test_' . $partner->getId());

        $this->runRequestResponseFlow($this->testData[__FUNCTION__]);
    }

    public function createResellerPartnerSubmerchant($subMerchantAttributes = [])
    {
        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->fixtures->merchant->create(['id' => self::DEFAULT_SUBMERCHANT_ID]);

        $this->fixtures->merchant_detail->create(['merchant_id' => self::DEFAULT_SUBMERCHANT_ID]);

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => 'reseller',
            'email'        => 'test@example.com',
        ]);

        $this->fixtures->merchant->edit(self::DEFAULT_SUBMERCHANT_ID, $subMerchantAttributes);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $app = Application\Entity::factory()->create([
            'id' => random_integer(10),
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'type' => 'partner'
        ]);

        $this->createMerchantApplication($app->merchant_id, 'reseller', $app->getId());

        $this->fixtures->create('merchant_detail:sane',[
            'merchant_id' => $app->merchant_id,
            'contact_name'=> 'randomName',
            'business_type' => 2
        ]);

        $this->fixtures->on('test')->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2]);
        $this->fixtures->on('live')->edit('merchant_detail', self::DEFAULT_SUBMERCHANT_ID, ['business_type' => 2]);

        $this->fixtures->on('test')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);
        $this->fixtures->on('live')->edit('merchant', self::DEFAULT_SUBMERCHANT_ID, ['name' => 'submerchant']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'id'          => 'IBb9OU2WPuCC29',
                'entity_type' => 'application',
                'entity_id'   => $app->getId(),
                'merchant_id' => self::DEFAULT_SUBMERCHANT_ID,
            ]
        );
    }

    public function createResellerPartner()
    {
        $merchantId = self::DEFAULT_MERCHANT_ID;

        $this->fixtures->merchant->edit(self::DEFAULT_MERCHANT_ID, [
            'partner_type' => 'reseller',
            'email'        => 'test@example.com',
        ]);

        $this->ba->proxyAuth('rzp_test_' . $merchantId);

        $app = Application\Entity::factory()->create([
            'id' => random_integer(10),
            'merchant_id' => self::DEFAULT_MERCHANT_ID,
            'type' => 'partner'
        ]);

        $this->createMerchantApplication($app->merchant_id, 'reseller', $app->getId());

        $this->fixtures->create('merchant_detail:sane',[
            'merchant_id' => $app->merchant_id,
            'contact_name'=> 'randomName',
            'business_type' => 2
        ]);
    }

    public function createPartnerWithPaymentBySubmerchant($partnerAttributes = [], $subMerchantAttributes = [])
    {
        list($partner, $app) = $this->createPartnerAndApplication($partnerAttributes);

        $config = $this->createConfigForPartnerApp($app->getId());

        list($subMerchant) = $this->createSubMerchant($partner, $app, $subMerchantAttributes);

        $dt = Carbon::today(Timezone::IST)->subDays(50);

        $createdAt = $dt->timestamp + 5;
        $capturedAt = $dt->timestamp + 10;

        $attrs = [
            'captured_at' => $capturedAt,
            'method'      => 'card',
            'amount'      => 1000000,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt + 10,
            'merchant_id' => $subMerchant->getId()
        ];

        $payment = $this->fixtures->create(
            'payment:captured',
            $attrs
        );

        return [$partner, $config, $payment];
    }
}
