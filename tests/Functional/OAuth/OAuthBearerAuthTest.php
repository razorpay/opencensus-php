<?php

namespace RZP\Tests\Functional\OAuth;

use Carbon\Carbon;
use Razorpay\OAuth\Client;

use RZP\Constants\Timezone;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;

/**
 * @group dns-sensitive
 */
class OAuthBearerAuthTest extends OAuthTestCase
{
    use OAuthTrait;
    use PaymentTrait;
    use MocksDnsTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OAuthBearerAuthTestData.php';

        $this->setupMockDns();

        parent::setUp();
    }

    public function testBearerAuth()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testBearerAuthProdClient()
    {
        $accessToken = $this->generateOAuthAccessToken([], 'prod');

        $this->ba->oauthBearerAuth($accessToken);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testBearerAuthDummyRouteScope()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'dummy'
            ]);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthDummyRouteScopeFail()
    {
        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'dummy'
            ]);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    /**
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled
     *      the merchant has the feature enabled
     *      (and the feature is not an oauth app blacklisted feature)
     */
    public function testDummyFeatureEnabledOnMerchantAndApp()
    {
        $client = factory(Client\Entity::class)->create();

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name' => 'dummy'
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name' => 'dummy'
            ]);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    /**
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled
     *      the merchant does not have the feature enabled
     *      (and the feature is not an oauth app blacklisted feature)
     */
    public function testBearerAuthAllowAppFeaturesRouteAccess()
    {
        $client = factory(Client\Entity::class)->create();

        $accessToken = $this->generateOAuthAccessToken(
                            [
                                'scopes' => ['dummy.read'],
                                'client_id' => $client->getId()
                            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 'dummy'
            ]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    /**
     * Tests that the route (feature route) is not accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app does not have the feature enabled, and,
     *      the merchant has the feature enabled, and,
     *      the feature is an oauth app blacklisted feature
     */
    public function testAppBlacklistedFeatureEnabledOnMerchant()
    {
        $client = factory(Client\Entity::class)->create();

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes' => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'name'        => 's2s'
            ]);

        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest($testData);
    }

    /**
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled, and,
     *      the merchant does not have the feature enabled, and,
     *      the feature is an oauth app blacklisted feature
     */
    public function testAppBlacklistedFeatureEnabledOnApp()
    {
        $client = factory(Client\Entity::class)->create();

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes' => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 's2s'
            ]);

        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken);

        $response = $this->startTest($testData);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    /**
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled, and,
     *      the merchant has the feature enabled, and,
     *      the feature is an oauth app blacklisted feature
     */
    public function testAppBlacklistedFeatureEnabledOnAppAndMerchant()
    {
        $client = factory(Client\Entity::class)->create();

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes' => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 's2s'
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'name'        => 's2s'
            ]);

        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken);

        $response = $this->startTest($testData);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    /**
     * Tests that the route (feature route) is not accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app does not have the feature enabled, and,
     *      the merchant does not have the feature enabled, and,
     *      the feature is not an oauth app blacklisted feature
     */
    public function testFeatureDisabledOnAppAndMerchant()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthWriteAccess()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthWriteAccessReadRoute()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testBearerAuthOutsideOfScope()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthWithTamperedToken()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken . 'bad');

        $this->startTest();
    }

    public function testBearerAuthWithTamperedJWTPayload()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);

        $accessToken = $this->tamperExpiryOfAccessToken($accessToken);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthExpiredToken()
    {
        $pastExpiry = Carbon::today(Timezone::IST)->subDays(10)->timestamp;

        $accessToken = $this->generateOAuthAccessToken(['expires_at' => $pastExpiry]);

        $this->ba->oauthBearerAuth($accessToken);

        $this->startTest();
    }

    public function testBearerAuthLiveModeInActiveMerchant()
    {
        $accessToken = $this->generateOAuthAccessToken(['mode' => 'live']);

        $this->ba->oauthBearerAuth($accessToken);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }
}
