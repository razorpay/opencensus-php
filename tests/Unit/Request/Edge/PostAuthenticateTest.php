<?php

namespace RZP\Tests\Unit\Request\Edge;

use Razorpay\Edge\Passport;

use RZP\Tests\TestCase;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Edge\PostAuthenticate;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;
use \Mockery;
use RZP\Trace\TraceCode;

class PostAuthenticateTest extends TestCase
{
    use HasRequestCases;

    protected function setUp(): void
    {
        parent::setUp();
        restore_error_handler();
    }

    /**
     * @param Passport\Passport|null $passport
     * @param boolean|null $expectedAuthenticated
     * @param boolean|null $expectedIdentified
     * @param string|null $expectedMode
     * @param string|null $expectedMerchantId
     * @param string|null $expectedAuth
     * @param boolean|null $expectedProxy
     * @param boolean|null $expectPassportAttrsMismatch
     * @return void
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     * @dataProvider getRequestCases
     */
    public function testHandleWhenNoPassport(
        $passport,
        $expectedAuthenticated,
        $expectedIdentified,
        $expectedMode,
        $expectedMerchantId,
        $expectedAuth,
        $expectedProxy,
        $expectPassportAttrsMismatch)
    {
        $request = $this->mockPrivateRoute();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');

        // Sets up request.ctx.v2's passport which could be null i.e. not
        // received from edge service.
        $reqCtx->passport = $passport;

        // Sets up basic auth expectations.
        $ba = $this->mockBasicAuth();
        $ba->expects($this->any())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->any())->method('getMerchantId')->willReturn($expectedMerchantId);
        $ba->expects($this->atLeastOnce())->method('getAuthType')->willReturn($expectedAuth);
        $ba->expects($this->atLeastOnce())->method('isProxyAuth')->willReturn($expectedProxy);
        $ba->expects($this->once())->method('setPassportDomain')->with($this->equalTo('razorpay'));

        // Asserts that no exceptions are thrown.
        (new PostAuthenticate)->handle($expectedAuthenticated, $request);
        // Asserts request.ctx.v2.
        $reqCtx = app('request.ctx.v2');
        $this->assertSame($reqCtx->authType, $expectedAuth);
        $this->assertSame($reqCtx->proxy, $expectedProxy);
        // Asserts passport.
        $passport = $reqCtx->passport;
        $this->assertNotNull($passport);
        $this->assertSame($passport->identified, $expectedIdentified);
        $this->assertSame($passport->authenticated, $expectedAuthenticated);
        if ($expectedAuthenticated === true) {
            $this->assertSame($passport->mode, $expectedMode);
        }
        $this->assertSame($passport->consumer !== null, $expectedMerchantId !== null);
        if ($expectedMerchantId !== null) {
            $this->assertSame($passport->consumer->id, $expectedMerchantId);
            $this->assertSame($passport->consumer->type, 'merchant');
        }
        // Asserts if mismatch errors were expected and logged.
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectPassportAttrsMismatch);
    }

    public function getRequestCases(): array
    {
        // These are different passports for each request cases below.
        $passport3 = new Passport\Passport;
        $passport3->identified = true;
        $passport3->authenticated = true;
        $passport3->mode = 'live';
        $passport3->consumer = new Passport\ConsumerClaims;
        $passport3->consumer->id = '10000000000000';
        $passport3->consumer->type = 'merchant';
        $passport3->domain = 'razorpay';

        $passport4 = clone $passport3;

        $passport5 = clone $passport3;
        $passport5->mode = 'test';

        $passport6 = clone $passport3;
        $passport6->consumer = null;

        // Returns list of [passport, expectedAuthenticated, expectedMode, expectedMerchant, expectedAuth, expectedProxy, expectPassportAttrsMismatch].
        return [
            // Case 1: Private route.
            [null, true, true, 'live', '10000000000000', 'private', false, false],
            // Case 2: Direct route.
            [null, false, false, null, null, 'direct', false, false],
            // Case 3: Direct route and invalid passport comes form edge.
            [$passport3, false, false, null, null, 'direct', false, true],
            // Case 4: Privat route and passport comes from edge.
            [$passport4, true, true, 'live', '10000000000000', 'private', false, false],
            // Case 5: Privat route and invalid passport comes from edge.
            [$passport5, true, true, 'live', '10000000000000', 'private', false, true],
            // Case 6: Privat route and invalid passport comes from edge.
            [$passport6, true, true, 'live', '10000000000000', 'private', false, true],
        ];
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getMode', 'getMerchantId', 'getAuthType', 'isProxyAuth',
                'getPartnerMerchantId', 'getOAuthClientId', 'getOAuthApplicationId',
                'getPublicKey', 'getPassport', 'setPassportDomain'])
            ->getMock();
        $this->app->instance('basicauth', $mock);

        return $mock;
    }

    /**
     * @dataProvider getOAuthCases
     *
     * @param Passport\Passport $passport
     * @param                   $expectedMode
     * @param                   $expectedOAuthClientId
     * @param                   $expectedOAuthApplicationId
     * @param                   $expectedPartnerMerchantId
     * @param                   $expectedMerchantId
     * @param                   $expectedMismatch
     */
    public function testBearerAuth(Passport\Passport $passport,
                                                     $expectedMode,
                                                     $expectedOAuthClientId,
                                                     $expectedOAuthApplicationId,
                                                     $expectedPartnerMerchantId,
                                                     $expectedMerchantId,
                                                     $expectedMismatch)
    {
        $request = $this->mockPrivateRouteWithOAuthBearerToken();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');

        $ba = $this->mockBasicAuth();
        $ba->expects($this->atLeastOnce())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->atLeastOnce())->method('getOAuthClientId')->willReturn($expectedOAuthClientId);
        $ba->expects($this->atLeastOnce())->method('getOAuthApplicationId')->willReturn($expectedOAuthApplicationId);
        $ba->expects($this->atLeastOnce())->method('getPartnerMerchantId')->willReturn($expectedPartnerMerchantId);
        $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($expectedMerchantId);


        $reqCtx->passport = $passport;

        (new PostAuthenticate)->handle(true, $request);
        $this->assertSame($passport->mode, $expectedMode);
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectedMismatch);
    }

    public function getOAuthCases()
    {
        $passport                 = new Passport\Passport;
        $passport->identified     = true;
        $passport->authenticated  = true;
        $passport->mode           = "live";
        $passport->consumer       = new Passport\ConsumerClaims;
        $passport->consumer->id   = "partner_id";
        $passport->consumer->type = "merchant";

        $passport->oauth            = new Passport\OAuthClaims;
        $passport->oauth->ownerId   = "merchant_id";
        $passport->oauth->ownerType = "merchant";
        $passport->oauth->clientId  = "client_id";
        $passport->oauth->appId     = "app_id";
        return [
            // Case 1 - Successful case.
            [$passport, $passport->mode, $passport->oauth->clientId, $passport->oauth->appId, $passport->consumer->id, $passport->oauth->ownerId, false],
            // Case 2 - Mismatch client_id
            [$passport, $passport->mode, 'i_client_id', $passport->oauth->appId, $passport->consumer->id, $passport->oauth->ownerId, true],
            // Case 3 - Mismatch app_id
            [$passport, $passport->mode, $passport->oauth->clientId, 'i_app_id', $passport->consumer->id, $passport->oauth->ownerId, true],
            // Case 4 - Mismatch partner_id
            [$passport, $passport->mode, $passport->oauth->clientId, $passport->oauth->appId, 'i_partner_id', $passport->oauth->ownerId, true],
            // Case 5 - Mismatch merchant_id
            [$passport, $passport->mode, $passport->oauth->clientId, $passport->oauth->appId, $passport->consumer->id, 'i_merchant_id', true],
        ];
    }

    /**
     * @dataProvider getPartnerAuthCases
     *
     * @param Passport\Passport $passport
     * @param                   $expectedMode
     * @param                   $expectedPartnerMerchantId
     * @param                   $expectedMerchantId
     * @param                   $expectedMismatch
     */
    public function testPartnerAuth(Passport\Passport $passport,
                                                      $expectedMode,
                                                      $expectedPartnerMerchantId,
                                                      $expectedMerchantId,
                                                      $expectedMismatch)
    {
        $request = $this->mockPrivateRouteWithPartnerAuthToken();
        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');

        $ba = $this->mockBasicAuth();
        $ba->expects($this->atLeastOnce())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->atLeastOnce())->method('getPartnerMerchantId')->willReturn($expectedPartnerMerchantId);
        $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($expectedMerchantId);


        $reqCtx->passport = $passport;

        (new PostAuthenticate)->handle(true, $request);
        $this->assertSame($passport->mode, $expectedMode);
        $this->assertSame($reqCtx->passportAttrsMismatch, $expectedMismatch);
    }

    public function getPartnerAuthCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->authenticated = true;
        $passport->mode = "live";
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->consumer->id = "partner_id";
        $passport->consumer->type = "merchant";

        $passport->impersonation = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer = new Passport\ConsumerClaims;
        $passport->impersonation->consumer->id = "merchant_id";
        $passport->impersonation->consumer->type = "merchant";
        $passport->impersonation->type = "partner";
        return [
            // Case 1 - Successful case.
            [$passport, $passport->mode, $passport->consumer->id, $passport->impersonation->consumer->id, false],
            // Case 2 - Mismatch partner_id
            [$passport, $passport->mode, 'i_partner_id', $passport->impersonation->consumer->id, true],
            // Case 3 - Mismatch merchant_id
            [$passport, $passport->mode, $passport->consumer->id, 'i_merchant_id', true],
        ];
    }

    /**
     * @dataProvider getAuthenticationMismatchesCases
     *
     * @param Passport\Passport $passport
     * @param                   $consumerId
     * @param                   $edgeAuthenticated
     * @param                   $consumerType
     * @param                   $publicKey
     * @param                   $apiAuthenticated
     * @param                   $mode
     * @param                   $isAuthenticatedPathRoute
     * @param string            $routeName
     */
    public function testAuthenticationMismatches(Passport\Passport $passport,
                                                                   $consumerId,
                                                                   $consumerType,
                                                                   $publicKey,
                                                                   $mode,
                                                                   $edgeAuthenticated,
                                                                   $apiAuthenticated,
                                                                   $isAuthenticatedPathRoute,
                                                 string            $routeName = 'invoice_fetch_multiple')
    {

        $edgeAuthenticatedBool = $edgeAuthenticated === 'true';
        $passport->mode = $mode;
        if ($consumerType !== NULL || $consumerId !== NULL) {
            $passport->consumer = new Passport\ConsumerClaims;
            $passport->consumer->type = $consumerType;
            $passport->consumer->id = $consumerId;
        }

        if ($publicKey !== NULL) {
            $passport->credential = new Passport\CredentialClaims;
            $passport->credential->username = $publicKey;
        }

        $passport->authenticated = $edgeAuthenticatedBool;
        $passport->identified = true;

        if ($isAuthenticatedPathRoute) {
            $request = $this->mockPrivateRouteWithLiveMode($routeName);
        } else {
            $request = $this->mockPublicRouteWithKeyInHeaders();
        }

        $request->headers->set('X-AUTHENTICATION-RESULT', $edgeAuthenticated);

        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();

        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $svc = Mockery::mock('Razorpay\Trace\Logger');

        $this->app->instance("trace", $svc);

        $ba = $this->mockBasicAuth();

        $svc->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $svc->shouldReceive('error')->times(0);

        if ($isAuthenticatedPathRoute && $edgeAuthenticated !== NULL) {
            $count = $edgeAuthenticatedBool !== $apiAuthenticated ? 1 : 0;
            $svc->shouldReceive('warning')->times($count);

            $ba->expects($this->any())->method('getMode')->willReturn($mode);

            if ($edgeAuthenticatedBool !== $apiAuthenticated) {
                $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($consumerId);
                $ba->expects($this->atLeastOnce())->method('getPublicKey')->willReturn($publicKey);
            }
        }

        (new PostAuthenticate)->handle($apiAuthenticated, $request);
        $this->assertSame($passport->mode, "live");
        Mockery::close();
    }

    /**
     * @dataProvider getImpersonationMismatchesCases
     *
     * @param Passport\Passport $passport
     * @param                   $consumerId
     * @param                   $consumerType
     * @param                   $publicKey
     * @param                   $mode
     * @param                   $edgeImpersonated
     * @param                   $apiAuthenticated
     * @param                   $impersonationType
     * @param                   $subMerchant
     * @param string $routeName
     */
    public function testImpersonationMismatchesWithImpersonation(Passport\Passport $passport,
                                                                                   $consumerId, $consumerType,
                                                                                   $publicKey, $mode,
                                                                                   $edgeImpersonated, $apiAuthenticated,
                                                                                   $impersonationType, $subMerchant)
    {

        $edgeImpersonatedBool = $edgeImpersonated === 'true';
        $passport->mode = $mode;
        $passport->consumer->id = $consumerId;
        $passport->consumer->type = $consumerType;
        $passport->credential->username = $publicKey;
        $passport->impersonation->type = $impersonationType;
        $passport->impersonation->consumer->id = $subMerchant;
        $passport->impersonation->consumer->type = $consumerType;
        $passport->authenticated = $edgeImpersonatedBool;
        $passport->identified = true;

        $request = $this->mockPrivateRouteWithLiveMode();

        $request->headers->set('X-IMPERSONATION-RESULT', $edgeImpersonated);

        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();

        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $svc = Mockery::mock('Razorpay\Trace\Logger');

        $svc->shouldReceive('histogram')->times(1);

        $svc->shouldReceive('error')->times(0);

        $ba = $this->mockBasicAuth();


        $count = $edgeImpersonatedBool !== $apiAuthenticated ? 1 : 0;

        $svc->shouldReceive('warning')->times($count);

        $ba->expects($this->any())->method('getMode')->willReturn($mode);

        if ($edgeImpersonatedBool !== $apiAuthenticated) {
            $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($consumerId);
            $ba->expects($this->atLeastOnce())->method('getPublicKey')->willReturn($publicKey);
            $ba->expects($this->any())->method('getPassport')->willReturn([
                'impersonation' => [
                    'type' => $impersonationType,
                    'consumer' => [
                        'type' => $consumerType,
                        'id' => $subMerchant
                    ]
                ]
            ]);
        }


        $this->app->instance("trace", $svc);

        (new PostAuthenticate)->handle($apiAuthenticated, $request);
        $this->assertSame($passport->mode, "live");
        Mockery::close();
    }

    /**
     * @dataProvider getImpersonationMismatchesCases
     *
     * @param Passport\Passport $passport
     * @param                   $consumerId
     * @param                   $consumerType
     * @param                   $publicKey
     * @param                   $mode
     * @param                   $edgeImpersonated
     * @param                   $apiAuthenticated
     * @param                   $impersonationType
     * @param                   $subMerchant
     */
    public function testImpersonationMismatchesWithoutImpersonation(Passport\Passport $passport,
                                                                                      $consumerId, $consumerType,
                                                                                      $publicKey, $mode,
                                                                                      $edgeImpersonated, $apiAuthenticated,
                                                                                      $impersonationType, $subMerchant)
    {

        $edgeImpersonatedBool = $edgeImpersonated === 'true';
        $passport->mode = $mode;
        $passport->consumer->id = $consumerId;
        $passport->consumer->type = $consumerType;
        $passport->credential->username = $publicKey;
        $passport->impersonation->type = $impersonationType;
        $passport->impersonation->consumer->id = $subMerchant;
        $passport->impersonation->consumer->type = $consumerType;
        $passport->authenticated = $edgeImpersonatedBool;
        $passport->identified = true;

        $request = $this->mockPrivateRouteWithLiveMode();

        $request->headers->set('X-IMPERSONATION-RESULT', $edgeImpersonated);

        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();

        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport = $passport;

        $svc = Mockery::mock('Razorpay\Trace\Logger');


        $count = $edgeImpersonatedBool !== $apiAuthenticated ? 1 : 0;

        $svc->shouldReceive('warning')->times($count);

        $svc->shouldReceive('histogram')->times(1);

        $svc->shouldReceive('error')->times(0);

        $this->app->instance("trace", $svc);

        $ba = $this->mockBasicAuth();

        $ba->expects($this->any())->method('getMode')->willReturn($mode);

        if ($edgeImpersonatedBool !== $apiAuthenticated) {
            $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($consumerId);
            $ba->expects($this->atLeastOnce())->method('getPublicKey')->willReturn($publicKey);
            $ba->expects($this->any())->method('getPassport')->willReturn([]);
        }


        (new PostAuthenticate)->handle($apiAuthenticated, $request);
        $this->assertSame($passport->mode, "live");
        Mockery::close();
    }

    public function getImpersonationMismatchesCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->consumer = new Passport\ConsumerClaims;
        $passport->credential = new Passport\CredentialClaims;
        $passport->impersonation = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer = new Passport\ConsumerClaims;
        return [
            // Case 1 - Successful case.
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "true", false, "partner", "account_id"],
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "false", true, "partner", "account_id"],
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "true", true, "partner", "account_id"],
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "false", false, "partner", "account_id"],
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", "true", false, "partner", "account_id"],
            [$passport, "merchant_id", "merchant", "rzp_live_partner_TheLiveAuthKey", "live", NULL, false, "partner", "account_id"],
            [$passport, NULL, NULL, "rzp_live_partner_TheLiveAuthKey", "live", NULL, false, "partner", "account_id"],
            [$passport, NULL, NULL, NULL, "live", NULL, false, "partner", "account_id"]
        ];
    }

    public function getAuthenticationMismatchesCases()
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        return [
            // Case 1 - Successful case.
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "true", false, true],
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "false", true, true],
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "true", true, true, "payouts_summary"],
            [$passport, "merchant_id", "merchant", "rzp_live_TheLiveAuthKey", "live", "false", false, true, "payouts_summary"],
            [$passport, NULL, NULL, "rzp_live_TheLiveAuthKey", "live", "true", false, true],
            [$passport, NULL, NULL, NULL, "live", "true", false, true],
            [$passport, NULL, NULL, NULL, "live", "true", false, false],
            [$passport, NULL, NULL, NULL, "live", NULL, false, false],
        ];
    }
    
    /**
     * @dataProvider getLogsPassportMismatchMetricsCases
     *
     * @param bool $private
     * @param bool $public
     * @param bool $oauth
     * @param bool $partner
     */
    public function logsPassportMismatchMetrics(bool $private, bool $public, bool $oauth, bool $partner)
    {
        $passport = new Passport\Passport;
        $passport->identified = true;
        $passport->authenticated = true;
        $passport->mode = "live";
        
        $request = $this->mockPrivateRouteWithPartnerAuthToken();
        app('request.ctx')->init();
        app('request.ctx')->resolveKeyIdIfApplicable();
        $reqCtx = app('request.ctx.v2');

        $trace = Mockery::mock('Razorpay\Trace\Logger');
        $this->app->instance("trace", $trace);

        $ba = $this->mockBasicAuth();
        $ba->expects($this->once())->method('getMode')->willReturn("test");
        $ba->expects($this->once())->method('getMerchantId')->willReturn("testmerchantid");
        $ba->expects($this->exactly(2))->method('getRequestMetricDimensions')->willReturn([]);
        $ba->expects($this->once())->method('getPublicKey')->willReturn('testpublickey');

        $reqCtx->passport = $passport;

        $pa = new PostAuthenticate;
        $pa->expects($this->once())->method('ensureRequestContextAdditionalAttrs')->with($request)->willReturn(null);
        $pa->expects($this->once())->method('reportAuthenticationMismatches')->with($passport->authenticated, $request)->willReturn(null);
        $pa->expects($this->once())->method('reportImpersonationMismatches')->with($passport->authenticated, $request)->willReturn(null);
        $pa->expects($this->once())->method('reportAuthorizationEnforcementMismatches')->with($passport->authenticated, $request)->willReturn(null);
        $pa->expects($this->once())->method('updateAPIPassport')->willReturn(null);

        $pa->expects($this->once())->method('isPrivateAuth')->willReturn($private);
        $pa->expects($this->once())->method('isOAuth')->willReturn($oauth);
        $pa->expects($this->once())->method('isPublicAuth')->willReturn($public);
        $pa->expects($this->once())->method('isPartnerAuth')->willReturn($partner);

        $svc->shouldReceive('histogram')->times(1);
        //there shouldn't be any exceptions
        $svc->shouldReceive('error')->times(0);
        $svc->shouldReceive('count')->times(1);
        $svc->shouldReceive('warning')->with(TraceCode::PASSPORT_ATTRS_MISMATCH, Mockery::type('array'));

        $pa->handle(true, $request); 
    }
    
    public function getLogsPassportMismatchMetricsCases()
    {
        return [
            // Case 1 - private auth
            [true, false, false, false],
            // Case 2 - public auth
            [false, true, false, false],
            // Case 3 - oauth
            [false, false, true, false],
            // Case 4 - partner auth
            [false, false, false, true]
        ];
    }
}
