<?php

namespace RZP\Tests\Unit\Request\Edge;

use Exception;
use Razorpay\Edge\Passport;
use Razorpay\Edge\Passport\OAuthClaims;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\KeyAuthCreds;
use RZP\Tests\TestCase;
use RZP\Http\Edge\PassportUtil;
use \Mockery;

class PassportUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        restore_error_handler();
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['isPartnerAuth', 'isPartnerAuthAllowed', 'getAccountId', 'setPartnerMerchantId', 'setOAuthApplicationId', 'setPartnerAuth', 'setMerchant'])
            ->getMock();
        $this->app->instance('basicauth', $mock);

        return $mock;
    }

    /**
     * @dataProvider getValidatePassportCases
     *
     * @param Passport\Passport  $passport
     * @param bool               $authenticated
     * @param string             $mode
     * @param string             $consumerType
     * @param bool               $setCredential
     * @param string             $publicKey
     * @param string             $impersonationType
     * @param string             $env
     * @param bool               $expected
     */
    public function testValidatePassport($passport, $authenticated, $mode, $consumerType, $setCredential, $publicKey, $impersonationType, $env, $expected)
    {

        $passport->identified = true;
        $passport->authenticated = $authenticated;
        $passport->mode = $mode;
        $passport->domain = "razorpay";
        $passport->consumer->type = $consumerType;
        $passport->impersonation->type = $impersonationType;
        $passport->oauth->env = $env;
        $passport->credential = ($setCredential === true) ? new Passport\CredentialClaims : null;
        if (! empty($passport->credential)) {
            $passport->credential->username = 'rzp_live_10000000000000';
            $passport->credential->publicKey = $publicKey;
        }

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->validatePassport(), $expected);
    }

    public function getValidatePassportCases(): array
    {
        $passport = new Passport\Passport;

        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = '10000000000000';

        $passport->impersonation = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer = new Passport\ConsumerClaims;
        $passport->impersonation->consumer->id = 'merchant_id';
        $passport->impersonation->consumer->type = 'merchant';

        $passport->oauth = new OauthClaims;
        $passport->oauth->appId = 'oauth_application_id';
        $passport->oauth->clientId = 'oauth_client_id';
        $passport->oauth->accessTokenId = 'oauth_access_token_id';
        $passport->oauth->ownerType = 'merchant';
        $passport->oauth->ownerId = 'oauth_owner_id';
        $passport->oauth->userId = 'oauth_user_id';

        $passport->roles = ['oauth::scope::read_only'];

        // $passport, $authenticated, $mode, $consumerType, $setCredential, $publicKey, $impersonationType, $env, $expected
        return [
            // Case 1: null mode
            [$passport, true, null, 'merchant', true, 'rzp_live_10000000000000', 'partner', 'test', false],
            // Case 2: consumer type missing
            [$passport, true, 'test', null, true, 'rzp_live_10000000000000', 'partner', 'test', false],
            // Case 3: credential public key missing
            [$passport, true, 'test', 'merchant', true, null, 'partner', 'test', false],
            // Case 4: keyless auth
            [$passport, false, 'test', 'merchant', false, null, 'partner', 'test', true],
            // Case 5: public auth credential public key missing
            [$passport, false, 'test', 'merchant', true, null, 'partner', 'test', false],
            // Case 6: impersonation type missing
            [$passport, true, 'test', 'merchant', true, 'rzp_live_10000000000000', null, 'test', false],
            // Case 7: oauth env missing
            [$passport, true, 'test', 'merchant', true, 'rzp_live_10000000000000', 'partner', null, false],
            // Case 8: success case
            [$passport, true, 'test', 'merchant', true, 'rzp_live_10000000000000', 'partner', 'test', true],
        ];
    }

    /**
     * @dataProvider getShouldAuthenticateUsingPassportCases
     *
     * @param Passport\Passport             $passport
     * @param bool                          $authenticated
     * @param bool                          $identified
     * @param string                        $passportUsable
     * @param Passport\ConsumerClaims       $consumer
     * @param Passport\CredentialClaims     $credential
     * @param bool                          $expected
     */
    public function testShouldAuthenticateUsingPassport($passport, $authenticated, $identified, $passportUsable, $consumer, $credential, $expected)
    {
        if (! empty($passport)) {
            $passport->authenticated = $authenticated;
            $passport->identified    = $identified;
            $passport->consumer      = $consumer;
            $passport->credential    = $credential;
        }

        $request = $this->app['request'];
        $request->headers->set('X_PASSPORT_USABLE', $passportUsable);

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->shouldAuthenticateUsingPassport($request), $expected);
    }

    public function getShouldAuthenticateUsingPassportCases(): array
    {
        $passport = new Passport\Passport;
        $passport->mode = 'live';
        $passport->domain = "razorpay";

        $consumer = new Passport\ConsumerClaims;
        $consumer->id = '10000000000000';
        $consumer->type = 'merchant';

        $credential = new Passport\CredentialClaims;
        $credential->username = 'rzp_live_10000000000000';
        $credential->publicKey = 'rzp_live_10000000000000';

        // $passport, $authenticated, $identified, $passportUsable, $consumer, $credential, $expected
        return [
            // Case 1: passport usable false
            [$passport, true, true, false, null, null, false],
            // Case 2: not authenticated and not identified
            [$passport, false, false, true, null, null, false],
            // Case 3: passport validation fails
            [$passport, true, true, true, null, null, false],
            // Case 4: success case private auth
            [$passport, true, true, true, $consumer, $credential, true],
            // Case 4: success case public auth
            [$passport, false, true, true, $consumer, $credential, true],
        ];
    }

    public function testfetchOauthScopes()
    {
        $passport = new Passport\Passport;
        $passport->roles = ['oauth::scope::read_only', 'oauth::scope::read_write'];

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->fetchOauthScopes(), ['read_only', 'read_write']);

        $passport->roles = ['rx::scope::read_only', 'rx::scope::read_write'];

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->fetchOauthScopes(), []);

        $passport->roles = [];

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->fetchOauthScopes(), []);

        $passport->roles = ['oauth::read_only'];

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->fetchOauthScopes(), []);

    }

    public function testGetAccountId()
    {
        $passport = new Passport\Passport;
        $passport->impersonation = null;

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->getAccountId(), '');

        $passport = new Passport\Passport;
        $passport->impersonation = null;

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->getAccountId(), '');

        $passport = new Passport\Passport;
        $passport->impersonation = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer = new Passport\ConsumerClaims;
        $passport->impersonation->consumer->id = '1000000000000';

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->getAccountId(), '1000000000000');
        // removes account id from request
        $this->assertEmpty($this->app['request']->input('account_id'));
    }

    /**
     * @dataProvider getCheckAndSetPartnerMerchantScopeCases
     *
     * @param string        $consumerType
     * @param bool          $isPartnerAuthAllowed
     * @param bool          $setImpersonationClaims
     * @param bool          $activated
     * @param bool          $subMerchantExists
     * @param bool          $expectedNull
     */
    public function testCheckAndSetPartnerMerchantScope($consumerType, $isPartnerAuthAllowed, $setImpersonationClaims, $activated, $subMerchantExists, $expectedNull)
    {
        $ba = $this->mockBasicAuth();
        $authCredsMock = Mockery::mock(KeyAuthCreds::class);
        $merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');
        $ba->authCreds = $authCredsMock;

        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app])->makePartial();
        $merchantRepoMock =  Mockery::mock('\RZP\Models\Merchant\Repository');
        $repoMock->shouldReceive('driver')->with('merchant')->andReturn($merchantRepoMock);
        $merchantRepoMock->shouldReceive('find')->andReturnUsing(function () use ($subMerchantExists, $merchantEntityMock) {
            return $subMerchantExists ? $merchantEntityMock : null;
        });
        $this->app->instance('repo', $repoMock);

        $ba->expects($this->any())->method('isPartnerAuthAllowed')->willReturn($isPartnerAuthAllowed);
        $ba->expects($this->any())->method('setPartnerAuth')->with(false);

        $authCredsMock->shouldReceive('getMerchant')->andReturn($merchantEntityMock);
        $merchantEntityMock->shouldReceive('getId')->andReturn('10000000000000');
        $merchantEntityMock->shouldReceive('isFeatureEnabled')->andReturn(true);
        $authCredsMock->shouldReceive('setPartnerMerchantId')->with('10000000000000');
        $authCredsMock->shouldReceive('fetchPartnerClient')->with('10000000000000');
        $authCredsMock->shouldReceive('getKey')->andReturn('10000000000000');
        $authCredsMock->shouldReceive('getPartnerApplicationId')->andReturn('10000000000000');
        $authCredsMock->shouldReceive('setOAuthApplicationId')->with('10000000000000');
        $authCredsMock->shouldReceive('setMerchant')->atMost(1);
        $authCredsMock->shouldReceive('checkMerchantActivatedForLive')
            ->andReturnUsing(function () use ($activated) {
                if ($activated) {
                    return null;
                }
                throw new Exception();
            });

        $passport = new Passport\Passport;
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = '10000000000000';
        $passport->consumer->type = $consumerType;
        $passport->impersonation = null;
        if ($setImpersonationClaims)
        {
            $passport->impersonation = new Passport\ImpersonationClaims;
            $passport->impersonation->consumer = new Passport\ConsumerClaims;
            $passport->impersonation->consumer->id = '1000000000000';
        }
        $passportUtil = new PassportUtil($passport);

        $ret = $passportUtil->checkAndSetPartnerMerchantScope();
        $expectedNull ? $this->assertNull($ret) : $this->assertNotNull($ret);
    }

    public function getCheckAndSetPartnerMerchantScopeCases(): array
    {
        // $consumerType, $isPartnerAuthAllowed, $accountId, $activated, $subMerchantExists, $expectedNull
        return [
            // Case 1: Not partner auth
            ['merchant', false, '', false, false, true],
            // Case 2: Partner auth not allowed
            ['partner', false, '', false, false, false],
            // Case 3: Account id is empty
            ['partner', true, '', false, false, true],
            // Case 4: account doesnt exists
            ['partner', true, '10000000000000', false, false, false],
            // Case 5: Merchant not activated
            ['partner', true, '10000000000000', false, true, false],
            // Case 6: Merchant activated
            ['partner', true, '10000000000000', true, true, true],
        ];
    }

    /**
     * @dataProvider getAccountAuthCases
     * includes tests for checkAndSetAccountScope too
     *
     * @param bool          $merchantExists
     * @param bool          $isPartner
     * @param bool          $isPurePlatformPartner
     * @param bool          $setImpersonationClaims
     * @param bool          $activated
     * @param bool          $expectedNull
     */
    public function testHandleAccountAuthIfApplicable($merchantExists, $isPartner, $isPurePlatformPartner, $setImpersonationClaims, $activated, $expectedNull)
    {
        $ba = $this->mockBasicAuth();
        $authCredsMock = Mockery::mock(KeyAuthCreds::class);
        $merchantEntityMock = Mockery::mock('RZP\Models\Merchant\Entity');
        $ba->authCreds = $authCredsMock;

        $authCredsMock->shouldReceive('getMerchant')->andReturnUsing(function () use ($merchantExists, $merchantEntityMock) {
            return $merchantExists ? $merchantEntityMock : null;
        });

        $merchantEntityMock->shouldReceive('getId')->andReturn('100000000000');
        $merchantEntityMock->shouldReceive('isFeatureEnabled')->andReturn(true);
        $merchantEntityMock->shouldReceive('isPartner')->andReturn($isPartner);
        $merchantEntityMock->shouldReceive('isPurePlatformPartner')->andReturn($isPurePlatformPartner);
        $authCredsMock->shouldReceive('setPartnerMerchantId')->atMost(1);
        $authCredsMock->shouldReceive('setMerchant')->atMost(1);
        $authCredsMock->shouldReceive('checkMerchantActivatedForLive')
            ->andReturnUsing(function () use ($activated) {
                if ($activated) {
                    return null;
                }
                throw new Exception();
            });

        $passport = new Passport\Passport;
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = '10000000000000';
        $passport->impersonation = null;
        if ($setImpersonationClaims)
        {
            $passport->impersonation = new Passport\ImpersonationClaims;
            $passport->impersonation->consumer = new Passport\ConsumerClaims;
            $passport->impersonation->consumer->id = '1000000000000';
        }
        $passportUtil = new PassportUtil($passport);
        $ret = $passportUtil->handleAccountAuthIfApplicable();
        $expectedNull ? $this->assertNull($ret) : $this->assertNotNull($ret);
    }

    public function getAccountAuthCases(): array
    {
        // $merchantExists, $isPartner, $isPurePlatformPartner, $accountId, $activated, $expectedNull
        return [
            // Case 1: merchant not exists
            [false, false, false, false, false, true],
            // Case 2: not isPartner
            [true, false, false, false, false, true],
            // Case 3: isPurePlatformPartner
            [true, true, true, false, false, true],
            // Case 4: empty account id
            [true, true, false, false, false, true],
            // Case 5: merchant activated
            [true, true, false, true, true, true],
            // Case 6: merchant not activated
            [true, true, false, true, false, false],
        ];
    }

    /**
     * @dataProvider getAuthTypeFromPassportCases
     *
     * @param Passport\Passport                $passport
     * @param bool                             $authenticated
     * @param string                           $consumerType
     * @param Passport\ImpersonationClaims     $impersonation
     * @param Passport\OauthClaims             $oauth
     * @param string                           $expected
     */
    public function testGetAuthTypeFromPassport($passport, $authenticated, $consumerType, $impersonation, $oauth, $credential, $expected)
    {
        $passport->authenticated = $authenticated;
        $passport->identified = true;

        $passport->consumer->type = $consumerType;
        $passport->credential = $credential;
        $passport->impersonation = $impersonation;
        $passport->oauth = $oauth;

        $passportUtil = new PassportUtil($passport);
        $this->assertSame($passportUtil->getAuthTypeFromPassport(), $expected);
    }

    public function getAuthTypeFromPassportCases(): array
    {
        $passport = new Passport\Passport;
        $passport->mode = 'live';

        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = '10000000000000';

        $credential = new Passport\CredentialClaims;
        $credential->username = 'rzp_live_10000000000000';
        $credential->publicKey = 'rzp_live_10000000000000';

        $impersonation = new Passport\ImpersonationClaims;
        $impersonation->consumer = new Passport\ConsumerClaims;
        $impersonation->consumer->id = 'merchant_id';
        $impersonation->consumer->type = 'merchant';
        $impersonation->type = 'partner';

        $oauth = new Passport\OauthClaims;
        $oauth->appId = 'oauth_application_id';
        $oauth->clientId = 'oauth_client_id';
        $oauth->accessTokenId = 'oauth_access_token_id';
        $oauth->ownerType = 'merchant';
        $oauth->ownerId = 'oauth_owner_id';
        $oauth->userId = 'oauth_user_id';
        $oauth->env = 'test';

        // $passport, $consumerType, $impersonation, $oauth, $expected
        return [
            // Case 1: merchant auth
            [$passport, true, 'merchant', null, null, $credential, 'merchant_auth_without_impersonation'],
            // Case 2: partner auth
            [$passport, true, 'partner', null, null, $credential, 'partner_auth_without_impersonation'],
            // Case 3: oauth
            [$passport, true, 'merchant', null, $oauth, $credential, 'oauth_without_impersonation'],
            // Case 4: merchant auth with impersonation
            [$passport, true, 'merchant', $impersonation, null, $credential, 'merchant_auth_with_impersonation'],
            // Case 5: partner auth with impersonation
            [$passport, true, 'partner', $impersonation, null, $credential, 'partner_auth_with_impersonation'],
            // Case 6: oauth with impersonation
            [$passport, true, 'partner', $impersonation, $oauth, $credential, 'oauth_with_impersonation'],
            // Case 7: public merchant auth
            [$passport, false, 'merchant', null, null, $credential, 'public_merchant_auth_without_impersonation'],
            // Case 8: public partner auth
            [$passport, false, 'partner', null, null, $credential, 'public_partner_auth_without_impersonation'],
            // Case 9: public oauth without impersonation
            [$passport, false, 'merchant', null, $oauth, $credential, 'public_oauth_without_impersonation'],
            // Case 10: public partner auth with impersonation
            [$passport, false, 'partner', $impersonation, null, $credential, 'public_partner_auth_with_impersonation'],
            // Case 11: public oauth with impersonation
            [$passport, false, 'partner', $impersonation, $oauth, $credential, 'public_oauth_with_impersonation'],
            // Case 12: keyless auth
            [$passport, false, 'merchant', null, null, null, 'keyless_auth'],
        ];
    }
}
