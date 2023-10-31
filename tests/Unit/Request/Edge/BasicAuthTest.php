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

class BasicAuthTest extends TestCase
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
            ->setMethods(['canModifyPassport','isEzetapApiApp'])
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
            ->setMethods(['info','warning'])
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

    /**
     * testShouldLogWarningOnModificationOfPassportAttributes
     * it checks if PASSPORT_MODIFICATION_NOT_ALLOWED is logged in case of passport alteration is restricted,
     * TODO: we are still allowing modification until the existing integrations are fixed.
     */
    public function testShouldLogWarningOnModificationOfPassportAttributes(){
        // init mocks
        $trace = $this->mockTrace();

        //expectations
        $trace->expects($this->once())
            ->method('warning')
            ->with(
                TraceCode::PASSPORT_MODIFICATION_NOT_ALLOWED,[]
            );

        //run test
        $ba = new BasicAuth($this->app);
        $ba->init();
        $ba->setPassportModificationNotAllowed();
        $ba->setPassportConsumerClaims('merchant','100000000',true);
        $passport = $ba->getPassport();
        self::assertEquals(true,$passport['authenticated']);
        self::assertEquals('100000000',$passport['consumer']['id']);
    }

    /**
     * testShouldNotLogWarningOnModificationOfPassportAttributes
     * it checks if PASSPORT_MODIFICATION_NOT_ALLOWED is not logged in case of passport alteration is restricted
     */
    public function testShouldNotLogWarningOnModificationOfPassportAttributes(){
        // init mocks
        $trace = $this->mockTrace();

        //expectations
        $trace->expects($this->never())
            ->method('warning')
            ->with(
                TraceCode::PASSPORT_MODIFICATION_NOT_ALLOWED,[]
            );

        //run test
        $ba = new BasicAuth($this->app);
        $ba->init();
        $ba->setPassportConsumerClaims('merchant','100000000',true);
        $passport = $ba->getPassport();
        self::assertEquals(true,$passport['authenticated']);
        self::assertEquals('100000000',$passport['consumer']['id']);
    }
}
