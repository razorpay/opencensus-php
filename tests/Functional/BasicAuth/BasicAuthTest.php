<?php

namespace RZP\Tests\Functional\BasicAuth;

use DateTimeZone;
use Carbon\Carbon;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Razorpay\Edge\Passport\Kid;
use Razorpay\Edge\Passport\Passport;
use Illuminate\Database\Eloquent\Factory;

use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Http\Route;
use RZP\Models\Key;
use RZP\Models\Merchant;
use RZP\Constants\Product;
use Razorpay\OAuth\Client;
use RZP\Models\Pricing\Fee;
use RZP\Models\User\Role;
use RZP\Services\DiagClient;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\BankingAccountStatement\Details;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Razorpay\Edge\Passport\Tests\GeneratesTestPassportJwts;
use GuzzleHttp\Server\Server;
use GuzzleHttp\Psr7\Response;

class BasicAuthTest extends TestCase
{
    use PartnerTrait;
    use PaymentTrait;
    use DbEntityFetchTrait;
    use GeneratesTestPassportJwts;

    /**
     * @var string passport jwks host
     */
    protected $jwksHost;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BasicAuthData.php';

        $this->jwksHost = "https://edge-base.dev.razorpay.in";

        parent::setUp();



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

    public function testProxyAuthWithoutAuthzRoles()
    {
        $adminRoleUser = $this->fixtures->user->createUserForMerchant('10000000000000', [], Role::MANAGER);

        $testData = & $this->testData[__FUNCTION__];
        $testData['expected_passport']['consumer']['id'] = $adminRoleUser->getId();

        $this->ba->proxyAuth('rzp_test_10000000000000', $adminRoleUser->getId());

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

        Passport::init($this->jwksHost, storage_path('passport'));
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
        app('config')->get('passport')['jwks_host'] = $this->jwksHost;

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
        app('config')->get('passport')['jwks_host'] = $this->jwksHost;

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
        app('config')->get('passport')['jwks_host'] = $this->jwksHost;

        $this->startTest([
            'request' => [
                'server' => [
                    'HTTP_X-Passport-JWT-V1' => $this->sampleConsumerPassportJwtBuilder('unknown-id', 'application')
                ]
            ]
        ]);

        // resetting old key back
        app('config')->get('passport')['jwks_host'] = $oldKey;
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
            'addon_methods' => ['credit_emi' => ['HDFC' => 1]],
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

    private function sampleConsumerPassportJwtBuilder(string $consumer_id = '', string $consumer_type = 'merchant',
                                                      string $mode = 'live', bool $identified = true,
                                                      bool $authenticated = true): string
    {
        $sysClock = new SystemClock(new DateTimeZone('UTC'));
        $builder = new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        $builder=  $builder
            ->issuedBy('https://edge.razorpay.com')
            ->permittedFor('https://api.razorpay.com')
            ->identifiedBy('per-req-uuid', true)
            ->issuedAt($sysClock->now())
            ->canOnlyBeUsedAfter($sysClock->now())
            ->expiresAt($sysClock->now()->add(new \DateInterval('P15M')))
            ->withHeader('kid', 'edgev1')
            // Custom claims follows.
            ->withClaim('identified', $identified)
            ->withClaim('authenticated', $authenticated)
            ->withClaim('mode', $mode)
            ->withClaim('consumer', ['id' => $consumer_id, 'type' => $consumer_type]);
        return $this->samplePassportJwt($builder);
    }

    public function testBalancesApiOnPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->fixtures->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'shared',
                'account_number' => '2224440041626903',
                'merchant_id'    => '10000000000000',
                'balance'        => 300000
            ]);

        $balance = $this->getDbLastEntity('balance');

        $bankingAccountAttributes = [
            'id'                    =>  'ABCde1234ABCdf',
            'account_number'        =>  '2224440041626907',
            'balance_id'            =>  $balance['id'],
            'account_type'          =>  'nodal',
        ];

        $this->createBankingAccount($bankingAccountAttributes);

        $this->testData[__FUNCTION__]['request']['url'] = '/balances';

        $this->fixtures->create('balance',
            [
                'type'           => 'banking',
                'account_type'   => 'direct',
                'account_number' => '2224440041626905',
                'merchant_id'    => '10000000000000',
                'balance'        => 800000
            ]);

        $balance = $this->getDbLastEntity('balance');

        $this->fixtures->create('banking_account_statement_details', [
            Details\Entity::ID                      => 'xbas0000000002',
            Details\Entity::MERCHANT_ID             => '10000000000000',
            Details\Entity::BALANCE_ID              => $balance->getId(),
            Details\Entity::ACCOUNT_NUMBER          => '2224440041626905',
            Details\Entity::CHANNEL                 => Details\Channel::ICICI,
            Details\Entity::STATUS                  => Details\Status::ACTIVE,
            Details\Entity::GATEWAY_BALANCE         => 800000,
            Details\Entity::BALANCE_LAST_FETCHED_AT => 1659873429
        ]);

        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() + 12000]);

        $this->verifyBalanceEventNotTracked();

        $this->startTest();

        $this->assertPassport();
    }

    private function mockDiag()
    {
        $diagMock = $this->getMockBuilder(DiagClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['trackEvent'])
            ->getMock();

        $this->app->instance('diag', $diagMock);
    }

    // For the request other that slack app the event should not be triggered
    private function verifyBalanceEventNotTracked()
    {
        $this->mockDiag();

        $this->app->diag->method('trackEvent')
            ->will($this->returnCallback(
                function (string $eventType,
                          string $eventVersion,
                          array $event,
                          array $properties)
                {
                    $e = new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR,
                        null,
                        [
                            'STATUS' => 'this event should not be tracked'
                        ]);

                    throw $e;
                }));
    }

    protected function createBankingAccount(array $attributes = [], string $mode = 'test')
    {
        $bankingAccount = $this->fixtures->on($mode)->create('banking_account', [
            'id'                    => $attributes["id"] ?? 'ABCde1234ABCde',
            'account_number'        => $attributes["account_number"] ?? '2224440041626905',
            'account_ifsc'          => $attributes["account_ifsc"] ?? 'RATN0000088',
            'account_type'          => $attributes["account_type"] ?? 'current',
            'merchant_id'           => $attributes["merchant_id"] ?? '10000000000000',
            'channel'               => $attributes["channel"] ?? 'rbl',
            'pincode'               => $attributes["pincode"] ?? '1',
            'bank_reference_number' => $attributes["bank_reference_number"] ?? '',
            'balance_id'            => $attributes["balance_id"] ?? '',
            'status'                => 'activated',
        ]);

        return $bankingAccount;
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

        Passport::init($this->jwksHost, storage_path('passport'));
        $passport = Passport::fromToken($token);

        $this->assertTrue($passport->identified);
        $this->assertTrue($passport->authenticated);
        $this->assertSame('test', $passport->mode);
        $this->assertInstanceOf(\Razorpay\Edge\Passport\ConsumerClaims::class, $passport->consumer);
        $this->assertSame('10000000000000', $passport->consumer->id);
        $this->assertSame('merchant', $passport->consumer->type);
    }

    public function testGetPayoutsPurposeApiOnPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() + 12000]);

        $this->startTest();

        $this->assertPassport();
    }
    //testMerchantAuthWithImpersonationOnPost checks request rejection in case of non whitelisted MID
    public function testMerchantAuthWithImpersonationOnPost()
    {
        $this->ba->privateAuth();

        $testData = $this->testData['testCreateOrderWithAccId'];

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_ACCOUNT_ID_IN_BODY);

    }

    //testMerchantAuthWithImpersonationWhitelistedMidRouteBodyParsing accepts request in case of whitelisted MID for body parsing.
    public function testMerchantAuthWithImpersonationWhitelistedMidRouteBodyParsing()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'Hoah6C9SnyNIs5']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);

        $this->ba->privateAuth('rzp_test_'.$key->getKey(),$key->getDecryptedSecret());

        $this->runRequestResponseFlow($this->testData['testCreateOrderWithAccId']);
    }

    //testMerchantAuthWithImpersonationNonWhitelistedMIDBodyParsing checks request rejection in case of non whitelisted MID
    public function testMerchantAuthWithImpersonationNonWhitelistedMIDBodyParsing()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'hoah6c9snynis8']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);

        $this->ba->privateAuth('rzp_test_'.$key->getKey(),$key->getDecryptedSecret());

        $testData = $this->testData['testMerchantAuthWithImpersonationForNonWhitelisted'];

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_ACCOUNT_ID_IN_BODY);
    }

    //testMerchantAuthWithImpersonationNonWhitelistedRouteBodyParsing checks request rejection in case of non whitelisted route with whitelisted MID
    public function testMerchantAuthWithImpersonationNonWhitelistedRouteBodyParsing()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'Hoah6C9SnyNIs5']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);

        $this->ba->privateAuth('rzp_test_'.$key->getKey(),$key->getDecryptedSecret());

        $this->testData['testMerchantAuthWithImpersonationForNonWhitelisted']['request']['method'] = 'POST';
        $this->testData['testMerchantAuthWithImpersonationForNonWhitelisted']['request']['url'] = '/refunds';

        $testData=$this->testData['testMerchantAuthWithImpersonationForNonWhitelisted'];

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_ACCOUNT_ID_IN_BODY);
    }

    //testMerchantAuthWithImpersonationOnPatch checks request rejection in case of non whitelisted route with whitelisted MID
    public function testMerchantAuthWithImpersonationOnPatch()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'Hoah6C9SnyNIs5']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);

        $this->ba->privateAuth('rzp_test_'.$key->getKey(),$key->getDecryptedSecret());

        $order = $this->runRequestResponseFlow($this->testData['testCreateOrderWithAccId']);

        $this->testData['testOrderEditWithAccId']['request']['url'] = '/orders/' . $order['id'];

        $testData = $this->testData['testOrderEditWithAccId'];

        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_ACCOUNT_ID_IN_BODY);

    }

    public function testMerchantAuthWithImpersonationOnGet()
    {
        $this->ba->privateAuth();

        $this->runRequestResponseFlow($this->testData['testPaymentFetchWithAccId']);
    }
}
