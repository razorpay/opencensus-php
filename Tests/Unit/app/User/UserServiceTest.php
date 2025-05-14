<?php

namespace Tests\Unit\app\User;

use App\Providers\GenericUser;
use App\User\Helper;
use App\User\Service as UserService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;

class UserServiceTest extends BaseTestCase
{
    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    /**
     * Test successful session generation through the generateSessionFromPayload method
     */
    public function testGenerateSessionFromPayloadSuccess()
    {
        // Input
        $input = [
            'id' => 'user123',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        // Expected GenericUser object
        $genericUser = new GenericUser([
            'id' => $input['id'],
            'name' => $input['name'],
            'email' => $input['email'],
        ]);

        // Mock Helper with overload
        $helperMock = Mockery::mock('overload:' . Helper::class);
        $helperMock->shouldReceive('createdGenericUser')
            ->once()
            ->with($input)
            ->andReturn($genericUser);

        // Mock partial UserService
        $userServiceMock = Mockery::mock(UserService::class)->makePartial();
        $userServiceMock->shouldAllowMockingProtectedMethods();

        // Mock protected handleLoginResponse
        $userServiceMock->shouldReceive('handleLoginResponse')
            ->once()
            ->with(null, $genericUser, 'email', 200, $input)
            ->andReturn([null, ['token' => 'csrf_token_123', 'user_id' => 'user123'], 200]);

        // Execute and assert
        $result = $userServiceMock->generateSessionFromPayload($input);

        $this->assertNull($result[0]);
        $this->assertEquals(['token' => 'csrf_token_123', 'user_id' => 'user123'], $result[1]);
        $this->assertEquals(200, $result[2]);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
