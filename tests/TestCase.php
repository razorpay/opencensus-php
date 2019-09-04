<?php

namespace RZP\Tests;

use App;
use Mockery;
use Request;
use ReflectionObject;
use RZP\Models\Terminal\Options as TerminalOptions;
use Illuminate\Foundation\Testing\TestCase as IlluminateTestCase;
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

    public function setUp()
    {
        //     $this->markTestSkippedForWercker();
        parent::setUp();

        // Load test data
        $this->loadTestData();

        config(['app.query_cache.mock' => true]);

        $this->config = $this->app['config'];

        $this->mockCardVault();
    }

    public function tearDown()
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

                case 'delete':
                    break;
            }

            return $response;
        };

        $cardVault->shouldReceive('sendRequest')
                  ->with(Mockery::type('string'), 'post', Mockery::type('array'))
                  ->andReturnUsing($callable);

        $this->app->instance('card.cardVault', $cardVault);
    }
}
