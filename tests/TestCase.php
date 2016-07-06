<?php

namespace Tests;

use Mockery;
use ReflectionObject;

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;

class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
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

        return require __DIR__.'/../../bootstrap/start.php';
    }

    public function setUp()
    {
        //     $this->markTestSkippedForWercker();
        parent::setUp();

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
