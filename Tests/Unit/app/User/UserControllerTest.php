<?php

namespace Tests\Unit\app\Edge;

use App\Constants\Constants;
use App\Http\Controllers\UserController;
use App\Providers\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use function PHPUnit\Framework\assertEquals;

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
}
