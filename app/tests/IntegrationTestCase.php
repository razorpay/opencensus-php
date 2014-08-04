<?php
use Laracasts\TestDummy\Factory;

class IntegrationTestCase extends Zizaco\TestCases\IntegrationTestCase
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
        
        if ($status == PHPUnit_Runner_BaseTestRunner::STATUS_ERROR || $status == PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE) {
            $this->browser->captureEntirePageScreenshot(storage_path().'/selenium.png', "");
        }

        parent::tearDown();
    }
    public static function tearDownAfterClass()
    {
        //Close Mockery
        Mockery::close();

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

    /**
     * Mocks a specific class and registers the mock in App
     *
     * @return mock object
     */
    protected function mock($class)
    {
      $mock = Mockery::mock($class)->shouldDeferMissing();

      App::instance($class, $mock);

      return $mock;
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
}