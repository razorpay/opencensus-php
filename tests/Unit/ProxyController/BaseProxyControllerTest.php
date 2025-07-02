<?php

namespace RZP\Tests\Unit\ProxyController;

use Illuminate\Http\Request;
use Mockery;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Http\Controllers\BaseProxyController;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;

class BaseProxyControllerTest extends TestCase
{
    use WorkflowTrait;

    public function testHandleAdminProxyRequestsBadRequest()
    {
        $stub = $this->getMockBuilder(BaseProxyController::class)
            ->setConstructorArgs(['cmma'])
            ->getMock();

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);

        $this->expectException('\RZP\Exception\BadRequestException');

        $stub->handleAdminProxyRequests('/twirp/rzp.example.user.v1.UserAPI/List');
    }

    public function testHandleAdminProxyRequestsNoAccess()
    {
        $stub = $this->getMockBuilder(BaseProxyController::class)
            ->setConstructorArgs(['cmma'])
            ->enableOriginalConstructor()
            ->enableOriginalClone()
            ->onlyMethods(['getRequestInstance'])
            ->getMock();


        $this->callMethod($stub, 'registerRoutesMap', [['GetUserList' => "/twirp\/rzp.example.user.v1.UserAPI\/List/"]]);

        $this->ba->adminAuth();;

        $this->defineVariable($stub, 'ba', $this->ba);

        $this->callMethod($stub, 'registerAdminRoutes', [["GetUserList"], ['GetUserList' => 'process_view']]);

        $this->expectExceptionCode(ErrorCode::BAD_REQUEST_ACCESS_DENIED);

        $request = new Request();
        $request->setMethod('POST');

        $stub->expects($this->exactly(1))
             ->method('getRequestInstance')
             ->willReturn($request);

        $this->expectException('\RZP\Exception\BadRequestException');

        $stub->handleAdminProxyRequests('/twirp/rzp.example.user.v1.UserAPI/List');
    }

    public function testHandleAdminProxyRequestsSuccess()
    {
        $this->ba->adminAuth();

        $stub = $this->getMockBuilder(BaseProxyController::class)
            ->setConstructorArgs(['cmma'])
            ->enableOriginalConstructor()
            ->enableOriginalClone()
            ->onlyMethods(['getRequestInstance', 'request'])
            ->getMock();

        $this->callMethod($stub, 'registerRoutesMap', [['GetUserList' => "/twirp\/rzp.example.user.v1.UserAPI\/List/"]]);

        $this->callMethod($stub, 'registerAdminRoutes', [["GetUserList"], ['GetUserList' => 'process_view']]);

        $this->ba->adminAuth();;

        $this->defineVariable($stub, 'ba', $this->ba);

        $this->addPermissionToBaAdmin('process_view');

        $request = new Request();
        $request->setMethod('POST');
        $request->request->add([]);

        $stub->expects($this->exactly(1))
             ->method('getRequestInstance')
             ->willReturn($request);

        $stub->expects($this->exactly(1))
             ->method('request')
             ->with
             ("https://cmma.razorpay.com//twirp/rzp.example.user.v1.UserAPI/List", [
                 'X-Admin-id'    => "RzrpySprAdmnId",
                 'X-Task-Id'     => $this->app['request']->getTaskId(),
                 'Content-Type'  => "application/json",
                 'Accept'        => "application/json",
                 'Authorization' => null,
                 'X-Request-ID'  => $this->app['request']->getTaskId(),
                 'X-Client-ID'   => '',
                 'rzpctx-dev-serve-user' => null,
             ], '{}', "POST", ['timeout' => null])
             ->willReturn(new \WpOrg\Requests\Response);

        $stub->handleAdminProxyRequests('/twirp/rzp.example.user.v1.UserAPI/List');
    }

    public static function callMethod($obj, $name, array $args)
    {

        $class = new \ReflectionClass($obj);

        $method = $class->getMethod($name);

        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    public static function defineVariable($obj, $name, $value)
    {

        $class = new \ReflectionClass($obj);

        $property = $class->getProperty($name);

        $property->setAccessible(true);

        $property->setValue($obj, $value);
    }

    protected function setMethodAccessible($obj, string $name, bool $access)
    {
        $class = new \ReflectionClass($obj);

        $method = $class->getMethod($name);

        $method->setAccessible($access);
    }
}
