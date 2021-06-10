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
     * @param  Passport\Passport|null $passport
     * @param  boolean|null           $expectedAuthenticated
     * @param  string|null            $expectedMode
     * @param  string|null            $expectedMerchantId
     * @param  string|null            $expectedAuth
     * @param  boolean|null           $expectedProxy
     * @param  boolean|null           $expectPassportAttrsMismatch
     * @return void
     *
     * @dataProvider getRequestCases
     */
    public function testHandleWhenNoPassport(
        $passport,
        $expectedAuthenticated,
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
        $ba->expects($this->atLeastOnce())->method('getMode')->willReturn($expectedMode);
        $ba->expects($this->atLeastOnce())->method('getMerchantId')->willReturn($expectedMerchantId);
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
        $this->assertSame($passport->identified, $expectedAuthenticated);
        $this->assertSame($passport->authenticated, $expectedAuthenticated);
        $this->assertSame($passport->mode, $expectedMode);
        $this->assertSame($passport->merchant !== null, $expectedMerchantId !== null);
        if ($expectedMerchantId !== null)
        {
            $this->assertSame($passport->merchant->id, $expectedMerchantId);
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
        $passport3->merchant = new Passport\Merchant;
        $passport3->merchant->id = '10000000000000';

        $passport4 = clone $passport3;

        $passport5 = clone $passport3;
        $passport5->mode = 'test';

        $passport6 = clone $passport3;
        $passport6->merchant = null;

        // Returns list of [passport, expectedAuthenticated, expectedMode, expectedMerchant, expectedAuth, expectedProxy, expectPassportAttrsMismatch].
        return [
            // Case 1: Private route.
            [null, true, 'live', '10000000000000', 'private', false, false],
            // Case 2: Direct route.
            [null, false, null, null, 'direct', false, false],
            // Case 3: Direct route and invalid passport comes form edge.
            [$passport3, false, null, null, 'direct', false, true],
            // Case 4: Privat route and passport comes from edge.
            [$passport4, true, 'live', '10000000000000', 'private', false, false],
            // Case 5: Privat route and invalid passport comes from edge.
            [$passport5, true, 'live', '10000000000000', 'private', false, true],
            // Case 6: Privat route and invalid passport comes from edge.
            [$passport6, true, 'live', '10000000000000', 'private', false, true],
        ];
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getMode', 'getMerchantId', 'getAuthType', 'isProxyAuth'])
            ->getMock();
        $this->app->instance('basicauth', $mock);

        return $mock;
    }
}
