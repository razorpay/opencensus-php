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

    protected static $fixtures = array(
        'merchant' => 'Models\Merchant\Entity',
        'key' => 'Models\Key\Entity',
        'transaction' => 'Models\Transaction\Entity',
        'balance' => 'Models\Merchant\Balance');

    protected $auth = array();

    public function setUp()
    {
        parent::setUp();

        //
        // Setting up db
        //
        Artisan::call('migrate');

        //
        // Enable filters
        //
        Route::enableFilters();

        //
        // Start DB transaction so as
        // to rollback once done
        //
        DB::beginTransaction();

        //
        // Seed the db with required data
        // This creates key entity and merchant entity
        // This key can be used by default for most use-cases
        // but you are not required to use it.
        //
        $merchant = $this->createEntity('merchant', ['id' => 1]);
        $key = $this->createEntity('key', ['merchant_id' => 1]);
        $balance = $this->createEntity('balance', ['id' => 1]);

        //
        // The key created in last command is setup as
        // default basic auth param.
        //
        $this->setupBasicAuthParams();
    }

    /**
     * Sets the key and secret created setUp call as the
     * default to provide basicauth.
     * You can use your key and secret by overriding this
     * function in child classes.
     */
    protected function setupBasicAuthParams()
    {
        $this->auth = array(
               'PHP_AUTH_USER' => 'd9c6bf091a1a64cb5678d8c1',
               'PHP_AUTH_PW' => 'thisissupersecret');
    }

    public function tearDown()
    {
        //
        // Undo DB Changes after test
        //
        DB::rollback();
    }

    protected function createEntity($entity, $attributes = array())
    {
        $this->eloquentUnguard();

        $entity = self::$fixtures[$entity];

        $entity = Factory::create($entity, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    protected function eloquentUnguard()
    {
        Eloquent::unguard();
    }

    protected function eloquentReguard()
    {
        Eloquent::reguard();
    }
}
