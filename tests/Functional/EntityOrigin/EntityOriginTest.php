<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Base\PublicEntity;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

class EntityOriginTest extends TestCase
{
    use PaymentTrait;
    use PartnerTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/EntityOriginTestData.php';

        parent::setUp();


    }

    /**
     * Asserts that the origin entity is created for a payment initiated using the merchant key.
     */
    public function testCreatePaymentOriginMerchantKey()
    {
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndGetPayment($payment);

        $origin = $this->getDbLastEntity('entity_origin');

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $expectedOrigin = $this->testData[__FUNCTION__]['response']['content'];

        $expectedOrigin['entity_id'] = PublicEntity::stripDefaultSign($payment['id']);

        $this->assertArraySelectiveEquals($expectedOrigin, $origin);
    }

    /**
     * Asserts that the origin entity is created for a payment initiated using the partner credentials.
     */
    public function testCreatePaymentOriginPartnerKey()
    {
        $payment = $this->getDefaultPaymentArray();

        $partnerId = '100000Razorpay';
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);

        $submerchantId = '10000000000000';

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => $submerchantId,
            ]
        );

        $response = $this->doPartnerAuthPayment($payment, $client->getId(), $submerchantId);

        $app = DB::Connection('auth')
                 ->table('applications')
                 ->orderBy('created_at', 'desc')
                 ->first();

        $origin = $this->getDbLastEntity('entity_origin');

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $expectedOrigin = $this->testData[__FUNCTION__]['response']['content'];

        $expectedOrigin['entity_id'] = PublicEntity::stripDefaultSign($response['razorpay_payment_id']);

        $expectedOrigin['origin_id'] = $app->id;

        $this->assertArraySelectiveEquals($expectedOrigin, $origin);
    }

    /**
     * Asserts that the origin entity is created for a payment initiated using the OAuth public token.
     */
    public function testCreatePaymentOriginOauthPublicToken()
    {
        $this->generateOAuthAccessToken(['public_token' => 'TheTestAuthKey', 'scopes' => ['read_write']]);

        $this->ba->oauthPublicTokenAuth();

        $payment = $this->getDefaultPaymentArray();

        $response = $this->doAuthPaymentOAuth($payment);

        $app = DB::Connection('auth')
                 ->table('applications')
                 ->orderBy('created_at', 'desc')
                 ->first();

        $origin = $this->getDbLastEntity('entity_origin');

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $expectedOrigin = $this->testData[__FUNCTION__]['response']['content'];

        $expectedOrigin['entity_id'] = PublicEntity::stripDefaultSign($response['razorpay_payment_id']);

        $expectedOrigin['origin_id'] = $app->id;

        $this->assertArraySelectiveEquals($expectedOrigin, $origin);
    }

    /**
     * Asserts that the origin entity is created for a payment initiated using the private auth.
     */
    public function testCreatePaymentOriginPrivateAuth()
    {
        $this->mockCardVault();

        $merchantId = '10000000000000';

        $this->ba->privateAuth();

        $payment = $this->getDefaultPaymentArray();

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_json']);

        $response = $this->doS2SPrivateAuthJsonPayment($payment);

        $origin = $this->getDbLastEntity('entity_origin');

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $expectedOrigin = $this->testData[__FUNCTION__]['response']['content'];

        $expectedOrigin['entity_id'] = PublicEntity::stripDefaultSign($response['razorpay_payment_id']);

        $expectedOrigin['origin_id'] = $merchantId;

        $this->assertArraySelectiveEquals($expectedOrigin, $origin);
    }

    public function testCreateOriginByInternalApp()
    {
        $this->ba->subscriptionsAuth();

        $payment = $this->fixtures->create('payment:authorized');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['entity_id']  = $payment->getPublicId();
        $testData['response']['content']['entity_id'] = $payment->getId();

        $this->startTest($testData);
    }

    public function testCreateApplicationOriginByInternalApp()
    {
        $this->ba->subscriptionsAuth();

        $client = $this->setUpPartnerMerchantAppAndGetClient();

        $payment = $this->fixtures->create('payment:authorized');

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['entity_id']  = $payment->getPublicId();
        $testData['request']['content']['origin_id']  = $client->getApplicationId();
        $testData['response']['content']['entity_id'] = $payment->getId();
        $testData['response']['content']['origin_id'] = $client->getApplicationId();

        $this->startTest($testData);
    }

    public function testCreateOriginInvalidIdByInternalApp()
    {
        $this->ba->subscriptionsAuth();

        $this->startTest();
    }
}
