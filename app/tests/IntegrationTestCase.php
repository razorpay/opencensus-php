<?php
/**
 * Base class for creating integration tests using selenium for dashboard
 */

use Laracasts\TestDummy\Factory;

class IntegrationTestCase extends ModifiedZizacoIntegrationTestCase
{   
    protected static $fixtures = array(
        'merchant' => 'Models\DAL\Merchant',
        'merchant_details' => 'Models\DAL\MerchantDetails',
        'admin' => 'Models\DAL\Admin');


	public function setUp()
    {
        parent::setUp();
        
        //setting up db
        Artisan::call('migrate');
    }

    public function tearDown()
    {
        $status = $this->getStatus();
        
        /** Take a screenshot in case of failure **/
        if ($status == PHPUnit_Runner_BaseTestRunner::STATUS_ERROR || $status == PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE) {
            $this->browser->captureEntirePageScreenshot(storage_path().'/selenium.png', "");
        }

        parent::tearDown();
    }
    public static function tearDownAfterClass()
    {   
        /** Empties content of all tables defined in fixtures above on teardown **/
        static::truncateTables();

        parent::tearDownAfterClass();
    }

    protected function createEntity($entity, $attributes = array(), $times = 1)
    {
        Eloquent::unguard();

        $entity = self::$fixtures[$entity];

        $entity = Factory::times($times)->create($entity, $attributes);

        Eloquent::reguard();

        return $entity;
    }

    protected function buildEntity($entity, $attributes = array())
    {
        $entity = self::$fixtures[$entity];

        return Factory::build($entity, $attributes);
    }

    protected static function truncateTables()
    {
        foreach(static::$fixtures as $model)
        {
            DB::statement("SET foreign_key_checks=0");
        
            $model::truncate();

            DB::statement("SET foreign_key_checks=1");
        }

    }

    protected static function generateRandomString($length = 6)
    {
        return bin2hex(openssl_random_pseudo_bytes($length/2));
    }

    protected static function generateMerchantEmail()
    {
        return static::generateRandomString()."@".static::generateRandomString().".com";
    }
}