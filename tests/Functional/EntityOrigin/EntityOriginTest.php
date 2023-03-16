<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Illuminate\Database\Eloquent\Factory;

use RZP\Models\Base\PublicEntity;
use RZP\Models\EntityOrigin;
use RZP\Models\Order\Entity as OrderEntity;
use RZP\Models\Payment\Entity as PaymentEntity;
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
        $this->markTestSkipped('Entity origin for merchant auth has been removed');

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
     * Asserts that the origin entity is created for a payment with public key
     */
    public function testEntityOriginCreateFromPaymentPublicKey()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');
        $partnerId = '100000Razorpay';
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', [], $partnerId);
        $payment = $this->getDefaultPaymentArray();

        $payment = $this->doAuthAndGetPayment($payment);

        $paymentEntity = new PaymentEntity($payment);
        $paymentEntity->setPublicKey('rzp_test_partner_'.$client->getId());

        (new EntityOrigin\Core)->createEntityOrigin($paymentEntity);

        $origin = $this->getDbLastEntity('entity_origin');
        $app = DB::Connection('auth')
                 ->table('applications')
                 ->orderBy('created_at', 'desc')
                 ->first();

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $this->assertEquals(substr($payment['id'], -14), $origin['entity_id']);
         $this->assertEquals($app->id, $origin['origin_id']);
    }

    /**
     * Asserts that the origin entity is created for a order with oauth key
     */
    public function testEntityOriginCreateFromOrderOauthKey()
    {
        $this->app['basicauth']->setModeAndDbConnection('test');
        $this->generateOAuthAccessToken(['public_token' => 'TheTestAuthKey', 'scopes' => ['read_write']]);
        $payment = $this->getDefaultPaymentArray();
        $order = [
          "id"=> "order_EKwxwAgItmmXdp",
          "entity" => "order",
          "amount" => 50000,
          "amount_paid"=> 0,
          "amount_due"=> 50000,
          "currency"=> "INR"
        ];


        $payment = $this->doAuthAndGetPayment($payment);

        // creating order with public key and associating to payment
        $paymentEntity = new PaymentEntity($payment);
        $orderEntity = new OrderEntity($order);
        $orderEntity->setPublicKey('rzp_test_oauth_TheTestAuthKey');
        $paymentEntity->order()->associate($orderEntity);
        $paymentEntity->setPublicKey(null);

        (new EntityOrigin\Core)->createEntityOrigin($paymentEntity);
        $origin = $this->getDbLastEntity('entity_origin');
        $app = DB::Connection('auth')
                 ->table('applications')
                 ->orderBy('created_at', 'desc')
                 ->first();

        $this->assertNotNull($origin);

        $origin = $origin->toArray();

        $this->assertEquals(substr($payment['id'], -14), $origin['entity_id']);
        $this->assertEquals($app->id, $origin['origin_id']);
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
        $this->markTestSkipped('Entity origin for merchant auth has been removed');
        
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

    public function testCreateApplicationOriginByInternalAppForPaymentLink()
    {
        $this->ba->proxyAuth();

        $client = $this->setUpPartnerMerchantAppAndGetClient();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['origin_id']  = $client->getApplicationId();
        $testData['response']['content']['origin_id'] = $client->getApplicationId();

        $this->startTest($testData);
    }

    public function testCreateMerchantOriginByInternalAppForPaymentLink()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient();

        $this->ba->proxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content']['origin_id']  = $client->application->getMerchantId();
        $testData['response']['content']['origin_id'] = $client->application->getMerchantId();

        $this->startTest($testData);
    }

    public function testCreateOriginInvalidIdByInternalApp()
    {
        $this->ba->subscriptionsAuth();

        $this->startTest();
    }
}
