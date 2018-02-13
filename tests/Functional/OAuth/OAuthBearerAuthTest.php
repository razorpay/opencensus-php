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
     *      the application hits the route on behalf of the merchant, and,
     *      the app has the feature enabled
     *      (and the feature is not an oauth application blacklisted feature)
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
     *      the application hits the route on behalf of the merchant, and,
     *      the merchant has the feature enabled, and,
     *      the feature is an oauth application blacklisted feature
     */
    public function testBearerAuthBlacklistedOAuthFeatureWithMerchant()
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
