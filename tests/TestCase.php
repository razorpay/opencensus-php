<?php

namespace RZP\Tests;

use App;
use Mockery;
use Request;
use ReflectionObject;
use RZP\Models\Terminal\Options as TerminalOptions;
use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;

use RZP\Models\Admin;
/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

class TestCase extends IlluminateTestCase
{
    // Not present in the illuminate test case
    protected $baseUrl = '';

    protected $testDataFilePath;

    protected $testData = array();

    protected static $t = 1;

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $unitTesting = true;

        // By default testing is set by IlluminateTestCase
        // Now uses what mode is passed from command line.
        $testEnvironment = $_SERVER['APP_ENV'] ?? 'testing';

        putenv('APP_ENV='.$testEnvironment);

        TerminalOptions::setTestChance(0);

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        //     $this->markTestSkippedForWercker();
        parent::setUp();

        // Load test data
        $this->loadTestData();

        config(['app.query_cache.mock' => true]);

        $this->config = $this->app['config'];

        $this->mockCardVault();

        $this->disbaleCpsConfig();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();

        $this->freeUpObjectProperties();

        $this->resetIniConfiguration();
    }

    protected function setUpTraits()
    {
        ;
    }

    protected function resetIniConfiguration()
    {
        ini_restore('memory_limit');
        ini_restore('max_execution_time');
    }

    protected function freeUpObjectProperties()
    {
        $reflectionObject = new ReflectionObject($this);

        foreach ($reflectionObject->getProperties() as $property)
        {
            if (($property->isStatic() === false) and
                (strpos($property->getDeclaringClass()->getName(), 'PHPUnit_') !== 0))
            {
                $property->setAccessible(true);
                $property->setValue($this, null);
            }
        }
    }

    protected function loadTestData()
    {
        static $testData = null;

        if (($this->testDataFilePath !== null) and
            ($testData === null))
        {
            $testData = require($this->testDataFilePath);
        }

        $this->testData = $testData;
    }

    protected function markTestSkippedForWercker()
    {
        if ($this->isTestRunningOnWercker())
        {
            $this->markTestSkipped();
        }
    }

    protected function isTestRunningOnWercker()
    {
        return (getenv('WERCKER') === 'true');
    }


    protected function mockCardVault($callable = null)
    {
        $app = App::getFacadeRoot();

        $cardVault = Mockery::mock('RZP\Services\CardVault', [$app])->makePartial();

        $this->app->instance('card.cardVault', $cardVault);

        $mpanVault = Mockery::mock('RZP\Services\CardVault', [$app, 'mpan'])->makePartial();

        $this->app->instance('mpan.cardVault', $mpanVault);

        $callable = $callable ?: function ($route, $method, $input)
        {
            $response = [
                'error' => '',
                'success' => true,
            ];

            switch ($route)
            {
                case 'tokenize':
                    $response['token'] = base64_encode($input['secret']);
                    $response['fingerprint'] = strrev(base64_encode($input['secret']));
                    $response['scheme'] = '0';
                    break;

                case 'detokenize':
                    $response['value'] = base64_decode($input['token']);
                    break;

                case 'validate':
                    if ($input['token'] === 'fail')
                    {
                        $response['success'] = false;
                    }
                    break;

                case 'token/renewal' :
                    $response['expiry_time'] = date('Y-m-d H:i:s', strtotime('+1 year'));
                    break;

                case 'delete':
                    break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $mpanVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', null)
                  ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }

    public function enableCpsConfig()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED => 1]);
    }

    public function enableRupayCaptureDelayConfig()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::DELAY_RUPAY_CAPTURE => 1]);
    }

    public function disbaleCpsConfig()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_PAYMENT_SERVICE_ENABLED => 0]);
    }

    public function enableCpsEmiFetch()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_PAYMENT_SERVICE_EMI_FETCH => 1]);
    }

    public function disableCpsEmiFetch()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::CARD_PAYMENT_SERVICE_EMI_FETCH => 0]);
    }

    public function enableNbPlusConfig()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::NB_PLUS_SERVICE_ENABLED => 1]);
    }

    public function disableNbPlusConfig()
    {
        (new Admin\Service)->setConfigKeys([Admin\ConfigKey::NB_PLUS_SERVICE_ENABLED => 0]);
    }

    public function enablePgRouterConfig()
    {
        (new Admin\Service())->setConfigKeys([Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED => 1]);
    }

    public function disablePgRouterConfig()
    {
        (new Admin\Service())->setConfigKeys([Admin\ConfigKey::PG_ROUTER_SERVICE_ENABLED => 0]);
    }

    public function enableScroogeRelationalLoadConfig()
    {
        (new Admin\Service())->setConfigKeys([Admin\ConfigKey::SCROOGE_0LOC_ENABLED => 1]);
    }

    public function disableScroogeRelationalLoadConfig()
    {
        (new Admin\Service())->setConfigKeys([Admin\ConfigKey::SCROOGE_0LOC_ENABLED => 0]);
    }
}
