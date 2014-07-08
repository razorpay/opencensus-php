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
        'transaction' => 'Models\Transaction\Entity');

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

        $this->setupBasicAuthParams();

        //
        // Start DB transaction so as
        // to rollback once done
        //
        DB::beginTransaction();
    }

    protected function setupBasicAuthParams()
    {
        // Auth
        $_SERVER['PHP_AUTH_USER'] = 'd9c6bf091a1a64cb5678d8c1';
        $_SERVER['PHP_AUTH_PW'] = 'thisissupersecret';
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
