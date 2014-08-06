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


	public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        //Refresh the db before a test
        passthru('cd ' . __DIR__ . '/../.. & php artisan migrate:refresh --env=testing');
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

    protected static function generateRandomString($length = 6)
    {
        return bin2hex(openssl_random_pseudo_bytes($length/2));
    }

    protected static function generateMerchantEmail()
    {
        return static::generateRandomString()."@".static::generateRandomString().".com";
    }
}