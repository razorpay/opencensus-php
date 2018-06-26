<?php

namespace RZP\Tests\Functional\BasicAuth;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use Illuminate\Database\Eloquent\Factory;

class BasicAuthTest extends TestCase
{
    use OAuthTrait;
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/BasicAuthData.php';

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
            $testData['request']['method'] = $routeInfo[0];
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
            $testData['request']['method'] = $routeInfo[0];
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

        $result = $this->startTest();

        $this->assertEquals($merchant->getId(), $result['id']);
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
        $this->ba->proxyAuth('rzp_test_10000000000000', null, 'owner1');

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
        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

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

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function setUpPartnerMerchantAppAndGetClient(string $env = 'dev')
    {
        $factoryPath = base_path() . '/vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $client = $this->createPartnerApplicationAndGetClientByEnv($env);

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'fully-managed']);

        $this->fixtures->merchant->addFeatures(['partner']);

        return $client;
    }
}
