<?php

namespace RZP\Tests\Functional\CircuitBreaker;

use Cache;
use Carbon\Carbon;
use RZP\Models\Admin\ConfigKey;
use Illuminate\Database\Eloquent\Factory;

use RZP\Exception;
use RZP\Constants\Product;
use RZP\Models\Coupon\Constants;
use RZP\Models\Schedule\Period;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\OAuth\OAuthTrait;
use RZP\Services\CircuitBreaker\KeyHelper;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class AadhaarEkycCircuitBreaker extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;
    use OAuthTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/CircuitBreakerTestData.php';

        parent::setUp();



        $this->ba->adminAuth();
    }

    protected function mockRazorxTreatment()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn('on');

    }

    public function testAadhaarEkycOpenCircuit()
    {
        Cache::put(KeyHelper::KEY_OPEN, true, 30);
        Cache::put(KeyHelper::KEY_HALF_OPEN, true, 50);

        $this->startTest();
    }
    public function testAadhaarEkycClosedCircuitFailed()
    {
        $this->startTest();
        $this->assertEquals(1,Cache::get(KeyHelper::KEY_TOTAL_FAILURES));
    }
}
