<?php

namespace RZP\Tests;

use Redis;
use Mockery;
use Request;
use ReflectionObject;
use RZP\Models\Terminal\Options as TerminalOptions;
use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;
/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;

class TestCase extends IlluminateTestCase
{
    // Not present in the illuminate test case
    protected $baseUrl = '';

    protected $testDataFilePath;

    protected $testData = array();

    protected static $t = 1;

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $unitTesting = true;

        $testEnvironment = 'testing';

        TerminalOptions::setTestChance(0);

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function setUp()
    {
        //     $this->markTestSkippedForWercker();
        parent::setUp();

        $this->setRedisMock();

        // Load test data
        $this->loadTestData();

        $this->config = $this->app['config'];
    }

    public function tearDown()
    {
        Mockery::close();

        $this->freeUpObjectProperties();

        parent::tearDown();
    }

    protected function setUpTraits()
    {
        ;
    }

    protected function setRedisMock()
    {
        $redisResponse = new \Predis\Response\Status('OK');

        Redis::shouldReceive('set')
            ->andReturn($redisResponse)
            ->byDefault();

        Redis::shouldReceive('get')
            ->andReturn('requestId')
            ->byDefault();

        Redis::shouldReceive('del')
            ->andReturn(1)
            ->byDefault();
    }

    protected function freeUpObjectProperties()
    {
        $reflectionObject = new ReflectionObject($this);

        foreach ($reflectionObject->getProperties() as $property)
        {
            if (($property->isStatic() === false) and
                (strpos($property->getDeclaringClass()->getName(), 'PHPUnit_') !== 0))
            {
                $property->setAccessible(true);
                $property->setValue($this, null);
            }
        }
    }

    protected function loadTestData()
    {
        static $testData = null;

        if (($this->testDataFilePath !== null) and
            ($testData === null))
        {
            $testData = require($this->testDataFilePath);
        }

        $this->testData = $testData;
    }

    protected function markTestSkippedForWercker()
    {
        if ($this->isTestRunningOnWercker())
        {
            $this->markTestSkipped();
        }
    }

    protected function isTestRunningOnWercker()
    {
        return (getenv('WERCKER') === 'true');
    }
}
