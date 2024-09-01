<?php

namespace Unit\Http\Middleware;


use Illuminate\Http\Request;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Tests\TestCase;

class AdminAccessTest extends TestCase
{
    private $baMock;
    
    protected $adminAccessClass;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->baMock = $this->mockBasicAuth();
    }
    
    public function testSetOrgHostnameByOrgId()
    {
        $mockedAdminAccessClass = $this->getMockBuilder('RZP\Http\Middleware\AdminAccess')
          ->setConstructorArgs([$this->app])
          ->getMock();
        
        $request = $this->mockRequest();
    
        $this->baMock->expects($this->once())->method('setOrgHostName')->with('dashboard.razorpay.in');
        
        $orgId= $request->headers->get('X-Org-Id');
        
        $this->assertEmpty($this->callProtectedMethod($mockedAdminAccessClass, "setOrgHostnameByOrgId", [$request, $orgId]));
    }
    
    public function testSetOrgHostnameByOrgIdAsNull()
    {
        $mockedAdminAccessClass = $this->getMockBuilder('RZP\Http\Middleware\AdminAccess')
          ->setConstructorArgs([$this->app])
          ->getMock();
        
        $request = $this->mockRequest();
        
        $this->baMock->expects($this->exactly(0))->method('setOrgHostName')->with('dashboard.razorpay.in');;
    
        $this->assertEmpty($this->callProtectedMethod($mockedAdminAccessClass, "setOrgHostnameByOrgId", [$request, null]));
    }
    
    protected function mockBasicAuth()
    {
        $mock = $this->getMockBuilder(BasicAuth::class)
          ->setConstructorArgs([$this->app])
          ->setMethods(['setOrgHostName'])
          ->getMock();
    
        $this->app->instance('basicauth', $mock);
        
        return $mock;
    }
    
    private function callProtectedMethod($instance, $method, array $args)
    {
        $class  = new \ReflectionClass(get_class($instance));
        $method = $class->getMethod($method);
        
        $method->setAccessible(true);
        
        return $method->invokeArgs($instance, $args);
    }
    
    protected function mockRequest(): Request
    {
        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('X-Org-Id', 'org_100000razorpay');
        $request->headers->set('X-Org-Hostname', 'dashboard.razorpay.in');
        return $request;
    }
}