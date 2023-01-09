<?php

namespace Tests\Unit;

use Mockery;
use ReflectionObject;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Config;

class TestCase extends PHPUnitTestCase
{

    protected $app;

    protected $hubspotMock;

    protected $repoMock;

    protected $basicAuthMock;

    protected $diagClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createApplication();

        $this->createApplicationMocks();

        Config::set('applications.test_case.execution', true);
    }

    /**
     * Creates the application.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $testEnvironment = $_SERVER['APP_ENV'] ?? 'testing';

        putenv('APP_ENV='.$testEnvironment);

        $app = require __DIR__.'/../../bootstrap/app.php';

        $app['rzp.mode'] = 'test';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function createApplicationMocks()
    {
        // HubsportClient mocking
        $this->hubspotMock = Mockery::mock('RZP\Services\HubspotClient');

        $this->app->instance('hubspot', $this->hubspotMock);

        // RepositoryManager mocking
        $this->repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);

        $this->app->instance('repo', $this->repoMock);

        // BasicAuth mocking
        $this->basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $this->basicAuthMock);

        //diag service mocking
        $this->diagClientMock = Mockery::mock('RZP\Services\DiagClient');

        $this->app->instance('diag', $this->diagClientMock);

    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->app)
        {

            $this->app->flush();

            $this->app = null;
        }

        Mockery::close();

        $this->freeUpObjectProperties();

        $this->resetIniConfiguration();
    }

    public function freeUpObjectProperties()
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

    public function resetIniConfiguration()
    {
        ini_restore('memory_limit');

        ini_restore('max_execution_time');
    }

    protected function getPrivateProperty(& $object, string $propertyName)
    {
        $reflector = new \ReflectionClass(get_class($object));

        $property = $reflector->getProperty($propertyName);

        $property->setAccessible(true);

        $propertyValue = $property->getValue($object);

        return $propertyValue;
    }

    public function setPrivateProperty(& $object, string $propertyName, $value)
    {
        $reflector = new \ReflectionClass(get_class($object));

        $property = $reflector->getProperty($propertyName);

        $property->setAccessible(true);

        $propertyValue = $property->setValue($object, $value);

        return $propertyValue;
    }
}
