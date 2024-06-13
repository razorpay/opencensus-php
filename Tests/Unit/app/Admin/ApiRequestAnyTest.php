<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Admin\ApiRequestAny;

class ApiRequestAnyTest extends BaseTestCase
{

    protected $cache;

    public function setUp(): void
    {
        parent::setUp();

        $this->app = \App::getFacadeRoot();

        $this->cache = $this->app['cache'];
        // clear the cache for an individual test case
        $this->cache->flush();
    }

    public function createApplication()
    {
        $testEnvironment = 'testing';

        putenv("APP_ENV=$testEnvironment");

        $app = require __DIR__ . '/../../../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function testUpdatePathWithQueryParams()
    {
        $testData = [
            [
                'path'  => 'vendor-payments/meta/summary',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'vendor-payments/meta/summary?count=10&text=testing'
            ],
            [
                'path'  => 'vendor1-payments/meta/summary',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'vendor1-payments/meta/summary'
            ],
            [
                'path'  => 'vendor-payments',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'vendor-payments?count=10&text=testing'
            ],
            [
                'path'  => 'vendor/meta/summary',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'vendor/meta/summary'
            ],
            [
                'path'  => 'vendor-portal/meta/summary',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'vendor-portal/meta/summary'
            ],
            [
                'path'  => 'gcoms/meta/summary',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'gcoms/meta/summary?count=10&text=testing'
            ],
            [
                'path'  => 'gcoms',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'gcoms?count=10&text=testing'
            ],
            [
                'path'  => 'vendor-payments/meta/summary',
                'method' => 'POST',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'vendor-payments/meta/summary'
            ],
            [
                'path'  => 'refunds',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'refunds?count=10&text=testing'
            ],
            [
                'path'  => 'refunds',
                'method' => 'POST',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'refunds'
            ],
            [
                'path'  => 'refunds/rfnd_OLY6pXytPx3nVC',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'refunds/rfnd_OLY6pXytPx3nVC?count=10&text=testing'
            ],
            [
                'path'  => 'payments/pay_OLY0H4ibyLkmV0/refunds',
                'method' => 'GET',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'payments/pay_OLY0H4ibyLkmV0/refunds?count=10&text=testing'
            ],
            [
                'path'  => 'payments/pay_OLY0H4ibyLkmV0/refunds',
                'method' => 'POST',
                'input' => [
                    'count' => 10,
                    'text' => 'testing'
                ],
                'expected' => 'payments/pay_OLY0H4ibyLkmV0/refunds'
            ],
        ];

        $ins  = new ApiRequestAny();

        $class  = new \ReflectionClass(get_class($ins));

        $method = $class->getMethod('updatePathWithQueryParams');

        $method->setAccessible(true);

        foreach ($testData as $testCase)
        {
            $actualResult = $method->invokeArgs($ins, [$testCase['path'], $testCase['method'], $testCase['input']]);

            $this->assertEquals($testCase['expected'], $actualResult);
        }
    }
}
