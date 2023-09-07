<?php

namespace RZP\Tests\Functional\PassportAuth;

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
        $consumer = ['id' => '10000000000000', 'type' => 'merchant'];
        $credential = ['username' => 'rzp_test_TheTestAuthKey', 'public_key' => 'rzp_test_TheTestAuthKey-acc_100000Razorpay'];
        $impersonation = ['consumer' => ['id' => '100000Razorpay', 'type' => 'merchant'], 'type' => 'partner'];

        $passportJWT = $this->samplePassportJwtBuilder($consumer, $credential, Mode::TEST, $impersonation);
        $testData = $this->testData['testMerchantAuthWithImpersonationCanSkipWorkflow'];
        $testData['request']['server']['HTTP_X-Passport-JWT-V1'] = $passportJWT;

        $this->fixtures->merchant->addFeatures(['marketplace', 'partner_sub_kyc_access']);
        $this->fixtures->create('merchant_access_map', ['entity_id' => '10000000000000', 'merchant_id' => '100000Razorpay']);

        $this->fixtures->create(
            'merchant_application',
            [
                'merchant_id' => '100000Razorpay',
                'application_id' => '10000000000000',
                'type'   => 'referred'
            ]
        );

        $this->fixtures->create('merchant_detail', ['merchant_id' => '100000Razorpay', 'activation_form_milestone' => 'L2']);

        $this->runRequestResponseFlow($testData);
        self::assertTrue($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport);
        $this->assertBaValues('TheTestAuthKey', '100000Razorpay', 'rzp_test_TheTestAuthKey-acc_100000Razorpay', KeyAuthCreds::class, '100000Razorpay');
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

}
