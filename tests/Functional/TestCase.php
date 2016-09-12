<?php

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

namespace RZP\Tests\Functional;

use Artisan;
use RZP\Tests\TestCase as ParentTestCase;

class TestCase extends ParentTestCase
{
    use CustomAssertions;

    protected $fixtures;

    protected $ba;

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

//      $this->markTestSkipped();

        $this->db = new Database($this->app);

        // Instantiate fixture class
        $this->fixtures = Fixtures\Fixtures::getInstance();

        $this->initialSetup();

        // Instantiate auth class
        $this->ba = new Authorization($this);

        // Enable filters
        // $this->app['router']->enableFilters();
    }

    public function initialSetup()
    {
        $this->db->setUp();

        $this->db->runFixtures($this->fixtures);
    }

    public function tearDown()
    {
        if ($this->db !== null)
            $this->db->tearDown();

        parent::tearDown();
    }

    protected function startTest($testDataToReplace = [])
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

    protected function changeEnvToNonTest()
    {
        $this->app['env'] = 'production';
    }
}
