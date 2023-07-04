<?php

namespace RZP\Tests\Functional\OAuth;

use Event;
use Mockery;
use Carbon\Carbon;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use Razorpay\Edge\Passport\Passport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\Events\CacheHit;
use Razorpay\Edge\Passport\OAuthClaims;
use Illuminate\Cache\Events\CacheMissed;

use RZP\Models\Feature;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use Razorpay\Edge\Passport\ConsumerClaims;
use Razorpay\Edge\Passport\CredentialClaims;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Traits\TestsStorkServiceRequests;
use Razorpay\Edge\Passport\ImpersonationClaims;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\VirtualAccount\VirtualAccountTrait;
use RZP\Http\OAuthCache;
use Illuminate\Cache\Events\KeyWritten;

class OAuthBearerAuthTest extends OAuthTestCase
{
    use OAuthCache;
    use PartnerTrait;
    use PaymentTrait;
    use TestsStorkServiceRequests;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/OAuthBearerAuthTestData.php';

        parent::setUp();

        $this->mockCardVault();
    }

    public function testBearerAuth()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();

        $this->assertPassport();
        $this->assertPassportKeyExists('oauth.client_id');
        $this->assertPassportKeyExists('oauth.app_id');
    }

    public function testRefreshClientCredentials()
    {
        $currentClient = $this->setUpPartnerMerchantAppAndGetClient();

        $accessToken =  $this->generateOAuthAccessTokenForClient([], $currentClient);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->ba->adminProxyAuth();
        $this->allowAdminToAccessMerchant('10000000000000');

        // create new clients
        $testData = $this->testData['testCreateClientsForApp'];
        $testData['request']['url'] = '/oauth/applications/'. $currentClient->getApplicationId() .'/clients';
        $response = $this->runRequestResponseFlow($testData);

        $this->assertCount(2, $response['old_clients']);

        $clientId = null;

        // get new client
        foreach ($response['clients'] as $client)
        {
            if (($client['environment'] === 'dev') and (in_array($client['id'], $response['old_clients']) === false))
            {
                $clientId = $client['id'];
                break;
            }
        }

        $this->assertNotNull($clientId);

        $clientRepo = new Client\Repository;
        $newClient = $clientRepo->findOrFailPublic($clientId);

        $newAccessToken =  $this->generateOAuthAccessTokenForClient([], $newClient);

        // check that both tokens still work
        $this->ba->oauthBearerAuth($accessToken->toString());
        $testData = $this->testData['testBearerAuth'];
        $this->runRequestResponseFlow($testData);

        $this->ba->oauthBearerAuth($newAccessToken->toString());
        $testData = $this->testData['testBearerAuth'];
        $this->runRequestResponseFlow($testData);

        // delete client
        $this->ba->adminProxyAuth();
        $testData = $this->testData['testDeleteClient'];
        $testData['request']['url'] = '/oauth/applications/'. $currentClient->getApplicationId() . '/clients/'. $currentClient->getId();
        $response = $this->runRequestResponseFlow($testData);
        $this->assertCount(3, $response['clients']);

        // remove the cache for the access token
        $this->flushCache();

        // now using old token should give error
        $this->ba->oauthBearerAuth($accessToken->toString());
        $testData = $this->testData['testBearerAuthDeletedClient'];
        $this->runRequestResponseFlow($testData);
    }

    public function testBearerAuthProdClient()
    {
        $accessToken = $this->generateOAuthAccessToken([], 'prod');

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();

        $this->assertPassport();
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

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();

        $this->assertPassport();
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

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();
    }

    /**
     * Please refer to the GitHub wiki page on middlewares before making any changes here.
     *
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled
     *      the merchant has the feature enabled
     *      (and the feature is not an oauth app blacklisted feature)
     */
    public function testDummyFeatureEnabledOnMerchantAndApp()
    {
        $client = Client\Entity::factory()->create();

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name'      => 'dummy'
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 'dummy'
            ]);

        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();
    }

    /**
     * Please refer to the GitHub wiki page on middlewares before making any changes here.
     *
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled
     *      the merchant does not have the feature enabled
     *      (and the feature is not an oauth app blacklisted feature)
     */
    public function testBearerAuthAllowAppFeaturesRouteAccess()
    {
        $client = Client\Entity::factory()->create();

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

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();
    }

    /**
     * Please refer to the GitHub wiki page on middlewares before making any changes here.
     *
     * Tests that the route (feature route) is not accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app does not have the feature enabled, and,
     *      the merchant has the feature enabled, and,
     *      the feature is an oauth app blacklisted feature
     */
    public function testAppBlacklistedFeatureEnabledOnMerchant()
    {
        $client = Client\Entity::factory()->create();

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->fixtures->merchant->addFeatures(['s2s']);

        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest($testData);
    }

    /**
     * Please refer to the GitHub wiki page on middlewares before making any changes here.
     *
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled, and,
     *      the merchant does not have the feature enabled, and,
     *      the feature is an oauth app blacklisted feature
     */
    public function testAppBlacklistedFeatureEnabledOnApp()
    {
        $client = Client\Entity::factory()->create();

        $this->mockCardVault();

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
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

        $this->ba->oauthBearerAuth($accessToken->toString());

        $response = $this->startTest($testData);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testAppBlacklistedFeatureEnabledOnAppHeadlessOtp()
    {
        $client = Client\Entity::factory()->create();

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

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
                'headless_otp' => '1',
            ]
        ]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['auth_type'] = 'otp';

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest($testData);
    }

    /**
     * Please refer to the GitHub wiki page on middlewares before making any changes here.
     *
     * Tests that the route (feature route) is accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app has the feature enabled, and,
     *      the merchant has the feature enabled, and,
     *      the feature is an oauth app blacklisted feature
     */
    public function testAppBlacklistedFeatureEnabledOnAppAndMerchant()
    {
        $client = Client\Entity::factory()->create();

        $this->mockCardVault();

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

        $this->fixtures->merchant->addFeatures(['s2s']);

        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken->toString());

        $response = $this->startTest($testData);

        $this->assertArrayHasKey('razorpay_payment_id', $response);
    }

    public function testAppBlacklistedFeatureEnabledOnAppHeadlessOtpGetSecretBugFix()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('On');

        $client = Client\Entity::factory()->create();

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

        $this->fixtures->merchant->addFeatures(['s2s', 's2s_otp_json']);

        $this->fixtures->create('terminal:shared_hitachi_terminal', [
            'type' => [
                'non_recurring' => '1'
            ]
        ]);

        $this->mockCardVault();
        $this->mockOtpElf();

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->fixtures->iin->create([
            'iin'     => '556763',
            'country' => 'IN',
            'issuer'  => 'ICIC',
            'network' => 'MasterCard',
            'flows'   => [
                '3ds'          => '1',
            ]
        ]);

        $order = $this->fixtures->create('order', ['id' => '100000000order', 'amount' => 50000]);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '5567630000002004';
        $payment['order_id'] = 'order_100000000order';

        $request  = [
            'url'     => '/payments/create/redirect',
            'method'  => 'POST',
            'content' => $payment,
        ];

        $this->ba->oauthBearerAuth($accessToken->toString());

        $response = $this->makeRequestParent($request);

        $targetUrl =$this->getMetaRefreshUrl($response);

        $this->assertTrue($this->isRedirectToAuthorizeUrl($targetUrl));

        $response = $this->makeRedirectToAuthorize($targetUrl);

        $content = $this->getJsonContentFromResponse($response, null);

        self::assertNotNull($content['razorpay_payment_id']);

        $payment = $this->getEntityById('payment', $content['razorpay_payment_id'], true);

        self::assertEquals(null, $payment['auth_type']);
        self::assertEquals('authorized', $payment['status']);

        $response = $this->makeRedirectToAuthorize($targetUrl);

        $content = $this->getJsonContentFromResponse($response, null);

        self::assertNotNull($content['razorpay_payment_id']);
    }

    /**
     * Please refer to the GitHub wiki page on middlewares before making any changes here.
     *
     * Tests that the route (feature route) is not accessible if
     *      the app hits the route on behalf of the merchant, and,
     *      the app does not have the feature enabled, and,
     *      the merchant does not have the feature enabled, and,
     *      the feature is not an oauth app blacklisted feature
     */
    public function testFeatureDisabledOnAppAndMerchant()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['dummy.read']]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();
    }

    public function testBearerAuthWriteAccess()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->expectStorkServiceRequestForAction('createWebhook');

        $this->startTest();
    }

    public function testBearerAuthWriteAccessReadRoute()
    {
        $accessToken = $this->generateOAuthAccessToken(['scopes' => ['read_write']]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testBearerAuthOutsideOfScope()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();
    }

    public function testBearerAuthWithTamperedToken()
    {
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken->toString() . 'bad');

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

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest();
    }

    public function testBearerAuthLiveModeInActiveMerchant()
    {
        $accessToken = $this->generateOAuthAccessToken(['mode' => 'live']);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testRestrictedAccessFeatureEnabledOnMerchantOnly()
    {
        $client = Client\Entity::factory()->create();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name'      => 'virtual_accounts'
            ]);

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testRestrictedAccessFeatureEnabledOnAppOnly()
    {
        $client = Client\Entity::factory()->create();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 'virtual_accounts'
            ]);

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    public function testRestrictedAccessFeatureEnabledOnMerchantAndApp()
    {
        $client = Client\Entity::factory()->create();

        $this->fixtures->merchant->enableMethod('10000000000000', 'bank_transfer');

        $this->fixtures->on('test')->create('terminal:shared_bank_account_terminal');

        $this->fixtures->create(
            'feature',
            [
                'entity_id' => '10000000000000',
                'name'      => 'virtual_accounts'
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 'virtual_accounts'
            ]);

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $testData = $this->testData[__FUNCTION__];

        $this->startTest($testData);
    }

    /**
     * A competitor OAuth application should not be allowed to access S2S routes on behalf of the merchant,
     * if the merchant does not have the allow_s2s_apps feature enabled.
     */
    public function testCompetitorAppAccessS2SRouteWithoutAllowS2SFeature()
    {
        $testAppId = Feature\Type::TEST_APP_ID;

        $application = Application\Entity::factory()->create([
            'id' => $testAppId,
        ]);

        $client = Client\Entity::factory()->create([
            'application_id' => $application->id,
        ]);

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 's2s'
            ]);

        $this->fixtures->merchant->addFeatures(['s2s']);

        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest($testData);
    }

    /**
     * A competitor OAuth application should be allowed to access S2S routes on behalf of the merchant,
     * if the merchant does has the allow_s2s_apps feature enabled.
     */
    public function testCompetitorAppAccessS2SRouteWithAllowS2SFeature()
    {
        $testAppId = Feature\Type::TEST_APP_ID;

        $application = Application\Entity::factory()->create([
            'id' => $testAppId,
        ]);

        $client = Client\Entity::factory()->create([
            'application_id' => $application->id,
        ]);

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->fixtures->create(
            'feature',
            [
                'entity_type' => 'application',
                'entity_id'   => $client->application_id,
                'name'        => 's2s'
            ]);

        $this->fixtures->merchant->addFeatures(['s2s']);

        $this->fixtures->merchant->addFeatures(['allow_s2s_apps']);

        $payment = $this->getDefaultPaymentArray();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'] = $payment;

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->startTest($testData);
    }

    /**
     * A competitor OAuth application should not be allowed to access a non S2S routes on behalf of the merchant.
     */
    public function testCompetitorAppAccessNonS2SRoute()
    {
        $testAppId = Feature\Type::TEST_APP_ID;

        $application = Application\Entity::factory()->create([
            'id' => $testAppId,
        ]);

        $client = Client\Entity::factory()->create([
            'application_id' => $application->id,
        ]);

        $accessToken = $this->generateOAuthAccessToken(
            [
                'scopes'    => ['read_write'],
                'client_id' => $client->getId()
            ]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();
    }

    public function testCacheHitForBearerToken()
    {
        config(['app.query_cache.mock' => false]);

        Event::fake();
        $accessToken = $this->generateOAuthAccessToken();

        $this->ba->oauthBearerAuth($accessToken->toString());

        $tokenTag = $this->getCacheTagsForToken($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->startTest();

        //
        // Asserts cache should not have been hit the first time
        //
        Event::assertDispatched(CacheMissed::class, function($e) use ($tokenTag)
        {
            foreach ($e->tags as $tag) {
                if (starts_with($tag, 'token_') and !starts_with($tag, 'token_id_'))
                {
                    $this->assertEquals($tokenTag, $tag);
                }
            }
            return true;
        });

        //
        // Asserts that key is found in cache on subsequent attempts
        //
        Event::assertDispatched(KeyWritten::class, function($e) use ($tokenTag)
        {
            foreach ($e->tags as $tag) {
                if (starts_with($tag, 'token_') and !starts_with($tag, 'token_id_'))
                {
                    $this->assertEquals($tokenTag, $tag);
                }
            }
            return true;
        });

        Event::assertNotDispatched(CacheHit::class, function ($e)
        {
            foreach ($e->tags as $tag)
            {
                if (starts_with($tag, 'token_') and !starts_with($tag, 'token_id_'))
                {
                    $this->assertEquals($tokenTag, $tag);
                }
            }
            return false;
        });

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000001']);

        $this->startTest();

        //
        // Asserts that key is found in cache on subsequent attempts
        //
        Event::assertDispatched(CacheHit::class, function($e) use ($tokenTag)
        {
            foreach ($e->tags as $tag) {
                if (starts_with($tag, 'token_') and !starts_with($tag, 'token_id_'))
                {
                    $this->assertEquals($tokenTag, $tag);
                }
            }
            return true;
        });
    }

    //Testing oauth related functionality to support slack app for X
    public function testSendOtpWithBearerAuth()
    {
        $client = Client\Entity::factory()->create(['environment' => 'dev']);

        $accessToken = $this->generateOAuthAccessToken(['scopes'    => ['rx_read_write', 'read_write'], 'client_id' => $client->getId()], 'dev');

        $this->fixtures->create('feature', [
            'entity_id' => $client->application_id,
            'entity_type' => 'application',
            'name'  => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('user', ['id' => '20000000000000', 'contact_mobile' => 9999999999]);

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $this->fixtures->create('feature', [
            'entity_id' => '10000000000000',
            'entity_type' => 'application',
            'name'  => Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH]);

        $response = $this->startTest();

        $this->assertArrayKeysExist($response,['token']);

        $this->assertPassport();
        $this->assertPassportKeyExists('oauth.client_id');
        $this->assertPassportKeyExists('oauth.app_id');
    }

    public function testBearerAuthWithPassport()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport();

        $tokenEntityAttributes = $tokenEntity->toArrayAdmin();

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $passport = new Passport;
        $passport->identified    = true;
        $passport->authenticated = true;
        $passport->mode          = 'test';
        $passport->roles         = ['oauth::scope::read_only'];
        //set consumer
        $consumer           = new ConsumerClaims;
        $consumer->id       = '10000000000000';
        $consumer->type     = 'merchant';
        $passport->consumer = $consumer;
        //set credential
        $credential            = new CredentialClaims;
        $credential->username  = null;
        $credential->publicKey = 'rzp_test_oauth_'.$tokenEntityAttributes['public_token'];
        $passport->credential  = $credential;
        //set oauth
        $oauth                = new OAuthClaims;
        $oauth->ownerType     = 'merchant';
        $oauth->ownerId       = '10000000000000';
        $oauth->accessTokenId = $tokenEntityAttributes['id'];
        $oauth->clientId      = $tokenEntityAttributes['client_id'];
        $oauth->appId         =  $tokenEntity->getApplicationIdAttribute();
        $oauth->env           = 'test';
        $oauth->userId        = '20000000000000';
        $passport->oauth      = $oauth;

        app('request.ctx.v2')->passport = $passport;
        app('request.ctx.v2')->hasPassportJwt = true;

        $this->startTest();
    }

    public function testBearerAuthWithUnusablePassport()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport();

        $tokenEntityAttributes = $tokenEntity->toArrayAdmin();

        $this->ba->oauthBearerAuth($accessToken->toString());

        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $passport = new Passport;
        $passport->identified    = true;
        $passport->authenticated = true;
        $passport->mode          = 'test';
        //$passport->roles         = ['oauth::scope::read_only'];
        //set consumer
        $consumer           = new ConsumerClaims;
        $consumer->id       = '10000000000000';
        $consumer->type     = 'merchant';
        $passport->consumer = $consumer;
        //set credential
        $credential            = new CredentialClaims;
        $credential->username  = null;
        $credential->publicKey = 'rzp_test_oauth_'.$tokenEntityAttributes['public_token'];
        $passport->credential  = $credential;
        //set oauth
        $oauth                = new OAuthClaims;
        $oauth->ownerType     = 'merchant';
        $oauth->ownerId       = '10000000000000';
        $oauth->accessTokenId = $tokenEntityAttributes['id'];
        $oauth->clientId      = $tokenEntityAttributes['client_id'];
        $oauth->appId         =  $tokenEntity->getApplicationIdAttribute();
        $oauth->env           = 'test';
        $oauth->userId        = '20000000000000';
        $passport->oauth      = $oauth;

        app('request.ctx.v2')->passport = $passport;
        app('request.ctx.v2')->hasPassportJwt = true;

        $this->startTest();
    }
}
