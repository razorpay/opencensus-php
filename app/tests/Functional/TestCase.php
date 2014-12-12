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

    protected $testDataFilePath;

    protected $testData = array();

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

        $this->db = new Database($this->app['db']);

        // Setup database
        $this->db->setUp();

        // Instantiate fixture class
        $this->fixtures = new Fixtures\Fixtures;

        $this->fixtures->setUp();

        // Load test data
        $this->loadTestData();

        // Instantiate auth class
        $this->ba = new Authorization($this);

        // Enable filters
        $this->app['router']->enableFilters();
    }

    public function tearDown()
    {
        $this->db->tearDown();

        parent::tearDown();
    }

    protected function loadTestData()
    {
        if ($this->testDataFilePath !== null)
        {
            $this->testData = require($this->testDataFilePath);
        }
    }
}
