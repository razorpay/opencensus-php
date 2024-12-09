<?php

namespace RZP\Tests\Functional\PassportAuth;

use ApiResponse;
use Illuminate\Support\Facades\App;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Route;
use RZP\Models\Merchant;
use RZP\Constants\Mode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Http\BasicAuth\ClientAuthCreds;
use RZP\Http\BasicAuth\KeyAuthCreds;
use RZP\Tests\Functional\Helpers\Edge\PassportTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Partner\PartnerTrait;
use RZP\Models\Feature\Constants as FeatureConstants;

class PassportAuthTest extends TestCase
{
    use PartnerTrait;
    use PaymentTrait;
    use PassportTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/PassportAuthData.php';
        parent::setUp();
    }

    public function testNotAuthenticatedByPassportWhenPassportUnusable()
    {
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';
        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    public function testNotAuthenticatedByPassportWhenPassportIsEmpty()
    {
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'true';
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = '';
        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    public function testNotAuthenticatedByPassportWhenRequestIsUnauthenticated()
    {
        $passportJWT = $this->samplePassportJwtBuilder([], [], Mode::TEST, [], [], [], false, false);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'true';
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    public function testNotAuthenticatedByPassportWhenPassportClaimsMissing()
    {
        $consumer = ['id' => '10000000000000', 'type' => ''];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => ''];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'true';
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $this->ba->privateAuth();

        $this->runRequestResponseFlow($testData);
        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    public function testMerchantAuth()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheTestAuthKey', '10000000000000', 'rzp_test_TheTestAuthKey', KeyAuthCreds::class);
    }

    public function testMerchantAuthWithImpersonation()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'Hoah6C9SnyNIs5']);
        $key = $this->fixtures->create('key', ['merchant_id' => $merchant->getId()]);
        $keyName = "rzp_test_".$key->getKey();

        $consumer = ['id' => 'Hoah6C9SnyNIs5', 'type' => 'merchant'];
        $credential = ['username' => $keyName, 'public_key' => $keyName."-acc_100000Razorpay"];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, $impersonation);
        $testData = $this->testData['testMerchantAuthWithImpersonation'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues($key->getKey(), 'Hoah6C9SnyNIs5', $keyName."-acc_100000Razorpay", KeyAuthCreds::class, '100000Razorpay');
        self::assertEmpty($this->app['request']->input('account_id'));
    }

    public function testPartnerAuth()
    {
        $this->setUpPartnerMerchantAppAndGetClient('dev');

        $consumer = ['id' => '10000000000000', 'type' => 'partner'];
        $credential = ['username' => 'rzp_test_partner_TheTestAuthKey', 'public_key' => 'rzp_test_partner_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues('TheTestAuthKey', '10000000000000', 'rzp_test_partner_TheTestAuthKey', ClientAuthCreds::class, '', Mode::TEST, false);
    }

    public function testPartnerAuthWithImpersonation()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create('key', ['id' => $client->getId(), 'merchant_id' => '10000000000000']);
        $this->fixtures->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);

        $consumer = ['id' => '10000000000000', 'type' => 'partner'];
        $username = "rzp_test_partner_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, $impersonation);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues($client->getId(), '100000Razorpay', $publickey, ClientAuthCreds::class, '100000Razorpay',
            Mode::TEST, true, '10000000000000', $client->getApplicationId(), $client->getApplicationId());
        self::assertEmpty($this->app['request']->input('account_id'));
    }

    public function testOauth()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport();
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->create('user', ['id' => '20000000000000']);
        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_oauth_TheTestAuthKey', 'public_key' => 'rzp_test_oauth_TheTestAuthKey'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'dev'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, [], $oauth, $roles);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '10000000000000', 'rzp_test_oauth_TheTestAuthKey', '20000000000000', $tokenEntity['id'],
            $tokenEntity['client_id'], $tokenEntity['application']['id'], ['read_only'], '10000000000000');
    }

    public function testPublicOauthWithInternalAuthAjax()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport();
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->create('user', ['id' => '20000000000000']);
        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_oauth_TheTestAuthKey', 'public_key' => 'rzp_test_oauth_TheTestAuthKey'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'dev'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, [], $oauth, $roles, true, false);
        $testData = $this->testData['paymentsCreateAjax'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_ajax', 'rzp_test', $pwd
        );

        // create payment_create_ajax route related fixtures
        $order = $this->createFixturesPaymentCreateAjax();

        // set request content
        $testData['request']['content'] = $this->getPaymentCreateAjaxRequestContent($order->getId(), $order->getAmount());

        $response = $this->makeRequestParent($testData['request']);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        $this->processAndAssertStatusCode($testData, $response);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);;
        $this->assertOauthValues( '10000000000000', 'rzp_test_oauth_TheTestAuthKey', '20000000000000', $tokenEntity['id'],
            $tokenEntity['client_id'], $tokenEntity['application']['id'], ['read_only'], '10000000000000', '', 'test', 'public');
    }

    public function testOauthWithImpersonationWithAggregatorClient()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient();

        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport(['scopes' => ['rx_read_write', 'read_write'], 'client_id' => $client->getId()], 'dev');
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->create('user', ['id' => '20000000000000']);
        $this->fixtures->create('payment', ['id' => '10000000000000']);
        $this->fixtures->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);
        $this->fixtures->merchant->addFeatures(['aggregator_oauth_client']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];

        $username = "rzp_test_oauth_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'dev'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, $impersonation, $oauth, $roles);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '100000Razorpay', $publickey, '20000000000000', $tokenEntity['id'],
            $tokenEntity['client_id'], $tokenEntity['application']['id'], ['read_only'], '10000000000000',
            '100000Razorpay');
    }

    public function testMerchantActivationOnLiveMode()
    {
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => true]);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_live_TheLiveAuthKey', 'public_key' => 'rzp_live_TheLiveAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheLiveAuthKey', '10000000000000', 'rzp_live_TheLiveAuthKey', KeyAuthCreds::class, '', Mode::LIVE);
    }

    public function testPublicMerchantActivationOnLiveModeWithInternalAuth()
    {
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => true]);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_live_TheLiveAuthKey', 'public_key' => 'rzp_live_TheLiveAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, authenticated: false);
        $testData = $this->testData['paymentsCreateCheckout'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_checkout', 'rzp_live', $pwd
        );

        $testData['request']['content'] = $this->getPaymentCreateCheckoutRequestContent();

        $response = $this->makeRequestParent($testData['request']);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        $this->processAndAssertStatusCode($testData, $response);
        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheLiveAuthKey', '10000000000000', 'rzp_live_TheLiveAuthKey', KeyAuthCreds::class, '', Mode::LIVE, authType: 'public');
    }

    public function testMerchantNotActivationOnLiveMode()
    {
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => false]);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_live_TheLiveAuthKey', 'public_key' => 'rzp_live_TheLiveAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE);
        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST;
        $testData['exception']['class'] = 'RZP\Exception\BadRequestException';
        $testData['exception']['internal_error_code'] = PublicErrorCode::BAD_REQUEST_ERROR;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheLiveAuthKey', '10000000000000', 'rzp_live_TheLiveAuthKey', KeyAuthCreds::class, '', Mode::LIVE);
    }

    public function testPublicMerchantNotActivatedOnLiveModeWithInternalAuth()
    {
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => false]);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_live_TheLiveAuthKey', 'public_key' => 'rzp_live_TheLiveAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, authenticated: false);
        $testData = $this->testData['paymentsCreateCheckout'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['code'] = PublicErrorCode::BAD_REQUEST_ERROR;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST;
        $testData['response']['status_code'] = 400;
        $testData['exception']['class'] = 'RZP\Exception\BadRequestException';
        $testData['exception']['internal_error_code'] = PublicErrorCode::BAD_REQUEST_ERROR;

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_checkout', 'rzp_live', $pwd
        );

        $testData['request']['content'] = $this->getPaymentCreateCheckoutRequestContent();

        $this->runRequestResponseFlow($testData);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheLiveAuthKey', '10000000000000', 'rzp_live_TheLiveAuthKey', KeyAuthCreds::class, '', Mode::LIVE, authType: 'public');
    }

    public function testPartnerAuthNotAllowedForPurePlatform()
    {
        $this->setUpPartnerMerchantAppAndGetClient('dev');

        $consumer = ['id' => '10000000000000', 'type' => 'partner'];
        $credential = ['username' => 'rzp_test_partner_TheTestAuthKey', 'public_key' => 'rzp_test_partner_TheTestAuthKey'];

        $this->fixtures->edit('merchant', '10000000000000', ['partner_type' => 'pure_platform']);

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED;


        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues('TheTestAuthKey', '10000000000000', 'rzp_test_partner_TheTestAuthKey', ClientAuthCreds::class, '', Mode::TEST, true);
    }

    public function testPartnerAuthSubMerchantNotActivated()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->on(Mode::LIVE)->create('key', ['id' => $client->getId(), 'merchant_id' => '10000000000000']);
        $this->fixtures->on(Mode::LIVE)->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => true]);

        $consumer = ['id' => '10000000000000', 'type' => 'partner'];
        $username = "rzp_live_partner_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, $impersonation);

        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues($client->getId(), '100000Razorpay', $publickey, ClientAuthCreds::class, '100000Razorpay', Mode::LIVE, true, '10000000000000');
    }

    public function testPartnerAuthParentMerchantNotActivated()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('prod');

        $this->fixtures->on(Mode::LIVE)->create('key', ['id' => $client->getId(), 'merchant_id' => '10000000000000']);
        $this->fixtures->on(Mode::LIVE)->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => false]);
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '100000Razorpay', ['activated' => true]);

        $consumer = ['id' => '10000000000000', 'type' => 'partner'];
        $username = "rzp_live_partner_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, $impersonation);

        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues($client->getId(), '100000Razorpay', $publickey, ClientAuthCreds::class,
            '100000Razorpay', Mode::LIVE, true, '10000000000000', $client->getApplicationId(), $client->getApplicationId());
    }

    public function testOauthMerchantActivationOnLiveMode()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport(['mode' => Mode::LIVE]);
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->on(Mode::LIVE)->create('user', ['id' => '20000000000000']);
        $this->fixtures->on(Mode::LIVE)->create('payment', ['id' => '10000000000000']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_live_oauth_TheLiveAuthKey', 'public_key' => 'rzp_live_oauth_TheLiveAuthKey'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'prod'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, [], $oauth, $roles);
        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '10000000000000', 'rzp_live_oauth_TheLiveAuthKey', '20000000000000', '',
            '', '', null, '', '', Mode::LIVE);
    }

    public function testPublicOauthMerchantNotActivatedOnLiveModeWithInternalAuth()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport(['mode' => Mode::LIVE]);
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->on(Mode::LIVE)->create('user', ['id' => '20000000000000']);
        $this->fixtures->on(Mode::LIVE)->create('payment', ['id' => '10000000000000']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_live_oauth_TheLiveAuthKey', 'public_key' => 'rzp_live_oauth_TheLiveAuthKey'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'prod'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, [], $oauth, $roles, true, false);
        $testData = $this->testData['paymentsCreateCheckout'];
        $testData['request']['status_code'] = 400;
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';
        $testData['response']['content']['error']['code'] = PublicErrorCode::BAD_REQUEST_ERROR;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_OAUTH_MERCHANT_NOT_ACTIVATED;

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_checkout', 'rzp_live', $pwd
        );

        $testData['request']['content'] = $this->getPaymentCreateCheckoutRequestContent();

        $this->runRequestResponseFlow($testData);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '10000000000000', 'rzp_live_oauth_TheLiveAuthKey', '20000000000000', '',
            '', '', null, '', '', Mode::LIVE, 'public');
    }

    public function testOauthSubMerchantNotActivated()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient();

        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport(['mode' => Mode::LIVE, 'scopes' => ['rx_read_write', 'read_write'], 'client_id' => $client->getId()], 'dev');
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->on(Mode::LIVE)->create('user', ['id' => '20000000000000']);
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => true]);
        $this->fixtures->on(Mode::LIVE)->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];

        $username = "rzp_live_oauth_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'prod'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, $impersonation, $oauth, $roles);

        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED;


        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '100000Razorpay', $publickey, '20000000000000', '',
            '', '', null, '10000000000000', '100000Razorpay', Mode::LIVE);
    }

    public function testPublicOauthSubMerchantNotActivatedWithInternalAuth()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient();

        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport(['mode' => Mode::LIVE, 'scopes' => ['rx_read_write', 'read_write'], 'client_id' => $client->getId()], 'dev');
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->on(Mode::LIVE)->create('user', ['id' => '20000000000000']);
        $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['activated' => true]);
        $this->fixtures->on(Mode::LIVE)->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];

        $username = "rzp_live_oauth_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'prod'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::LIVE, $impersonation, $oauth, $roles, true, false);

        $testData = $this->testData['paymentsCreateCheckout'];
        $testData['request']['status_code'] = 400;
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';
        $testData['response']['content']['error']['code'] = PublicErrorCode::BAD_REQUEST_ERROR;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED;

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_checkout', 'rzp_live', $pwd
        );

        $testData['request']['content'] = $this->getPaymentCreateCheckoutRequestContent();

        $this->runRequestResponseFlow($testData);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '100000Razorpay', $publickey, '20000000000000', '',
            '', '', null, '10000000000000', '100000Razorpay', Mode::LIVE, 'public');
    }

    public function testMerchantAuthWithImpersonationCannotSkipWorkflow()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey-acc_100000Razorpay'];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, $impersonation);

        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID;
        $testData['response']['status_code'] = 401;

        $this->fixtures->merchant->addFeatures(['marketplace']);
        $this->fixtures->create('merchant_access_map', ['entity_id'   => '10000000000000', 'merchant_id' => '100000Razorpay']);
        $this->fixtures->create('merchant_application',
            [
                'merchant_id' => '100000Razorpay',
                'application_id' => '10000000000000',
                'type'   => 'referred'
            ]
        );

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues('TheTestAuthKey', '10000000000000', 'rzp_test_TheTestAuthKey-acc_100000Razorpay', KeyAuthCreds::class, '100000Razorpay');
    }

    public function testOauthWithImpersonationWithoutAggregatorClient()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient();

        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport(['scopes' => ['rx_read_write', 'read_write'], 'client_id' => $client->getId()], 'dev');
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->create('user', ['id' => '20000000000000']);
        $this->fixtures->create('merchant_access_map', ['entity_id' => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];

        $username = "rzp_test_oauth_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'dev'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, $impersonation, $oauth, $roles);

        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '100000Razorpay', $publickey, '20000000000000', $tokenEntity['id'],
            $tokenEntity['client_id'], $tokenEntity['application']['id'], ['read_only'], '10000000000000', '100000Razorpay');
    }

    public function testOauthWithoutRazorpayXFeatureEnabled()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport();
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->create('user', ['id' => '20000000000000']);
        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_oauth_TheTestAuthKey', 'public_key' => 'rzp_test_oauth_TheTestAuthKey'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'test'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, [], $oauth, $roles);
        $testData = $this->testData['testOauthWithoutRazorpayXFeatureEnabled'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertOauthValues( '10000000000000', 'rzp_test_oauth_TheTestAuthKey', '20000000000000', $tokenEntity['id'],
            $tokenEntity['client_id'], $tokenEntity['application']['id'], ['read_only'], '10000000000000', '');
    }

    /**
     * testInternalAuthWithPassport
     * should not use passport even if valid passport is passed in header with passport usable as true, as current route is not whitelisted
     * i.e not part of private/public route array
     *
     */
    public function testPassportAuthOnInternalRoute()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test', 'public_key' => 'rzp_test'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['testPassportAuthOnInternalRoute'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $cronConfig = \Config::get('applications.cron');
        $pwd = $cronConfig['secret'];
        $this->ba->basicAuth('rzp_test',$pwd);
        $this->ba->addAppAuthHeaders('dashboard.razorpay.com');
        $this->runRequestResponseFlow($testData);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    /**
     * testPassportAuthOnAdminRoute
     * should not use passport even if valid passport is passed in header with passport usable as true, as current route is not whitelisted
     * i.e not part of private/public route array
     *
     */
    public function testPassportAuthOnAdminRoute()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test', 'public_key' => 'rzp_test'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['testPassportAuthOnAdminRoute'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->ba->adminAuth();
        $this->runRequestResponseFlow($testData);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    /**
     * testPassportAuthOnProxyRoute
     * should not use passport even if valid passport is passed in header with passport usable as true, as current route is not whitelisted
     * i.e not part of private/public route array
     *
     */
    public function testPassportAuthOnProxyRoute()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_10000000000000', 'public_key' => 'rzp_test_10000000000000'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['testPassportAuthOnProxyRoute'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->ba->proxyAuth();
        $this->runRequestResponseFlow($testData);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    /**
     * testPassportAuthOnDirectRoute
     * should not use passport even if valid passport is passed in header with passport usable as true, as current route is not whitelisted
     * i.e not part of private/public route array
     *
     */
    public function testPassportAuthOnDirectRoute()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['testPassportAuthOnDirectRoute'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->ba->directAuth();

        $response = $this->sendRequest($testData['request']);
        $this->assertStringContainsString('<title>Razorpay Checkout</title>', $response->getContent());
        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    /**
     * testPassportAuthOnDeviceRoute
     * should not use passport even if valid passport is passed in header with passport usable as true, as current route is not whitelisted
     * i.e not part of private/public route array
     *
     */
    public function testPassportAuthOnDeviceRoute()
    {
        $mockBA = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['deviceAuth'])
            ->getMock();
        $this->app->instance('basicauth', $mockBA);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['testPassportAuthOnDeviceRoute'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->ba->deviceAuth();

        // generate dummy response
        $mockBA->expects($this->once())->method('deviceAuth')->willReturn(ApiResponse::generateResponse());

        $this->sendRequest($testData['request']);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    //Public auth test cases

    // public auth with merchant key. added test case here so it can be used in future when enabling auth middleware bypass also
    public function testPublicMerchantAuth()
    {
        $testData = $this->testData['testPublicAuth'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];
        $consumer = ['id'=>'10000000000000','type'=>'merchant'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $this->samplePassportJwtBuilder(consumer: $consumer, credential: $credential, authenticated: false);
        $this->ba->publicAuth();
        $response = $this->makeRequestAndGetContent($testData['request']);
        $this->assertArrayHasKey('razorpay_payment_id',$response);
    }

    // public auth with merchant key. added test case here so it can be used in future when enabling auth middleware bypass also
    public function testPublicMerchantAuthWithInternalAuthWithAccess()
    {
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];
        $consumer = ['id'=>'10000000000000','type'=>'merchant'];
        $passportJWT = $this->samplePassportJwtBuilder(consumer: $consumer, credential: $credential, authenticated: false);
        $testData = $this->testData['paymentsCreateAjax'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_ajax', 'rzp_test', $pwd
        );

        // create payment_create_ajax route related fixtures
        $order = $this->createFixturesPaymentCreateAjax();

        // set request content
        $testData['request']['content'] = $this->getPaymentCreateAjaxRequestContent($order->getId(), $order->getAmount());

        $response = $this->makeRequestParent($testData['request']);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        $this->processAndAssertStatusCode($testData, $response);
        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheTestAuthKey', '10000000000000', 'rzp_test_TheTestAuthKey', KeyAuthCreds::class, authType: 'public');
    }

    public function testPartnerPublicAuthWithImpersonation()
    {
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create('key', ['id' => $client->getId(), 'merchant_id' => '10000000000000']);
        $this->fixtures->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);

        $this->fixtures->create('emi_plan');

        $this->fixtures->create('methods', [
            'merchant_id'    => '100000Razorpay',
            'emi'            => [Merchant\Methods\EmiType::CREDIT => '1'],
            'disabled_banks' => [],
            'banks'          => '[]',
            'addon_methods' => ['credit_emi' => ['HDFC' => 1]],
        ]);

        $this->ba->publicAuth('rzp_test_partner_' . $client->getId());

        $consumer = ['id' => '10000000000000', 'type' => 'partner'];
        $username = "rzp_test_partner_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, $impersonation,authenticated: false);
        $testData = $this->testData['testPartnerPublicAuth'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_100000Razorpay';

        $this->ba->publicAuth($username);
        $response = $this->makeRequestAndGetContent($testData['request']);
        $this->assertArrayHasKey('HDFC',$response);
        self::assertEmpty($this->app['request']->input('account_id'));
    }

    public function testPartnerPublicAuthWithImpersonationWithInternalAuth()
    {
        // TODO: Complete this function
        $client = $this->setUpPartnerMerchantAppAndGetClient('dev');

        $this->fixtures->create('key', ['id' => $client->getId(), 'merchant_id' => '10000000000000']);
        $this->fixtures->create('merchant_access_map', ['entity_id'   => $client->getApplicationId(), 'merchant_id' => '100000Razorpay']);

        $consumer = ['id' => '10000000000000', 'type' => 'partner'];
        $username = "rzp_test_partner_" . $client->getId();
        $publickey = $username . "-acc_100000Razorpay";
        $credential = ['username' => $username, 'public_key' => $publickey];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $testData = $this->testData['paymentsCreateCheckout'];
        $passportJWT = $this->samplePassportJwtBuilder(
            consumer: $consumer,
            credential: $credential,
            mode: Mode::TEST,
            impersonation: $impersonation,
            identified: true,
            authenticated: false
        );
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';
        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_100000Razorpay';

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_checkout', 'rzp_test', $pwd
        );

        $payment = $this->getPaymentCreateCheckoutRequestContent();
        $testData['request']['content'] = $payment;

        $response = $this->makeRequestParent($testData['request']);
        $response->assertViewIs('tokenisation.recurringTokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();
        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);
        $this->assertEquals($payment['card']['number'], $card['number']);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        $this->processAndAssertStatusCode($testData, $response);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        self::assertEmpty($this->app['request']->input('account_id'));

        $this->assertBaValues(
            keyId: $client->getId(),
            consumerId: '100000Razorpay',
            publicKey: $publickey,
            authCredsClass: ClientAuthCreds::class,
            accountId: '100000Razorpay',
            isPartnerAuth: true,
            partnerMerchantId: '10000000000000',
            partnerApplicationId: $client->getApplicationId(),
            oauthApplicationId: $client->getApplicationId(),
            authType: 'public'
        );
    }

    public function testPublicOauthWithInternalAuth()
    {
        [$accessToken, $tokenEntity] = $this->generateOAuthAccessTokenForPassport();
        $tokenEntity = $tokenEntity->toArray();

        $this->fixtures->create('user', ['id' => '20000000000000']);
        $this->fixtures->create('payment', ['id' => '10000000000000']);

        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_oauth_TheTestAuthKey', 'public_key' => 'rzp_test_oauth_TheTestAuthKey'];

        $oauth = [
            'app_id' => $tokenEntity['application']['id'],
            'client_id' => $tokenEntity['client_id'],
            'access_token_id' => $tokenEntity['id'],
            'owner_type' => 'merchant',
            'owner_id' => '10000000000000',
            'user_id' => '20000000000000',
            'env' => 'test'
        ];

        $roles = ['oauth::scope::read_only'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, [], $oauth, $roles, true, false);
        $testData = $this->testData['paymentsCreateCheckout'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_checkout', 'rzp_test', $pwd
        );

        $payment = $this->getPaymentCreateCheckoutRequestContent();
        $testData['request']['content'] = $payment;

        $response = $this->makeRequestParent($testData['request']);
        $response->assertViewIs('tokenisation.recurringTokenisationConsentForm');

        $responseContent = $response->getOriginalContent()->getData();
        $card = $this->app['encrypter']->decrypt($responseContent['input']['card']);
        $this->assertEquals($payment['card']['number'], $card['number']);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        $this->processAndAssertStatusCode($testData, $response);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);;
        $this->assertOauthValues( '10000000000000', 'rzp_test_oauth_TheTestAuthKey', '20000000000000', $tokenEntity['id'],
            $tokenEntity['client_id'], $tokenEntity['application']['id'], ['read_only'], '10000000000000', '', 'test', 'public');
    }

    public function testPublicMerchantAuthWithImpersonation()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey-acc_100000Razorpay'];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST,impersonation: $impersonation,identified: true,authenticated: false);
        $testData = $this->testData['testPublicAuth'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_100000Razorpay';

        $this->fixtures->create('merchant_access_map', ['entity_id' => '10000000000000', 'merchant_id' => '100000Razorpay']);
        $this->ba->publicAuth('rzp_test_TheTestAuthKey');
        $response = $this->makeRequestAndGetContent($testData['request']);
        $this->assertArrayHasKey('razorpay_payment_id',$response);
        self::assertEmpty($this->app['request']->input('account_id'));
    }

    public function testPublicMerchantAuthWithImpersonationWithInternalAuth()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey-acc_100000Razorpay'];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $testData = $this->testData['paymentsCreateAjax'];
        $passportJWT = $this->samplePassportJwtBuilder(
            consumer: $consumer,
            credential: $credential,
            mode: Mode::TEST,
            impersonation: $impersonation,
            identified: true,
            authenticated: false
        );
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';
        $testData['request']['server']['HTTP_X-Razorpay-Account'] = 'acc_100000Razorpay';

        $this->fixtures->create('merchant_access_map', ['entity_id' => '10000000000000', 'merchant_id' => '100000Razorpay']);

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_ajax', 'rzp_test', $pwd
        );

        // create payment_create_ajax route related fixtures
        $order = $this->createFixturesPaymentCreateAjax();

        // set request content
        $testData['request']['content'] = $this->getPaymentCreateAjaxRequestContent($order->getId(), $order->getAmount());

        $response = $this->makeRequestParent($testData['request']);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        // TODO: clean this mess
        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
        $this->assertArrayHasKey('payment_id', $response);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        self::assertEmpty($this->app['request']->input('account_id'));

        $this->assertBaValues(
            keyId: 'TheTestAuthKey',
            consumerId: '10000000000000',
            publicKey: 'rzp_test_TheTestAuthKey-acc_100000Razorpay',
            authCredsClass: KeyAuthCreds::class,
            accountId: '100000Razorpay',
            authType: 'public'
        );
    }

    // key active at Edge but expired at API due to sync delays
    public function testMerchantAuthWithKeyExpiredOnAPI()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['badRequestFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['response']['content']['error']['description'] = PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED;
        $testData['response']['status_code'] = 401;

        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() - 12000]);

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheTestAuthKey', null, '', KeyAuthCreds::class);

        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() + 120000]);
    }


    /**
     * testMerchantAuthWithInternalAuthWithAccess
     * allows the request if passport is passed in header and request has valid internal auth credentials
     *
     */
    public function testMerchantAuthWithInternalAuthWithAccess()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');

        $pwd = $pgRouterConfig['secret'];

        /* @var route Route */
        $route = $this->app['api.route'];


        $currentAppConfig = $route::$internalApps['pg_router'];
        $currentInternalAuthWithPassportRoutes = $route::$internalAuthWithPassportRoutes;

        $route::$internalApps['pg_router'] = ['payment_fetch_multiple'];
        $route::$internalAuthWithPassportRoutes[] = 'payment_fetch_multiple';

        $this->ba->basicAuth('rzp_test',$pwd);

        $this->runRequestResponseFlow($testData);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues( 'TheTestAuthKey', '10000000000000', 'rzp_test_TheTestAuthKey', KeyAuthCreds::class);
    }

    /**
     * testMerchantAuthWithKeylessAuthWithAccess
     * allows the request if passport is passed in header and request has valid internal auth credentials
     *
     */
    public function testMerchantAuthWithKeylessAuthWithAccess()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => null, 'public_key' => null];

        $passportJWT = $this->sampleKeylessPassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['validPassportFlowData'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');

        $pwd = $pgRouterConfig['secret'];

        /* @var route Route */
        $route = $this->app['api.route'];


        $currentAppConfig = $route::$internalApps['pg_router'];
        $currentInternalAuthWithPassportRoutes = $route::$internalAuthWithPassportRoutes;

        $route::$internalApps['pg_router'] = ['payment_fetch_multiple'];
        $route::$internalAuthWithPassportRoutes[] = 'payment_fetch_multiple';

        $this->ba->basicAuth('rzp_test', $pwd);

        $this->runRequestResponseFlow($testData);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertKeylessValues('', '10000000000000', '', KeyAuthCreds::class);
    }

    public function testKeylessMerchantAuthWithXEntityID()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => null, 'public_key' => null];

        $testData = $this->testData['paymentsCreateAjax'];

        $passportJWT = $this->sampleKeylessPassportJwtBuilder($consumer, $credential);

        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');
        $pwd = $pgRouterConfig['secret'];

        [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes] = $this->getCurrentInternalAuthConfigs(
            'pg_router', 'payment_create_ajax', 'rzp_test', $pwd
        );

        // create payment_create_ajax route related fixtures
        $order = $this->createFixturesPaymentCreateAjax();

        // set request content
        $testData['request']['content'] = $this->getPaymentCreateAjaxRequestContentWithXEntityID($order->getId(), $order->getAmount());

        $response = $this->makeRequestParent($testData['request']);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        $this->processAndAssertStatusCode($testData, $response);
        $this->processAndAssertResponseData($testData, $response);
        $this->assertArrayHasKey('payment_id', $response);

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        self::assertEmpty($this->app['request']->input('account_id'));

        $this->assertKeylessValues('', '10000000000000', '', KeyAuthCreds::class);
    }

    /**
     * testMerchantAuthWithInternalAuthNoAccess
     * rejects the request if passport is passed in header and request has valid internal auth credentials but app is not listed
     * in $internalAuthWithPassportApps.
     *
     */
    public function testMerchantAuthWithInternalAuthNoAccess()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['inValidAppAuthWithEdgePassport'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');

        $pwd = $pgRouterConfig['secret'];

        $this->ba->basicAuth('rzp_test',$pwd);

        $this->runRequestResponseFlow($testData);
    }


    /**
     * testMerchantAuthWithInternalAuthWithWrongSecret
     * rejects the request if passport is passed in header and request has invalid secret
     *
     */
    public function testMerchantAuthWithInternalAuthWithWrongSecret()
    {
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential);
        $testData = $this->testData['inValidAppAuthWithEdgePassport'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;
        $testData['request']['server']['HTTP_X-PASSPORT-USABLE'] = 'false';

        $pgRouterConfig = \Config::get('applications.pg_router');

        /* @var route Route */
        $route = $this->app['api.route'];


        $currentAppConfig = $route::$internalApps['pg_router'];
        $currentInternalAuthWithPassportRoutes = $route::$internalAuthWithPassportRoutes;

        $route::$internalApps['pg_router'] = ['payment_fetch_multiple'];
        $route::$internalAuthWithPassportRoutes[] = 'payment_fetch_multiple';

        $this->ba->basicAuth('rzp_test','dummy_password');

        $this->runRequestResponseFlow($testData);

        //Phpstorm might show `Static property cannot be unset` but it's possible in php
        $route::$internalApps['pg_router'] = $currentAppConfig;
        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;

        self::assertFalse($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
    }

    // TODO: needs better name
    protected function getCurrentInternalAuthConfigs(string $internalApp, string $passportRoute, string $username, string $pwd)
    {
        /* @var route Route */
        $route = $this->app['api.route'];

        $currentAppConfig = $route::$internalApps[$internalApp];
        $currentInternalAuthWithPassportRoutes = $route::$internalAuthWithPassportRoutes;

        $route::$internalApps['pg_router'] = [$passportRoute];
        $route::$internalAuthWithPassportRoutes[] = $passportRoute;

        $this->ba->basicAuth($username, $pwd);

        return [$route, $currentAppConfig, $currentInternalAuthWithPassportRoutes];
    }

    protected function createFixturesPaymentCreateAjax($mid = '10000000000000')
    {
        $this->fixtures->merchant->addFeatures(FeatureConstants::ONE_CLICK_CHECKOUT, $mid);
        $order = $this->fixtures->order->create(['merchant_id' => $mid, 'receipt' => 'receipt']);
        $this->fixtures->create('order_meta',
            [
                'order_id' => $order->getId(),
                'value'    => $this->getOrderMetaValue(),
                'type'     => 'one_click_checkout',
            ]);
        return $order;
    }

    protected function getPaymentCreateAjaxRequestContent(string $orderId, int $amount)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment["order_id"] = 'order_'.$orderId;
        $payment["amount"] = $amount;
        return $payment;
    }

    protected function getPaymentCreateAjaxRequestContentWithXEntityID(string $orderId, int $amount)
    {
        $payment = $this->getDefaultPaymentArray();
        $payment["order_id"] = 'order_'.$orderId;
        $payment["amount"] = $amount;
        $payment["x_entity_id"] = 'invalid_id';
        return $payment;
    }

    protected function getPaymentCreateCheckoutRequestContent()
    {
        $payment = $this->getDefaultPaymentArray();
        $payment['_']['library'] = 'razorpayjs';
        $payment['recurring'] = '1';
        $payment['method'] = 'card';
        $payment['customer_id'] = 'cust_100000customer';
        return $payment;
    }

    /**
     * meta values used to create fixtures for ajax route (payment)
     * ref: tests/Functional/Payment/OneCcPaymentsTest.php:getOrderMetaValue()
     */
    protected function getOrderMetaValue()
    {
        $app = App::getFacadeRoot();
        $shipping_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'shipping_address',
            'primary'       => true
        ];
        $billing_address = [
            'line1'         => 'some line one',
            'line2'         => 'some line two',
            'city'          => 'Bangalore',
            'state'         => 'Karnataka',
            'zipcode'       => '560001',
            'country'       => 'in',
            'type'          => 'billing_address',
            'primary'       => true
        ];
        $customer = [
            'contact'           =>'+9191111111111',
            'email'             =>'john.doe@razorpay.com',
            'shipping_address'  =>$shipping_address,
            'billing_address'   =>$billing_address
        ];
        return [
            'cod_fee'           => 100000,
            'net_price'         => 1100000,
            'sub_total'         => 1100000,
            'shipping_fee'      => 10000,
            'customer_details'  => $app['encrypter']->encrypt($customer),
            'line_items_total'  => 1000000,
        ];
    }
}
