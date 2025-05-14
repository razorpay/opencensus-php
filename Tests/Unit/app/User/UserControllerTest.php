<?php

namespace Tests\Unit\app\Edge;

use App\Constants\Constants;
use App\Http\Controllers\UserController;
use App\Providers\GenericUser;
use App\User\Service as UserService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Mockery;
use function PHPUnit\Framework\assertEquals;
use ReflectionClass;

class UserControllerTest extends BaseTestCase {

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

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

    public function testGetLogout()
    {
        $userController = new UserController();

        $this->userLogin();

        $userController->getLogout();

        $accessTokenCookie =  cookie(Constants::RZP_ACCESS_TOKEN);

        $refreshTokenCookie = cookie(Constants::RZP_REFRESH_TOKEN);

        assertEquals($accessTokenCookie->getMaxAge(), 0);
        assertEquals($refreshTokenCookie->getMaxAge(), 0);
    }

    /**
     * Test successful session creation through the createSession method
     */
    public function testCreateSessionSuccess()
    {
        // Input payload the controller expects
        $inputData = [
            'id' => 'user123',
            'email' => 'test@example.com',
            'name' => 'Test User'
        ];

        // Expected session data returned by the service
        $sessionData = [
            'id' => 'user123',
            'logged_in_via' => 'email',
            'show_tnc_popup' => false
        ];

        // Mock UserService
        $userServiceMock = Mockery::mock(UserService::class);
        $userServiceMock->shouldReceive('generateSessionFromPayload')
            ->once()
            ->with($inputData)
            ->andReturn([null, $sessionData, 200]);

        // Inject the input into the global request instance
        $request = Request::create('/internal/session', 'POST', $inputData);
        $this->app->instance('request', $request);

        // Mock trace
        $traceMock = Mockery::mock();
        $traceMock->shouldReceive('info')->once();
        $this->app->instance('trace', $traceMock);

        // Create controller instance
        $controller = new UserController();

        // Use reflection to set the private property userService
        $reflection = new ReflectionClass($controller);
        $userServiceProperty = $reflection->getProperty('userService');
        $userServiceProperty->setAccessible(true); // Make it accessible
        $userServiceProperty->setValue($controller, $userServiceMock); // Inject mock

        // Run the test
        $response = $controller->createSession();

        // Assert response is what we expect
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            json_encode([
                'status_code' => 200,
                'success' => true,
                'data' => $sessionData
            ]),
            $response->getContent()
        );
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
