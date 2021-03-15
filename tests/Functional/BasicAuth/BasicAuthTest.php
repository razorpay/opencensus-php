<?php

namespace RZP\Tests\Functional\BasicAuth;

use Carbon\Carbon;
use RZP\Http\Route;
use RZP\Models\Key;

use RZP\Models\Merchant;
use RZP\Services\RazorXClient;

use RZP\Tests\Functional\TestCase;
use Illuminate\Database\Eloquent\Factory;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BasicAuthTest extends TestCase
{
    use PartnerTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BasicAuthData.php';

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

    public function testPrivateAuthWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();
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
    }

    public function testProxyAuthOnPrivateRouteNotInCloud()
    {
        $this->ba->proxyAuth();

        $this->cloud = false;

        $this->startTest();
    }

    public function testPrivateAuthKeyNotExpired()
    {
        $this->ba->privateAuth();

        // Key expires 2 minutes from now
        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() + 120]);

        $this->startTest();
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

        foreach ($internalRoutes as $routeName => $routeInfo)
        {
            $testData['request']['method'] = ($routeInfo[0] === 'any' ? 'post' : $routeInfo[0]);
            $testData['request']['url']    = $routeInfo[1];

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
            'url'    => '/payments/create/jsonp?keyid=rzp_test_TheTestAuthKey',
            'method' => 'GET',
        ];

        $this->ba->noAuth();

        $this->makeRequestAndGetContent($request);
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
    }

    public function testAccountAuthInvalidId()
    {
        $this->ba->appAuth();

        $this->ba->addAccountAuth('12345');

        $this->startTest();
    }

    /**
     * Testing the user authentication on user resend verification route.
     */
    public function testUserWhiteListAuthenticate()
    {
        $this->ba->appAuth();

        $this->startTest();
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
}
