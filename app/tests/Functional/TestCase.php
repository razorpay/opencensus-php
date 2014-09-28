<?php
namespace Tests\Functional;

use Laracasts\TestDummy\Factory;
use Eloquent;
use DB;
use Artisan;
use Route;
use TestCase as ParentTestCase;

class TestCase extends ParentTestCase
{   
    protected static $fixtures = array(
        'merchant' => 'Models\DAL\Merchant',
        'merchant_details' => 'Models\DAL\MerchantDetails',
        'admin' => 'Models\DAL\Admin');

    public function setUp()
    {
        parent::setUp();

        Artisan::call('migrate');

        DB::beginTransaction();
    }

    public function tearDown()
    {
        DB::rollback();
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

    protected static function generateRandomEmail()
    {
        return static::generateRandomString()."@".static::generateRandomString().".com";
    }
}