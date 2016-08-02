<?php

namespace Tests\Functional;

use Laracasts\TestDummy\Factory;
use Eloquent;
use Illuminate\Support\Facades\DB;
use Artisan;
use Route;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected static $fixtures = [
        'merchant'          => 'App\Merchant\Entity',
        'merchant_details'  => 'App\MerchantDetails\Entity',
        'admin'             => 'App\Admin\Entity'
    ];

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

    protected static function generateMerchantEmail()
    {
        return static::generateRandomString()."@".static::generateRandomString().".com";
    }
}
