<?php

namespace Tests\Unit\app\Edge;

use App\Edge\ValidateEdgeToken;
use App\Providers\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ValidateEdgeTokenTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // added to test facade construction, not used anywhere else
        $this->edgeTokenValidator = $app['edgeTokenValidator'];

        return $app;
    }

    private function userLogin()
    {
        $merchantData = [
            'id' => 1,
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

        $user = new GenericUser($userData);
        $this->actingAs($user);
        Auth::login($user);
    }

    public function testTokenNotRevoked()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn(false);

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);


        $edgeTokenValidator = new ValidateEdgeToken();
        $this->assertFalse($edgeTokenValidator->verifyRevoked());
    }

    public function testEmptyUser()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn(true);

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $edgeTokenValidator = new ValidateEdgeToken();
        $this->assertFalse($edgeTokenValidator->verifyRevoked());
    }

    public function testTokenHeaderNotPresent()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn(null);

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $edgeTokenValidator = new ValidateEdgeToken();
        $this->assertFalse($edgeTokenValidator->verifyRevoked());
    }

    public function testTokenRevoked()
    {
        $requestMock = $this->getMockBuilder(Request::class)
            ->setConstructorArgs([[], [], [], [], [], [], null])
            ->setMethods(['header'])
            ->getMock();

        // Sets returns for metric dimensions
        $requestMock->expects($this->any())->method('header')->willReturn(true);

        // Finally set the mocked request object as app instance
        $this->app->instance('request', $requestMock);

        $this->userLogin();
        $edgeTokenValidator = new ValidateEdgeToken();
        $this->assertTrue($edgeTokenValidator->verifyRevoked());
    }
}
