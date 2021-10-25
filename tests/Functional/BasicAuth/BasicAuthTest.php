<?php

namespace RZP\Tests\Functional\BasicAuth;

use Carbon\Carbon;
use Lcobucci\JWT\Builder;
use Razorpay\Edge\Passport\Kid;
use Razorpay\Edge\Passport\Passport;
use Illuminate\Database\Eloquent\Factory;

use RZP\Http\Route;
use RZP\Models\Key;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Razorpay\Edge\Passport\Tests\GeneratesTestPassportJwts;

class BasicAuthTest extends TestCase
{
    use PartnerTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use GeneratesTestPassportJwts;

    /**
     * @var string passport public key
     */
    protected $publicKey;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BasicAuthData.php';

        $publicKeyStr = file_get_contents(__DIR__.'/helpers/edge-passport-apiv1-public.key');
        $this->publicKey = str_replace('\n', PHP_EOL, $publicKeyStr);

        parent::setUp();

        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->ba->privateAuth();
    }

    public function testNoAuth()
    {
        $this->ba->noAuth();

        $this->startTest();

        $this->assertEquals('Basic realm="Razorpay"', $this->response->headers->get('WWW-Authenticate'));
    }

    public function testNoAuthOnJsonpRoute()
    {
        $this->ba->noAuth();

        $this->startTest();

        $this->assertEquals('Basic realm="Razorpay"', $this->response->headers->get('WWW-Authenticate'));
    }

    public function testWrongKeyOnPublicJsonpRoute()
    {
        $this->ba->publicAuth('rzp_test_TheTstWrongKey');

        $this->startTest();
    }

    /**
     * This also checks the effect of providing secret on
     * public route
     */
    public function testPrivateAuthOnPublicRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAdminAuth()
    {
        $this->ba->adminAuth('test');

        $this->startTest();

        $this->assertPassport();
    }

    public function testAdminProxyAuthOnPrivateRoute()
    {
        $this->ba->adminProxyAuth();

        $this->fixtures->admin->edit($this->ba->getAdmin()->getId(), ['allow_all_merchants' => true]);

        $this->startTest();

        $this->assertPassport();
    }

    public function testAdminProxyAuthOnProxyRoute()
    {
        $this->ba->adminProxyAuth();

        $this->fixtures->admin->edit($this->ba->getAdmin()->getId(), ['allow_all_merchants' => true]);

        $this->startTest();

        $this->assertPassport();
    }

    public function testPrivateAuthOnAdminRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUnauthorizedOnJsonpRoute()
    {
        $this->ba->publicAuth('rzp_test_TheTestAusdKey');

        $this->startTest();
    }

    public function testNoSecretOnPrivateRoute()
    {
        $this->ba->privateAuth(null, '');

        $this->startTest();
    }

    public function testPublicAuthWithWrongKeyId()
    {
        $this->ba->publicAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongKeyId()
    {
        $this->ba->privateAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrimaryPrivateAuthWithoutXHeaderWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();

        $this->assertEquals(Product::PRIMARY, $this->app['basicauth']->getProduct());
    }

    public function testPrimaryPrivateAuthWithXHeaderWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();

        //This ensures that passing the X-Request-Origin header doesn't result in banking as product in case of private primary routes.
        $this->assertEquals(Product::PRIMARY, $this->app['basicauth']->getProduct());
    }

    public function testBankingPrivateAuthWithXHeaderWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();

        $this->assertEquals(Product::BANKING, $this->app['basicauth']->getProduct());
    }

    public function testBankingPrivateAuthWithoutXHeaderWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();

        $this->assertEquals(Product::BANKING, $this->app['basicauth']->getProduct());
    }

    public function testBankingProxyAuthWithWrongUser()
    {
        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000']);

        $this->fixtures->create('contact', ['id' => '1000000contact']);

        $this->ba->proxyAuth('randomuser1234');

        $this->startTest();

        //This ensures that in case of proxy auth, the X-Request-Origin header is the source of truth. Hence primary even though route belongs to banking.
        $this->assertEquals(Product::PRIMARY, $this->app['basicauth']->getProduct());
    }

    public function testAppAuthWithNoSecret()
    {
        $this->ba->appAuth('rzp_test', null);

        $this->startTest();
    }

    public function testPrivateAuthOnAppRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testProxyAuthOnPrivateRouteInCloud()
    {
        $this->ba->proxyAuth();

        $this->startTest();

        $this->assertPassport();
    }

    public function testProxyAuthOnPrivateRouteNotInCloud()
    {
        $this->ba->proxyAuth();

        $this->cloud = false;

        $this->startTest();
    }

    public function testProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->startTest();

        $this->assertPassport();
    }

    public function testPrivateAuthKeyNotExpired()
    {
        $this->ba->privateAuth();

        // Key expires 2 minutes from now
        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() + 120]);

        $this->startTest();

        $this->assertPassport();
    }

    public function testPrivateAuthAndPassportJwtIssuedByApi()
    {
        $this->ba->privateAuth();

        $this->startTest();

        // Asserts passport jwt build by api is valid.
        $token = $this->app['basicauth']->getPassportJwt('subscriptions.razorpay.com');

        $publicKey = file_get_contents(__DIR__.'/helpers/passport-apiv1-public.key');
        $kid1 = new Kid("apiv1", $publicKey);
        Passport::init($kid1);
        $passport = Passport::fromToken($token);

        $this->assertTrue($passport->identified);
        $this->assertTrue($passport->authenticated);
        $this->assertSame('test', $passport->mode);
        $this->assertInstanceOf(\Razorpay\Edge\Passport\ConsumerClaims::class, $passport->consumer);
        $this->assertSame('10000000000000', $passport->consumer->id);
        $this->assertSame('merchant', $passport->consumer->type);
    }

    public function testPrivateAuthKeyExpired()
    {
        $this->ba->privateAuth();

        // Key expired 20 seconds ago
        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() - 20]);

        $this->startTest();
    }

    public function testBasicAuthRealm()
    {
        ;
    }

    public function testAppRoutesWithPrivateAuth()
    {
        $this->ba->privateAuth();

        $internalRoutes = $this->app['api.route']->getApiRouteInCategory('internal');

        foreach ($internalRoutes as $routeName => $routeInfo)
        {
            $testData['request']['method'] = ($routeInfo[0] === 'any' ? 'post' : $routeInfo[0]);
            $testData['request']['url']    = $routeInfo[1];

            $this->startTest($testData);
        }
    }

    public function testAppRoutesWithInvalidPrivateAuth()
    {
        $this->ba->privateAuth(null, '=');

        $internalRoutes = $this->app['api.route']->getApiRouteInCategory('internal');

        foreach ($internalRoutes as $routeName => $routeInfo) {
            $testData['request']['method'] = ($routeInfo[0] === 'any' ? 'post' : $routeInfo[0]);
            $testData['request']['url'] = $routeInfo[1];

            $this->startTest($testData);
        }
    }

    public function testInvalidMerchantKeyForAppRouteAndNotExistentRoute()
    {
        ;
    }

    public function testValidMerchantKeyForAppRouteAndNonExistentRoute()
    {
        ;
    }

    public function testPublicQueryAuth()
    {
        $this->markTestIncomplete();

        $request = [
            'url' => '/payments/create/jsonp?keyid=rzp_test_TheTestAuthKey',
            'method' => 'GET',
        ];

        $this->ba->noAuth();

        $this->makeRequestAndGetContent($request);
    }

    public function testPublicAuth()
    {
        $this->doAuthPaymentViaCheckoutRoute(null);

        $this->assertPassport();
    }

    public function testAppAuthForCron()
    {
        $this->ba->cronAuth();

        $this->startTest();

        $this->assertPassport();

        $this->assertEquals(Product::PRIMARY, $this->app['basicauth']->getProduct());
    }

    public function testAppAuthNewFlowWithoutPassport()
    {
        $this->ba->appBasicAuth();

        $this->startTest();
    }

    public function testAppAuthNewFlowWithPassportForCron()
    {

        $this->ba->appBasicAuth(env('APP_V2_CREDENTIAL_USERNAME_LIVE_CRON'), env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CRON'));

        // overriding public key for test case
        $oldKey = app('config')->get('passport')['public_key'];
        app('config')->get('passport')['public_key'] = $this->publicKey;

        $this->startTest([
            'request' => [
                'server' => [
                    'HTTP_X-Passport-JWT-V1' => $this->sampleConsumerPassportJwtBuilder(env('APP_V2_ID_CRON'), 'application')
                ]
            ]
        ]);

        // resetting old key back
        app('config')->get('passport')['public_key'] = $oldKey;
    }

    public function testAppAuthNewFlowWithWrongPassportForCron()
    {

        $this->ba->appBasicAuth(env('APP_V2_CREDENTIAL_USERNAME_LIVE_CRON'), env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CRON'));

        // overriding public key for test case
        $oldKey = app('config')->get('passport')['public_key'];
        app('config')->get('passport')['public_key'] = $this->publicKey;

        $this->startTest([
            'request' => [
                'server' => [
                    'HTTP_X-Passport-JWT-V1' => $this->sampleConsumerPassportJwtBuilder(env('APP_V2_ID_CRON'), 'application', 'live', false)
                ]
            ]
        ]);

        // resetting old key back
        app('config')->get('passport')['public_key'] = $oldKey;

    }

    public function testAppAuthNewFlowWithWrongAppForCron()
    {

        $this->ba->appBasicAuth(env('APP_V2_CREDENTIAL_USERNAME_LIVE_CRON'), env('APP_V2_CREDENTIAL_PASSWORD_LIVE_CRON'));

        // overriding public key for test case
        $oldKey = app('config')->get('passport')['public_key'];
        app('config')->get('passport')['public_key'] = $this->publicKey;

        $this->startTest([
            'request' => [
                'server' => [
                    'HTTP_X-Passport-JWT-V1' => $this->sampleConsumerPassportJwtBuilder('unknown-id', 'application')
                ]
            ]
        ]);

        // resetting old key back
        app('config')->get('passport')['public_key'] = $oldKey;
    }

    public function testAppAuthForCronWithXHeader()
    {
        $this->ba->cronAuth();

        $this->startTest();

        $this->assertPassport();

        //This ensures that just passing the X-Request-Origin header doesn't result in banking as product in case request isn't made from dashboard
        $this->assertEquals(Product::PRIMARY, $this->app['basicauth']->getProduct());
    }

    public function testAppAuthWithAccount()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $merchant = $this->fixtures->create(
            'merchant', ['org_id' => Org::RZP_ORG]);

        $admin->merchants()->attach($merchant);

        $this->ba->addAccountAuth($merchant->getId());

        $publicOrgID = 'org_' . Org::RZP_ORG;

        $url = '/orgs/' . $publicOrgID . '/self';

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertEquals($publicOrgID, $result['id']);
    }

    public function testGraphqlAppAuth()
    {
        $user = $this->fixtures->create('user');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/users/' . $user['id'];

        $testData['request']['server']['HTTP_X-Dashboard-User-id'] = $user['id'];

        $this->ba->frontendGraphqlAuth();

        $this->startTest();
    }

    public function testGraphqlAppAuthOnProxyRoute()
    {
        $this->ba->frontendGraphqlAuth();

        $this->startTest();
    }

    public function testAdminAuthWithAccount()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $merchant = $this->fixtures->create(
            'merchant', ['org_id' => Org::RZP_ORG]);

        $admin->merchants()->attach($merchant);

        $this->ba->addAccountAuth($merchant->getId());

        $result = $this->startTest();

        $this->assertEquals($merchant->getId(), $result['id']);

        $this->assertPassport();
        $this->assertPassportKeyExists('impersonation.consumer.id', "/^{$merchant->getId()}$/");
    }

    public function testAccountAuthInvalidIdViaMerchantDashboard()
    {
        $this->ba->proxyAuth();

        $this->ba->addAccountAuth('12345');

        $this->startTest();
    }

    public function testAccountAuthInvalidIdViaMerchantDashboardWithXHeader()
    {
        $this->ba->proxyAuth();

        $this->ba->addAccountAuth('12345');

        $this->startTest();

        // In case of proxyAuth, X-Request-Origin header is the source of truth to set the product. Hence banking even though route belongs to primary.
        $this->assertEquals(Product::BANKING, $this->app['basicauth']->getProduct());
    }

    public function testAccountAuthInvalidIdViaAdminDashboard()
    {
        $this->ba->adminAuth();

        $this->ba->addAccountAuth('12345');

        $this->startTest();
    }

    /**
     * Testing the user authentication on user resend verification route.
     */
    public function testUserWhiteListAuthenticate()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        //In case of internal auth from an internalApp, source of truth is X-Request-Origin header. Hence primary.
        $this->assertEquals(Product::PRIMARY, $this->app['basicauth']->getProduct());
    }

    public function testUserWhiteListAuthenticateWithXHeader()
    {
        $this->ba->dashboardGuestAppAuth();

        $this->startTest();

        //In case of internal auth from an internalApp, source of truth is X-Request-Origin header. Hence banking.
        $this->assertEquals(Product::BANKING, $this->app['basicauth']->getProduct());
    }

    public function testFailedMerchantUserRouteValidation()
    {
        $merchantUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner1');

        $this->ba->proxyAuth('rzp_test_10000000000000', $merchantUser->getId());

        $this->startTest();
    }

    public function testPartnerAuthOnJsonpRoute()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $this->fixtures->create('emi_plan');

        $this->fixtures->create('methods', [
            'merchant_id'    => '100000Razorpay',
            'emi'            => [Merchant\Methods\EmiType::CREDIT => '1'],
            'disabled_banks' => [],
            'banks'          => '[]',
        ]);

        $this->ba->publicAuth('rzp_test_partner_' . $client->getId());

        $this->startTest();
    }

    public function testPartnerAuthOnJsonpRouteWrongClientId()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $this->fixtures->create('emi_plan');

        $this->ba->publicAuth('rzp_test_partner_' . 'wrongClient123');

        $this->startTest();
    }

    public function testPartnerAuthOnJsonpRouteAppMissing()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev', ['deleted_at' => Carbon::now()->timestamp]);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $this->fixtures->create('emi_plan');

        $this->ba->publicAuth('rzp_test_partner_' . $client->getId());

        $this->startTest();
    }

    public function testPartnerAuthOnJsonpRouteWrongMerchantForClient()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $newMerchant = $this->fixtures->create('merchant');

        $testData = ['request' => ['server' => ['HTTP_X-Razorpay-Account' => 'acc_' . $newMerchant['id']]]];

        $this->fixtures->create('emi_plan');

        $this->ba->publicAuth('rzp_test_partner_' . $client->getId());

        $this->startTest($testData);
    }

    public function testPartnerAuthOnJsonpRouteApiKey()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $this->fixtures->create('emi_plan');

        $key = $this->getLastEntity('key', true);

        $this->ba->publicAuth('rzp_test_partner_' . $key['id']);

        $this->startTest();
    }

    // If submerchantid is not passed in X-Razorpay-Account header, only whitelisted routes should be accessible
    public function testPartnerAuthWithoutAccountIdInHeader()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create('emi_plan', ['merchant_id' => '10000000000000']);

        $this->ba->publicAuth('rzp_test_partner_' . $client->getId());

        $this->startTest();
    }

    public function testRequestWithPartnerHeadersClientCreds()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $this->startTest();
    }

    public function testRequestWithPartnerHeadersPurePlatform()
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'pure_platform']);

        $this->fixtures->create(
            'merchant_access_map',
            [
                'entity_id'   => $client->getApplicationId(),
                'merchant_id' => '100000Razorpay'
            ]
        );

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $this->startTest();
    }

    public function testRequestWithPartnerNoSecret()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), '');

        $this->startTest();
    }

    public function testRequestWithPartnerHeadersClientCredsWrongMode()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->ba->privateAuth('rzp_live_partner_' . $client->getId(), $client->getSecret());

        $this->startTest();
    }

    public function testRequestWithPartnerHeadersWrongClientCreds()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), 'wrongsecret');

        $this->startTest();
    }

    public function testRequestWithPartnerInactiveMerchantLiveMode()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('prod');

        $this->ba->privateAuth('rzp_live_partner_' . $client->getId(), $client->getSecret());

        $this->startTest();
    }

    public function testRequestWithPartnerHeadersClientCredsNotPartner()
    {
        $client = $this->createOAuthApplicationAndGetClientByEnv('dev');

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $this->startTest();
    }

    public function testPartnerRequestOnNonMappedMerchant()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->ba->privateAuth('rzp_test_partner_' . $client->getId(), $client->getSecret());

        $this->startTest();
    }

    public function testRequestWithTwoFaRequiredWithTwoFaVerifiedTrue()
    {
        $this->mockRazorxWith(
            Merchant\RazorxTreatment::VALIDATE_USER_2FA_STATUS, 'on');

        $merchant = $this->fixtures->create('merchant:with_keys', [
            // Required for updating keys in live mode
            Merchant\Entity::HAS_KEY_ACCESS => true,
            Merchant\Entity::ACTIVATED      => true,
        ]);
        $merchantId = $merchant->getId();

        $key = $this->getDbEntity('key',
        [
            'merchant_id'   => $merchantId,
        ], 'live');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', 'live');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/keys/rzp_live_'.$key->getId();

        $this->ba->proxyAuth('rzp_live_'.$merchantId, $merchantUser->getId());

        $this->startTest();
    }

    public function testRequestWithTwoFaRequiredWithTwoFaVerifiedFalse()
    {
        $this->mockRazorxWith(
            Merchant\RazorxTreatment::VALIDATE_USER_2FA_STATUS);

        $merchant = $this->fixtures->create('merchant:with_keys', [
            // Required for updating keys in live mode
            Merchant\Entity::HAS_KEY_ACCESS => true,
            Merchant\Entity::ACTIVATED      => true,
        ]);
        $merchantId = $merchant->getId();

        $key = $this->getDbEntity('key',
        [
            'merchant_id'   => $merchantId,
        ], 'live');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner', 'live');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/keys/rzp_live_'.$key->getId();

        $this->ba->proxyAuth('rzp_live_'.$merchantId, $merchantUser->getId());

        $this->startTest();
    }

    public function testRequestWithTwoFaRequiredWithTwoFaVerifiedFalseFromBanking()
    {
        $this->mockRazorxWith(
            Merchant\RazorxTreatment::VALIDATE_USER_2FA_STATUS);

        $merchant = $this->fixtures->create('merchant:with_keys', [
            // Required for updating keys in live mode
            Merchant\Entity::HAS_KEY_ACCESS => true,
            Merchant\Entity::ACTIVATED      => true,
        ]);
        $merchantId = $merchant->getId();

        $key = $this->getDbEntity('key',
        [
            'merchant_id'   => $merchantId,
        ], 'live');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/keys/rzp_live_'.$key->getId();

        $this->ba->proxyAuth(
            'rzp_live_'.$merchantId,
            $merchantUser->getId());

        $this->startTest();
    }

    // This should pass even TwoFaVerified false,
    // since key update is a critical action only in live mode and not in test mode
    public function testRequestWithTwoFaRequiredOnlyOnLiveWithTwoFaVerifiedFalse()
    {
        $this->mockRazorxWith(
            Merchant\RazorxTreatment::VALIDATE_USER_2FA_STATUS);

        $merchant = $this->fixtures->create('merchant:with_keys');
        $merchantId = $merchant->getId();

        $key = $this->getDbEntity('key',
        [
            'merchant_id'   => $merchantId,
        ], 'test');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId, [], 'owner');

        $testData = & $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/keys/rzp_test_'.$key->getId();

        $this->ba->proxyAuth(
            'rzp_test_'.$merchantId,
            $merchantUser->getId());

        $this->startTest();
    }

    public function testAdminRouteWildcardPermissionFail()
    {
        $permission = Route::$routePermission['admin_get_multiple'];

        Route::$routePermission['admin_get_multiple'] = '*';

        $this->ba->adminAuth();

        $this->startTest();

        Route::$routePermission['admin_get_multiple'] = $permission;
    }

    public function testConnectionForTestingEnvironment()
    {
        $merchant = $this->fixtures->on('live')->create('merchant:with_keys');
        $merchantId = $merchant->getId();

        $this->app['env'] = 'testing';

        $merchant1 = $this->getDbEntity('merchant',
            [
                'id'   => $merchantId,
            ], 'live');

        $this->app['env'] = 'testing_docker';

        $merchant2 = $this->getDbEntity('merchant',
            [
                'id'   => $merchantId,
            ], 'live');

        $this->assertEquals($merchant['name'], $merchant1['name']);
        $this->assertEquals($merchant['name'], $merchant2['name']);
    }


    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    private function mockRazorxWith(string $featureUnderTest, string $value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')->will(
            $this->returnCallback(
                function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                {
                    return $feature === $featureUnderTest ? $value : 'control';
                }
            ));
    }

    private function sampleConsumerPassportJwtBuilder(string $consumer_id = '', string $consumer_type = 'merchant',
                                                      string $mode = 'live', bool $identified = true,
                                                      bool $authenticated = true): string
    {
        $builder = (new Builder)
            // Reserved/standard claims follows.
            ->issuedBy('https://edge.razorpay.com')
            ->permittedFor('https://api.razorpay.com')
            ->identifiedBy('per-req-uuid', true)
            ->issuedAt(time())
            ->canOnlyBeUsedAfter(time())
            ->expiresAt(time() + 15)
            ->withHeader('kid', 'edgev1')
            // Custom claims follows.
            ->withClaim('identified', $identified)
            ->withClaim('authenticated', $authenticated)
            ->withClaim('mode', $mode)
            ->withClaim('consumer', ['id' => $consumer_id, 'type' => $consumer_type]);
        return $this->samplePassportJwt($builder);
    }

    public function testPassportTokenForJob()
    {
        $this->ba->privateAuth();

        $this->startTest();

        // Asserts passport jwt build by api is valid.
        $jwtToken = $this->app['basicauth']->getPassportJwt(get_class(), 600);

        // Set to new passport function used for Jobs
        $this->app['basicauth']->setPassportFromJob($jwtToken);
        $token = $this->app['basicauth']->getPassportFromJob();

        $publicKey = file_get_contents(__DIR__.'/helpers/passport-apiv1-public.key');
        $kid1 = new Kid("apiv1", $publicKey);
        Passport::init($kid1);
        $passport = Passport::fromToken($token);

        $this->assertTrue($passport->identified);
        $this->assertTrue($passport->authenticated);
        $this->assertSame('test', $passport->mode);
        $this->assertInstanceOf(\Razorpay\Edge\Passport\ConsumerClaims::class, $passport->consumer);
        $this->assertSame('10000000000000', $passport->consumer->id);
        $this->assertSame('merchant', $passport->consumer->type);
    }
}
