<?php

namespace Tests\Unit\app\Edge;

use App\Edge\Middleware\RegionCookieHandler;
use App\Providers\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RegionCookieHandlerTest extends BaseTestCase
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

        return $app;
    }

    private function userLogin($cc)
    {
        $merchantData = [
            'id' => 1,
            'role' => 'owner',
            'name' => 'John Doe',
            'banking_role' => 'owner',
            'logo_url' => null
        ];

        if (!empty($cc)) {
            $merchantData['country_code'] = $cc;
        }

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

    public function testRegionCookieSetForMerchantRegion()
    {
        $regionCookieHandler = new RegionCookieHandler();
        $request = new Request();

        $this->userLogin('SG');
        $response = $regionCookieHandler->handle($request, function ($request) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $this->assertEquals('rzp_user_merchant_region', $response->headers->getCookies()[0]->getName());
        $this->assertEquals('SG', $response->headers->getCookies()[0]->getValue());
    }

    public function testRegionCookieSetDefaultOnError()
    {
        $regionCookieHandler = new RegionCookieHandler();
        $request = new Request();

        $this->userLogin('');
        $response = $regionCookieHandler->handle($request, function ($request) {
            return response('', 200,  ['Content-Type' => 'application/json']);
        });

        $cookies = $response->headers->getCookies();
        $this->assertCount(0, $cookies);
    }
}
