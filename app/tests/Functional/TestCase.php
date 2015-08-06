<?php

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

namespace Tests\Functional;

use Artisan;
use DB;
use Tests\TestCase as ParentTestCase;

class TestCase extends ParentTestCase
{
    use CustomAssertions;

    protected $fixtures;

    protected $ba;

    protected static $initialSetupDone = false;

    /**
     * To denote whether to simulate unit tests with
     * environment being in cloud
     *
     * @var boolean
     */
    protected $cloud = true;

    public function setUp()
    {
        parent::setUp();

        $this->db = new Database($this->app);

        // Instantiate fixture class
        $this->fixtures = Fixtures\Fixtures::getInstance();

        $this->initialSetup();

        // Instantiate auth class
        $this->ba = new Authorization($this);

        // Enable filters
        $this->app['router']->enableFilters();
    }

    public function initialSetup()
    {
        if ((self::$initialSetupDone === true) and
            ($this->isTestRunningOnWercker()))
        {
            // Setup database
            $this->db->setUp();

            return;
        }

        // Run migrations
        $this->db->migrate();

        // // Truncate tables
        // $this->db->truncate();

        if ($this->isTestRunningOnWercker() === false)
        {
            $this->db->setUp();
        }

        // Seed database
        $this->fixtures->setUp();

        if ($this->isTestRunningOnWercker() === true)
        {
            // Setup database
            $this->db->setUp();
        }

        self::$initialSetupDone = true;
    }

    public function tearDown()
    {
        if ($this->db !== null)
            $this->db->tearDown();

        parent::tearDown();
    }

    protected function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = [];
        if (isset($this->testData[$name]))
        {
            $testData = $this->testData[$name];
        }

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}
