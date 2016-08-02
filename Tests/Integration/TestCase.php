<?php

namespace Tests\Integration;

use App;
use Artisan;
use Tests\TestCase as BaseTestCase;
use Laracasts\TestDummy\Factory;
use Eloquent;
use Illuminate\Support\Facades\DB;
use PHPUnit_Runner_BaseTestRunner;
use PHPUnit_Extensions_Selenium2TestCase_ScreenshotListener as ScreenshotListener;

class TestCase extends BaseTestCase
{
    use BrowserHelper;

    protected static $fixtures = [
        'merchant'          => 'App\Merchant\Entity',
        'merchant_details'  => 'App\MerchantDetails\Entity',
        'admin'             => 'App\Admin\Entity',
        'user'              => 'App\User\Entity',
    ];

    // Overriding this
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

    protected static function generateRandomInteger($digits)
    {
        $min = pow(10, $digits-1);
        $max = pow(10, $digits) -1;
        return rand($min, $max);
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

    public function tearDown()
    {
        $this->listener->addError($this, new \Exception(), NULL);
    }

    public function setUp()
    {
        parent::setUp();

        $this->listener = new ScreenshotListener(
            storage_path() . '/screenshots'
        );
    }
}
