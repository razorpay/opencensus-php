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

    protected $dbTransactionInProgress = false;

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

    protected $entities = array();

    public function setUp()
    {
        parent::setUp();

        // Instantiate fixture class
        $this->fixtures = new Fixtures\Fixtures;

        // Setup database
        $this->runDbSetupOperations();

        // Load test data
        $this->loadTestData();

        // Instantiate auth class
        $this->ba = new Authorization($this);

        // Enable filters
        $this->app['router']->enableFilters();
    }

    public function tearDown()
    {
        //
        // Undo DB Changes after test
        //
        if ($this->dbTransactionInProgress === true)
        {
            DB::connection('live')->rollBack();
            DB::connection('test')->rollBack();

            $this->dbTransactionInProgress = false;
        }

        DB::disconnect('live');
        DB::disconnect('test');

        parent::tearDown();
    }

    protected function loadTestData()
    {
        if ($this->testDataFilePath !== null)
        {
            $this->testData = require($this->testDataFilePath);
        }
    }

    protected function runDbSetupOperations()
    {
        // Run DB migration
        $this->dbMigrate();

        //
        // Start DB payment so as
        // to rollback once test is finished
        // leaving a clean slate
        //
        DB::connection('test')->beginTransaction();
        DB::connection('live')->beginTransaction();

        $this->dbTransactionInProgress = true;

        // Seed DB with default entities to be used in
        // tests
        $this->entities = $this->fixtures->seedDbWithDefaultEntities();
    }

    /**
     * Migrates database
     */
    protected function dbMigrate()
    {
        Artisan::call('migrate', array('--database' => 'live'));
        Artisan::call('migrate', array('--database' => 'test'));
    }
}
