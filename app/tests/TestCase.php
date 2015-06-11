<?php

namespace Tests;

use Mockery;

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

use Laracasts\TestDummy\Factory;

class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    protected $testDataFilePath;

    protected $testData = array();

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
        $this->markTestSkippedForWercker();
        parent::setUp();

        // Load test data
        $this->loadTestData();

        $this->config = $this->app['config'];
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
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
        if (getenv('WERCKER') === "true")
        {
            $this->markTestSkipped();
        }
    }
}
