<?php

/**
 * Base test case class provided bdy laravel all, test cases inherit it
 * ALl test cases follow, GIVEN, WHEN, THEN structure
 */

namespace RZP\Tests\Functional;

use Cache;
use Artisan;
use Carbon\Carbon;
use Illuminate\Cache\FileStore;
use Illuminate\Support\Facades\Redis;

use Config;
use RZP\Services\EsClient;
use RZP\Services\Mock\BeamService;
use RZP\Tests\TestCase as ParentTestCase;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use RZP\Services\AutoGenerateApiDocs\Constants as ApiDocsConstants;

class TestCase extends ParentTestCase
{
    use CustomAssertions, ArraySubsetAsserts;

    /**
     * @var Fixtures\Fixtures
     */
    protected $fixtures;

    /**
     * @var Authorization
     */
    protected $ba;

    /**
     * @var Database
     */
    protected $db;

    /**
     * @var EsClient
     */
    protected $es;

    /**
     * To denote whether to simulate unit tests with
     * environment being in cloud
     *
     * @var boolean
     */
    protected $cloud = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initialSetup();

        // Instantiate auth class
        $this->ba = new Authorization($this);

        //
        // Creates and configures EsClient instance.
        // We don't get the same from service provider as it might
        // cause some issues.
        //
        $this->es = new EsClient($this->app);

        $host = $this->config->get('database.es_host');

        $this->es->setEsClient(['hosts' => [$host]]);

        Config::set('applications.test_case.execution', true);

    }

    public function initialSetup()
    {
        $this->db = new Database($this->app);

        // Instantiate fixture class
        $this->fixtures = Fixtures\Fixtures::getInstance();

        $this->db->setUp();

        $this->db->runFixtures($this->fixtures);

        //
        // Redis cache flushing needs to be in setUp and not tearDown as some
        // tests, mock the redis facade and the connection methods are not
        // available for the mock object in tearDown.
        //

        $this->flushCache();
    }

    public function flushCache()
    {
        //A lot of test cases are redis independent,
        // thus if we set Cache Driver as File in .env.testing on local machine,
        // we can bypass the dep of redis.
        if (Cache::driver()->getStore() instanceof FileStore)
        {
            Cache::flush();
        }
        else
        {
            Redis::connection('unit_tests_connection')->flushall();

            foreach ($this->config->get('database.redis.clusters') as $cluster => $config)
            {
                foreach (Redis::connection($cluster)->getConnection() as $node)
                {
                    $node->executeCommand(new \Predis\Command\ServerFlushDatabase());
                }
            }
        }
    }

    protected function tearDown(): void
    {
        if ($this->db !== null)
        {
            $this->db->tearDown();
        }

        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function getDefaultDescription(string $functionName): string
    {
        $prefix = 'test';
        $functionName = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $functionName);
        return ucwords(implode(' ',preg_split('/(?=[A-Z])/', $functionName)));
    }

    protected function startTest($testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = [];
        if (isset($this->testData[$name]))
        {
            $testData = $this->testData[$name];

            if(empty($testData) === false)
            {
                $apiDocumentationData = [];
                $apiDocumentationData[ApiDocsConstants::API_SUMMARY]                  = !empty($testData['summary']) ? $testData['summary']: null ;
                $apiDocumentationData[ApiDocsConstants::API_REQUEST_DESCRIPTION]      = $testData['request']['description'] ?? $this->getDefaultDescription($name);
                $apiDocumentationData[ApiDocsConstants::API_RESPONSE_DESCRIPTION]     = $testData['response']['description'] ?? '';

                $headers                        = $testData['request']['headers'] ?? [];
                $testData['request']['headers'] = array_merge($headers,  [ApiDocsConstants::API_DOCUMENTATION_DETAILS => json_encode($apiDocumentationData)]);
            }
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

    protected function setEsMockExpectations($callee, $esMock, $method = 'search')
    {
        $esParams   = "{$callee}ExpectedSearchParams";
        $esResponse = "{$callee}ExpectedSearchResponse";

        $mockObj = $esMock->expects($this->once())
                          ->method($method);

        if (isset($this->testData[$esParams]) === true)
        {
            $expectedSearchParams = $this->testData[$esParams];
            $mockObj->with($expectedSearchParams);
        }

        if (isset($this->testData[$esResponse]) === true)
        {
            $expectedSearchRes = $this->testData[$esResponse];
            $mockObj->willReturn($expectedSearchRes);
        }
    }

    protected function createEsMockAndSetExpectations(string $callee, string $method = 'search')
    {
        $mock = $this->createEsMock([$method]);

        $this->setEsMockExpectations($callee, $mock, $method);

        return $mock;
    }

    protected function mockSqlConnectorWithReplicaLag($replicaLag)
    {
        $connector = \Mockery::mock('RZP\Base\Database\Connectors\MySqlConnector', [$this->app])->makePartial();

        $connector->shouldReceive('getReplicationLagInMilli')
            ->with(\Mockery::type('string'))
            ->andReturnUsing(function () use ($replicaLag)
            {
                return $replicaLag;
            });

        return $connector;
    }

    protected function mockLedgerSns($count, & $snsPayloadArray = [])
    {
        $sns = \Mockery::mock('RZP\Services\Aws\Sns');

        $this->app->instance('sns', $sns);

        $this->app['config']->set('applications.ledger.enabled', true);

        $sns->shouldReceive('publish')
            ->times($count)
            ->with(\Mockery::on(function (string $input) use (& $snsPayloadArray)
            {
                $jsonDecodedInput = json_decode($input, true);

                array_push($snsPayloadArray, $jsonDecodedInput);

                return true;

            }), \Mockery::type('string'));
    }

    /**
     * Asserts that basicauth has exactly same passport value set as expected
     * in `expected_passport` key of test data.
     * @return void
     */
    protected function assertPassport()
    {
        $callee = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $expected = $this->testData[$callee]['expected_passport'];
        $actual = $this->app['basicauth']->getPassport();

        $this->assertArraySelectiveEquals($expected, $actual);
        $this->assertEqualsCanonicalizing(array_keys($expected), array_keys($actual));
    }

    /**
     * See function assertPassport().
     * @param  string $key   Key in dotted notation
     * @param  string $regex Optional regex for key's value to assert with
     * @return void
     */
    protected function assertPassportKeyExists(string $key, string $regex = null)
    {
        $actual = array_dot($this->app['basicauth']->getPassport());

        $this->assertArrayHasKey($key, $actual);

        if ($regex)
        {
            $this->assertMatchesRegularExpression($regex, $actual[$key]);
        }
    }

    public function mockBeam(callable $callback)
    {
        $beamServiceMock = $this->getMockBuilder(BeamService::class)
                                ->setConstructorArgs([$this->app])
                                ->onlyMethods(['beamPush'])
                                ->getMock();

        $beamServiceMock->method('beamPush')->will($this->returnCallback($callback));

        $this->app['beam']->setMockService($beamServiceMock);
    }
}
