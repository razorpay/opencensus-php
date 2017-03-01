<?php

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

namespace RZP\Tests\Functional;

use Artisan;
use RZP\Tests\TestCase as ParentTestCase;
use RZP\Services\EsClient;

class TestCase extends ParentTestCase
{
    use CustomAssertions;

    protected $fixtures;

    /**
     * @var Authorization
     */
    protected $ba;

    /**
     * To denote whether to simulate unit tests with
     * environment being in cloud
     *
     * @var boolean
     */
    protected $cloud = true;

    public function setUp()
    {
        parent::setUp();

//      $this->markTestSkipped();

        $this->db = new Database($this->app);

        // Instantiate fixture class
        $this->fixtures = Fixtures\Fixtures::getInstance();

        $this->initialSetup();

        // Instantiate auth class
        $this->ba = new Authorization($this);

        // Enable filters
        // $this->app['router']->enableFilters();
    }

    public function initialSetup()
    {
        $this->db->setUp();

        $this->db->runFixtures($this->fixtures);
    }

    public function tearDown()
    {
        if ($this->db !== null)
            $this->db->tearDown();

        parent::tearDown();
    }

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = [];
        if (isset($this->testData[$name]))
        {
            $testData = $this->testData[$name];
        }

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    protected function changeEnvToNonTest()
    {
        $this->app['env'] = 'production';
    }

    /**
     * After insert/update api call, only response is asserted for update via base code.
     * This method helps in asserting the same expected response with db's last
     * entity.
     * This ensures following failing case: If entity is build but saveOrFail()
     * is not called, response will have expected updated data but in db it'll
     * be old data still.
     *
     * @param string $entity
     * @param string $methodName
     *
     * @return null
     */
    protected function assertResponseWithLastEntity(string $entity, string $methodName)
    {
        $entity   = $this->getLastEntity($entity);

        $expected = $this->testData[$methodName]['response']['content'];

        $this->assertArraySelectiveEquals($expected, $entity);
    }

    /**
     * Creates a mock of EsClient and sets it to be used when invoked from app.
     * Also returns the same mock for setting expectations.
     *
     * @return object
     */
    protected function createEsMock($withMethods = [])
    {
        $esMock = $this->getMockBuilder(EsClient::class)
                       ->setConstructorArgs([$this->app])
                       ->setMethods($withMethods)
                       ->getMock();

        $this->app->instance('es', $esMock);

        return $esMock;
    }
}
