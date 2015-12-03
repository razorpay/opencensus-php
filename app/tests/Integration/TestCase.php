<?php
namespace Tests\Integration;

use Config, App;
use Laracasts\TestDummy\Factory;
use Eloquent;
use DB;
use PHPUnit_Runner_BaseTestRunner;

class TestCase extends ZizacoIntegrationTestCase
{
    protected static $fixtures = array(
        'merchant' => 'Models\Merchant\Entity',
        'merchant_details' => 'Models\MerchantDetails\Entity',
        'admin' => 'Models\Admin\Entity');

    // Overriding this
    protected function startBrowser()
    {
        // Set the Application URL containing the port of the test server
        Config::set(
            'app.url',
            Config::get('app.url').':4443'
        );

        App::setRequestForConsoleEnvironment(); // This is a must

        if(! TestCase::$loadedBrowser)
        {
            $client  = new \Selenium\Client('localhost', 4444);
            $client->setBrowserClass('Tests\Integration\BrowserWrapper');
            $this->browser = $client->getBrowser('http://localhost:4443');
            $this->browser->start();
            $this->browser->windowMaximize();

            TestCase::$loadedBrowser = $this->browser;
        }
        else
        {
            $this->browser = TestCase::$loadedBrowser;
            $this->browser->open('/');
        }

    }

	public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        Factory::$factoriesPath = __DIR__.'/../factories/';

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
}
