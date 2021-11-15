<?php

namespace RZP\Tests\Unit\Request\Edge;

use Razorpay\Edge\Passport;

use RZP\Tests\TestCase;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Edge\PostAuthenticate;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;

class PostAuthenticateTest extends TestCase
{
    use HasRequestCases;

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
        if ($expectedAuthenticated === true)
        {
            $this->assertSame($passport->mode, $expectedMode);
        }
        $this->assertSame($passport->consumer !== null, $expectedMerchantId !== null);
        if ($expectedMerchantId !== null)
        {
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
            ->setMethods(['getMode', 'getMerchantId', 'getAuthType', 'isProxyAuth', 'getPartnerMerchantId', 'getOAuthClientId', 'getOAuthApplicationId'])
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
        $reqCtx           = app('request.ctx.v2');

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
        $reqCtx           = app('request.ctx.v2');

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
        $passport                 = new Passport\Passport;
        $passport->identified     = true;
        $passport->authenticated  = true;
        $passport->mode           = "live";
        $passport->consumer       = new Passport\ConsumerClaims;
        $passport->consumer->id   = "partner_id";
        $passport->consumer->type = "merchant";

        $passport->impersonation                    = new Passport\ImpersonationClaims;
        $passport->impersonation->consumer          = new Passport\ConsumerClaims;
        $passport->impersonation->consumer->id      = "merchant_id";
        $passport->impersonation->consumer->type    = "merchant";
        $passport->impersonation->type              = "partner";
        return [
            // Case 1 - Successful case.
            [$passport, $passport->mode, $passport->consumer->id, $passport->impersonation->consumer->id, false],
            // Case 2 - Mismatch partner_id
            [$passport, $passport->mode, 'i_partner_id', $passport->impersonation->consumer->id, true],
            // Case 3 - Mismatch merchant_id
            [$passport, $passport->mode, $passport->consumer->id, 'i_merchant_id', true],
        ];
    }
}
