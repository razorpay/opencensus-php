<?php

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

namespace Tests\Functional;

use Artisan;
use DB;
use Eloquent;
use Laracasts\TestDummy\Factory;
use Route;
use Tests\TestCase as ParentTestCase;

class TestCase extends ParentTestCase
{
    use CustomAssertions;

    protected $fixtures;

    protected $dbTxnInProgress = false;

    protected $auth = array();

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

        // Enable filters
        Route::enableFilters();
    }

    public function tearDown()
    {
        //
        // Undo DB Changes after test
        //
        if ($this->dbTxnInProgress === true)
        {
            DB::connection('live')->rollBack();
            DB::connection('test')->rollBack();

            $this->dbTxnInProgress = false;
        }

        DB::disconnect('live');
        DB::disconnect('test');
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
        // Start DB transaction so as
        // to rollback once test is finished
        // leaving a clean slate
        //
        DB::connection('test')->beginTransaction();
        DB::connection('live')->beginTransaction();

        $this->dbTxnInProgress = true;

        // Seed DB with default entities to be used in
        // tests
        $this->entities = $this->fixtures->seedDbWithDefaultEntities();
    }

    /**
     * Migrates database
     */
    protected function dbMigrate()
    {
        Artisan::call('migrate');
        Artisan::call('migrate', array('--database' => 'test'));
    }

    /**
     * Sets the key and secret created setUp call as the
     * default to provide basicauth.
     * You can use your key and secret by overriding this
     * function in child classes.
     */
    protected function setupBasicAuthParams($user = null, $pwd = null)
    {
        $this->auth = array(
               'PHP_AUTH_USER' => $user,
               'PHP_AUTH_PW' => $pwd);
    }

    protected function setupAppBasicAuthParams($user = 'rzp_test', $pwd = 'DASHBOARD_AUTH_PASS')
    {
        $this->setupBasicAuthParams($user, $pwd);
    }

    protected function setupProxyBasicAuthParams($user = 'rzp_test_363e4efa820b0c06208ccd99')
    {
        $this->setupAppBasicAuthParams($user);
    }

    protected function setupPublicBasicAuthParams($user = 'rzp_test_d9c6bf091a1a64cb5678d8c1')
    {
        $this->setupBasicAuthParams($user, '');
    }

    protected function setupPrivateBasicAuthParams($user = null, $pwd = null)
    {
        if ($user === null)
        {
            $user = 'rzp_test_d9c6bf091a1a64cb5678d8c1';
        }

        if ($pwd === null)
        {
            $pwd = 'thisissupersecret';
        }

        $this->setupBasicAuthParams($user, $pwd);
    }

    protected function createEntity($entity, $attributes = array())
    {
        return $this->fixtures->createEntity($entity, $attributes);
    }
}
