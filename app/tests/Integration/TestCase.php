<?php
namespace Tests\Integration;

use Laracasts\TestDummy\Factory;
use Eloquent;
use DB;
use PHPUnit_Runner_BaseTestRunner;

class TestCase extends ZizacoIntegrationTestCase
{
    protected static $fixtures = array(
        'merchant' => 'Models\DAL\Merchant',
        'merchant_details' => 'Models\DAL\MerchantDetails',
        'admin' => 'Models\DAL\Admin');

	public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Refresh the db before a test
        exec('cd ' . __DIR__ . '/../.. & php artisan migrate --env=testing');
    }

    public function tearDown()
    {
        $status = $this->getStatus();

        /** Take a screenshot in case of failure **/
        if (($status == PHPUnit_Runner_BaseTestRunner::STATUS_ERROR) or
            ($status == PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE))
        {
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

    protected function truncateAll()
    {
        DB::statement("SET foreign_key_checks=0");

        foreach (static::$fixtures as $model)
        {
            $model::truncate();
        }

        DB::statement("SET foreign_key_checks=1");
    }
}