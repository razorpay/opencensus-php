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

    protected $dbTxnInProgress = false;

    protected static $fixtures = array(
        'merchant'      => 'Models\Merchant\Entity',
        'key'           => 'Models\Key\Entity',
        'terminal'      => 'Models\Terminal\Entity',
        'transaction'   => 'Models\Transaction\Entity',
        'balance'       => 'Models\Merchant\Balance',
        'pricing'       => 'Models\Pricing\Entity');

    protected $auth = array();

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

        //
        // Setting up db
        //
        Artisan::call('migrate');
        Artisan::call('migrate', array('--database' => 'test'));

        //
        // Enable filters
        //
        Route::enableFilters();

        //
        // Start DB transaction so as
        // to rollback once done
        //
        DB::connection('test')->beginTransaction();
        DB::connection('live')->beginTransaction();
        $this->dbTxnInProgress = true;

        //
        // Seed the db with required data
        // This creates key entity and merchant entity
        // This key can be used by default for most use-cases
        // but you are not required to use it.
        //

        $this->entities = array(
            'pricing'     => $this->createDefaultPricingPlan(),
            'merchant'    => $this->createEntityInTestAndLive('merchant', ['id' => '363e4efa820b0c06208ccd99']),
            'terminal'    => $this->createEntity('terminal', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            'key'         => $this->createEntity('key', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            'balance'     => $this->createEntity('balance', ['id' => '363e4efa820b0c06208ccd99']),
            'transaction' => $this->createEntity('transaction', ['merchant_id' => '363e4efa820b0c06208ccd99']),
            );
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
        $this->eloquentUnguard();

        $entity = self::$fixtures[$entity];

        $entity = Factory::create($entity, $attributes);

        $this->eloquentReguard();

        return $entity;
    }

    protected function createEntityInTestAndLive($entity, $attributes = array())
    {
        $this->eloquentUnguard();

        $entity = self::$fixtures[$entity];

        $entity = Factory::build($entity, $attributes);

        $testEntity = clone $entity;
        $liveEntity = clone $entity;

        $testEntity->setConnection('test')->save();
        $liveEntity->setConnection('live')->save();

        $entity->exists = true;
    }

    public function createDefaultPricingPlan()
    {
        $pricingPlanId = '13906d42c88a41ee4e2d812e';

        $rows = array(
                    array(
                        'id' => '13906d42c88a41ee4e2d812e',
                        'plan_id' => '13906d42c88a41ee4e2d812e',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => null,
                        'payment_issuer' => null,
                        'percent_rate' => '2000',
                        'fixed_rate' => 0,
                    ),
                    array(
                        'id' => '13906de1816d11113ef4f86f',
                        'plan_id' => '13906d42c88a41ee4e2d812e',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => 'AMEX',
                        'payment_issuer' => null,
                        'percent_rate' => 3000,
                        'fixed_rate' => 0,
                    ),
                    array(
                        'id' => '13906df591e73f02d8afc302',
                        'plan_id' => '13906d42c88a41ee4e2d812e',
                        'plan_name' => 'testDefaultPlan',
                        'payment_mode' => 'card',
                        'payment_mode_type' => null,
                        'payment_network' => 'DICL',
                        'payment_issuer' => null,
                        'percent_rate' => 3000,
                        'fixed_rate' => 0,
                    ),
                );

        $repo = new \Models\Pricing\Repository;
        foreach ($rows as $row)
        {
            $pricing = new \Models\Pricing\Entity;
            $pricing->fill($row);
            $repo->saveOrFail($pricing);
        }

        $pricing = (new \Models\Pricing\Repository)->getPricingPlanByIdOrFailPublic($pricingPlanId);

        return $pricing;
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
