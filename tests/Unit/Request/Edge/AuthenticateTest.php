<?php

namespace RZP\Tests\Unit\Request\Edge;

use Exception;
use Razorpay\Edge\Passport;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\Middleware\Authenticate;
use RZP\Http\Route;
use RZP\Tests\Functional\Helpers\Edge\PassportTrait;
use RZP\Tests\TestCase;
use \Mockery;
use RZP\Tests\Unit\Request\Traits\HasRequestCases;
use RZP\Trace\TraceCode;

class AuthenticateTest extends TestCase
{

    use HasRequestCases;
    use PassportTrait;

    protected function setUp(): void
    {
        parent::setUp();
        restore_error_handler();
    }

    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['verifyInternalApp','isEzetapApiApp'])
            ->getMock();
        $this->app->instance('basicauth', $mock);

        return $mock;
    }

    protected function mockRoute()
    {
        $mock = $this->getMockBuilder(Route::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['isInternalAuthWithPassportRoutes'])
            ->getMock();
        $this->app->instance('api.route', $mock);

        return $mock;
    }

    protected function mockTrace()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }


    /**
     * testShouldUsePassportWithInternalAuthWithNoRouteMatch
     * it returns false if route is not part of $internalAuthWithPassportRoutes
     */
    public function testShouldUsePassportWithInternalAuthWithNoRouteMatch(){
        // init mocks
        $request = $this->mockPrivateRouteWithLiveMode();
        app('request.ctx')->init();
        $route = $this->app['api.route'];
        $currentInternalAuthWithPassportRoutes = $route::$internalAuthWithPassportRoutes;
        $route::$internalAuthWithPassportRoutes[] = 'invoice_fetch_multiple';
        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        $route::$internalAuthWithPassportRoutes = $currentInternalAuthWithPassportRoutes;
        //do assertions
        self::assertFalse($shouldUsePassport);

    }


    /**
     * testShouldUsePassportWithInternalAuthWithNoEdgePassport
     * it returns false if passport isn't set in context.
     */
    public function testShouldUsePassportWithInternalAuthWithNoEdgePassport(){
        // init mocks
        $request = $this->mockPrivilegeRouteWithInternalAppAuth();
        app('request.ctx')->init();
        $route = $this->mockRoute();
        $trace = $this->mockTrace();


        //mock expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);
        $trace->expects($this->once())
            ->method('info')
            ->with(
                TraceCode::INTERNAL_AUTH_PASSPORT_CHECKS_FAILED
            );

        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertFalse($shouldUsePassport);
    }

    /**
     * testShouldUsePassportWithInternalAuthWithNoKidSecret
     * it returns false if not username or password provided in authorization header.
     */
    public function testShouldUsePassportWithInternalAuthWithNoKidSecret(){
        // init mocks
        $request = $this->mockPrivateRouteWithOAuthBearerToken();
        $route = $this->mockRoute();
        app('request.ctx')->init();


        //mock expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);

        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertFalse($shouldUsePassport);
    }

    /**
     * testShouldUsePassportWithInternalAuthWithInvalidPassport
     * returns false if edge passport is not valid.
     *
     */
    public function testShouldUsePassportWithInternalAuthWithInvalidPassport(){
        // init mocks
        $route = $this->mockRoute();
        $trace = $this->mockTrace();
        $request = $this->mockPrivilegeRouteWithInternalAppAuth();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport  = $this->getDummyMerchantAuthPassport();
        $reqCtx->passport->identified = false;
        $reqCtx->hasPassportJwt = true;

        //add expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);
        $trace->expects($this->once())
            ->method('info')
            ->with(
                TraceCode::INTERNAL_AUTH_PASSPORT_CHECKS_FAILED
            );


        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertFalse($shouldUsePassport);
    }


    /**
     * testShouldUsePassportWithInternalAuthWithInvalidMode
     * returns false if request mode and passport mode doesn't match
     *
     */
    public function testShouldUsePassportWithInternalAuthWithInvalidMode(){
        // init mocks
        $route = $this->mockRoute();
        $trace = $this->mockTrace();
        $request = $this->mockPrivilegeRouteWithInternalAppAuth();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport  = $this->getDummyMerchantAuthPassport();
        $reqCtx->passport->mode = 'live';
        $reqCtx->hasPassportJwt = true;

        //add expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);
        $trace->expects($this->once())
            ->method('info')
            ->with(
                TraceCode::INTERNAL_AUTH_PASSPORT_MODE_MISMATCH,
                [
                    'mode'     => 'live',
                    'username' => 'rzp_test',
                ]
            );


        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertFalse($shouldUsePassport);
    }


    /**
     * testShouldUsePassportWithInternalAuthWithInvalidUsername
     * it returns false if username used is not valid internal auth username
     *
     */
    public function testShouldUsePassportWithInternalAuthWithInvalidUsername(){
        // init mocks
        // username with `rzp_live_{mid}`.
        $request = $this->mockPrivateRoute();
        $route = $this->mockRoute();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->hasPassportJwt = true;

        //set expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);

        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertFalse($shouldUsePassport);
    }

    /**
     * testShouldUsePassportWithInternalAuthWithInvalidInternalAppVerificationFailure
     * it returns false if app verification fails
     */
    public function testShouldUsePassportWithInternalAuthWithInvalidInternalAppVerificationFailure(){
        // init mocks
        $ba = $this->mockBasicAuth();
        $route = $this->mockRoute();
        $trace = $this->mockTrace();
        //this sets invalid username rzp_live_{mid}
        $request = $this->mockPrivilegeRouteWithInternalAppAuth();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport  = $this->getDummyMerchantAuthPassport();
        $reqCtx->passport->mode = 'test';
        $reqCtx->hasPassportJwt = true;

        //set expectations
        //set expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);
        $ba->expects($this->once())->method('verifyInternalApp')->willReturn(false);
        $trace->expects($this->once())
            ->method('info')
            ->with(
                TraceCode::INTERNAL_AUTH_PASSPORT_APP_VERIFICATION_FAILED
            );

        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertFalse($shouldUsePassport);
    }

    /**
     * testShouldUsePassportWithInternalAuthFailWithEzTap
     * it returns false if app is eztap
     */
    public function testShouldUsePassportWithInternalAuthFailWithEzTap(){
        // init mocks
        $ba = $this->mockBasicAuth();
        $route = $this->mockRoute();
        $trace = $this->mockTrace();
        //this sets invalid username rzp_live_{mid}
        $request = $this->mockPrivilegeRouteWithInternalAppAuth();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport  = $this->getDummyMerchantAuthPassport();
        $reqCtx->passport->mode = 'test';
        $reqCtx->hasPassportJwt = true;

        //set expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);
        $ba->expects($this->once())->method('verifyInternalApp')->willReturn(true);
        $ba->expects($this->any())->method('isEzetapApiApp')->willReturn(true);

        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertFalse($shouldUsePassport);
    }


    /**
     * testShouldUsePassportWithInternalAuthWithSuccess
     * it returns true if all condition passes
     */
    public function testShouldUsePassportWithInternalAuthWithSuccess(){
        // init mocks
        $ba = $this->mockBasicAuth();
        $route = $this->mockRoute();
        $trace = $this->mockTrace();
        //this sets invalid username rzp_live_{mid}
        $request = $this->mockPrivilegeRouteWithInternalAppAuth();
        app('request.ctx')->init();
        $reqCtx = app('request.ctx.v2');
        $reqCtx->passport  = $this->getDummyMerchantAuthPassport();
        $reqCtx->passport->mode = 'test';
        $reqCtx->hasPassportJwt = true;

        //set expectations
        $route->expects($this->once())->method('isInternalAuthWithPassportRoutes')->willReturn(true);
        $ba->expects($this->once())->method('verifyInternalApp')->willReturn(true);

        //run test
        $authenticate = new Authenticate($this->app);

        $shouldUsePassport = $authenticate->shouldUsePassportWithInternalAuth('dummy_route', $request);

        //do assertions
        self::assertTrue($shouldUsePassport);
    }
}
