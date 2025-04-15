<?php


namespace Tests\Unit\app\Http\Middleware;

use Mockery;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Providers\GenericUser;
use Illuminate\Support\Collection;
use \Illuminate\Support\Facades\Auth;
use App\Admin\Service as AdminService;
use Illuminate\Contracts\Console\Kernel;
use App\Splitz\Service as SplitzService;
use Razorpay\Api\Errors\BadRequestError;
use App\Http\Middleware\ABACDashboardAccess;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\MerchantDetails\Service as MerchantDetailsService;

class ABACDashboardAccessTest extends BaseTestCase
{
    protected $cache;

    protected $adminServiceMock;
    
    protected $splitzServiceMock;
    
    protected $merchantDetailsMock;
    
    const ABACMerchantOauthAccessExpKey = "ABAC_ACCESS_DASHBOARD";

    public function setUp(): void
    {
        parent::setUp();

        $this->app = \App::getFacadeRoot();

        $this->cache = $this->app['cache'];
        // clear the cache for an individual test case
        $this->cache->flush();

        $this->mockRequestServer("dashboard.razorpay.com");
        
        $this->adminServiceMock = Mockery::mock(AdminService::class)->makePartial();;
        
        $this->splitzServiceMock = Mockery::mock(SplitzService::class)->makePartial();
    
        $this->merchantDetailsMock = Mockery::mock("overload:" . MerchantDetailsService::class);
        
        $this->abacDashboardAccess = new ABACDashboardAccess($this->adminServiceMock, $this->merchantDetailsMock, $this->splitzServiceMock);
    }
    
    public function TearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
    
    private function mockRequiredData($splitzValue, $orgError,  $orgValue, $merchantDetailsValue)
    {
        if ($splitzValue !== null){
            $this->splitzServiceMock->shouldReceive('getVariantBulk')->once()->withAnyArgs()
                ->andReturn([config('splitz.experiments')[self::ABACMerchantOauthAccessExpKey] => ["variables" => ["result" => $splitzValue]]]);
        }
        
        if ($orgValue !== null ||$orgError !== null ){
            $this->adminServiceMock->shouldReceive('getOrg')->once()->withAnyArgs()
                ->andReturn([$orgError, $orgValue]);
        }
        
        if ($merchantDetailsValue !== null){
            $this->merchantDetailsMock->shouldReceive('getDetailsFromAPIWithCache')->once()->withAnyArgs()
                ->andReturn($merchantDetailsValue);
        }
    }
    
    private function createGenericUserTestData(): GenericUser
    {
        $merchantData = [
          'id' => 1123,
          'role' => 'owner',
          'name' => 'John Doe',
          'banking_role' => 'owner',
          'logo_url' => null
        ];
        
        $userData =  [
          'id'     => 1,
          'name'   => 'John Doe',
          'email' => 'test@gmail.com',
          'merchants' => new Collection([
            (object) $merchantData
          ])
        ];
        
        return new GenericUser($userData);
    }
    
    private function mockRequestServer(string $value)
    {
        $requestMock = $this->getMockBuilder(Request::class)
          ->setConstructorArgs([[], [], [], [], [], [], null])
          ->setMethods(['server'])
          ->getMock();
    
        $requestMock->expects($this->any())->method('server')->willReturn($value);
    
        $this->app->instance('request', $requestMock);
    }
    
    public function testCheckUrlArray()
    {
        foreach (ABACDashboardAccess::$globalAccessRouteUrl as $prefixedUrl => $data) {
            $this->assertTrue(is_array($data));
            $this->assertArrayHasKey('http_method', $data);
            $this->assertTrue(!Str::startsWith($prefixedUrl, "/"));
        }
    }
    
    public function testShouldCheckUrlOrRouteForAccess()
    {
        $req = Request::create("app/dashboard", "GET");
        [$shouldCheck, $pattern, $routeName] = $this->abacDashboardAccess->shouldCheckUrlOrRouteForAccess($req, "random");
        $this->assertTrue($shouldCheck);
        $this->assertEquals("app/*", $pattern);
        $this->assertEmpty($routeName);
    
        $req = Request::create("abcdd/dashboard", "GET");
        [$shouldCheck, $pattern, $routeName] = $this->abacDashboardAccess->shouldCheckUrlOrRouteForAccess($req, "random");
        $this->assertFalse($shouldCheck);
    }
    
    /**
     * @throws \Razorpay\Api\Errors\BadRequestError
     */
    public function testHandleMerchantIdNullNotLoginCase()
    {
        Auth::shouldReceive('user')->once()->andReturn(null);
        $req = Request::create("feature", "GET");
        $response = $this->abacDashboardAccess->handle($req, function ($request) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testHandleSplitzExpFalse()
    {
        $req = Request::create("feature", "GET");
        
        Auth::shouldReceive('user')->once()->andReturn($this->createGenericUserTestData());
    
        $this->mockRequiredData("off",
          null,
          null,
          null
        );
        
        $response = $this->abacDashboardAccess->handle($req, function ($req) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });
    
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testHandleGetOrgDetailsFailure()
    {
        $req = Request::create("feature", "GET");
        
        Auth::shouldReceive('user')->once()->andReturn($this->createGenericUserTestData());
        
        $this->mockRequiredData("on",
            "error in fetching org details",
            null,
            null
        );
        
        $response = $this->abacDashboardAccess->handle($req, function ($req) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testHandleCurrentOrgIsNotRazorpayOrg()
    {
        $req = Request::create("feature", "GET");
        
        Auth::shouldReceive('user')->once()->andReturn($this->createGenericUserTestData());
        
        $this->mockRequiredData("on",
            null,
            ["id" => "org_123", "merchant_session_timeout_in_seconds" => 3600, 'hostname' => "test.com"],
            null
        );
        
        $response = $this->abacDashboardAccess->handle($req, function ($req) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testHandleCurrentOrgIsEqualToLoginCurrentMerchantOrgID()
    {
        $req = Request::create("feature", "GET");
        
        Auth::shouldReceive('user')->once()->andReturn($this->createGenericUserTestData());
        
        $this->mockRequiredData("on",
            null,
            ["id" => "org_100000razorpay", "merchant_session_timeout_in_seconds" => 3600, 'hostname' => "test.com"],
            ["merchant" => ["org_id" => "100000razorpay"]]
        );
        
        $response = $this->abacDashboardAccess->handle($req, function ($req) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testHandleCurrentOrgIsNotEqualToLoginCurrentMerchantOrgIDButRouteIsWhiteListed()
    {
        // This is a case where the route is whitelisted and the org id is not equal to the login current merchant org id
        $req = Request::create("app/*", "GET");
        
        Auth::shouldReceive('user')->once()->andReturn($this->createGenericUserTestData());
        
        $this->mockRequiredData("on",
            null,
            ["id" => "org_100000razorpay", "merchant_session_timeout_in_seconds" => 3600, 'hostname' => "test.com"],
            ["merchant" => ["org_id" => "100000razorpay1"]]
        );
        
        $response = $this->abacDashboardAccess->handle($req, function ($req) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testHandleCurOrgIsNotEqToLoginCurMerchantOrgIDButRouteIsNotWhiteListed()
    {
        // This is a case where the route is whitelisted and the org id is not equal to the login current merchant org id
        $req = Request::create("partner", "GET");
        
        Auth::shouldReceive('user')->once()->andReturn($this->createGenericUserTestData());
  
        $this->expectException(BadRequestError::class);
        
        $this->mockRequiredData("on",
            null,
            ["id" => "org_100000razorpay", "merchant_session_timeout_in_seconds" => 3600, 'hostname' => "test.com"],
            ["merchant" => ["org_id" => "100000razorpay1"]]
        );
        
        $response = $this->abacDashboardAccess->handle($req, function ($req) {
           return response('', 400,  ['Content-Type' => 'application/json']);;
        });
    
        $this->assertEquals(400, $response->getStatusCode());
    }
    
}


