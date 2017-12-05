<?php

namespace RZP\Tests\Unit\Models\Fetch;

use RZP\Base\Fetch;
use RZP\Tests\TestCase;
use RZP\Base\JitValidator;
use RZP\Constants\Entity as E;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Tests\Unit\Models\Mock\MockBasicAuth;
use RZP\Tests\Unit\Models\Mock\MocksAppServices;
use RZP\Exception\BadRequestValidationFailureException;

class FetchTest extends TestCase
{
    use MocksAppServices;
    /**
     * @var MockBasicAuth
     */
    protected $ba;

    /**
     * Allowed types in valid rules
     *
     * @var array
     */
    protected $validRuleTypes;

    /**
     * @var JitValidator
     */
    protected $validator;

    /**
     * Contains entity to entityFetch list we need run tests for.
     *
     * @var array
     */
    protected $entityList;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/FetchTestData.php';

        parent::setUp();

        $this->ba = $this->mockBasicAuth();

        $this->entityList = $this->getEntitiesToTest();
    }

    public function testValidateFetchClasses()
    {
        // These properties are only required in validateRules and ValidateAccessTypes
        // methods, thus no need to set them from setUp.
        $this->validRuleTypes = array_keys(Fetch::DEFAULT_RULES);

        $this->validator = new JitValidator([]);

        foreach ($this->entityList as $fetch)
        {
            $this->assertInstanceOf(Fetch::class, $fetch);

            $this->validateRuleTypes($fetch);

            $this->validateAccessTypes($fetch);
        }

    }

    public function testForPrivateAuth()
    {
        $this->ba->appAuth();

        $this->runForType(AuthType::PRIVATE_AUTH);
    }

    public function testForProxyAuth()
    {
        $this->ba->proxyAuth();

        $this->runForType(AuthType::PROXY_AUTH);
    }

    public function testForPrivilegeAuth()
    {
        $this->ba->appAuth();

        $this->runForType(AuthType::PRIVILEGE_AUTH);
    }

    public function testForAdminAuth()
    {
        $this->ba->adminAuth();

        $this->runForType(AuthType::ADMIN_AUTH);
    }

    /**
     * Here we takes tests for privilege auth and runs
     * for private auth, this validates that exception
     * ExtraFieldException is thrown.
     */
    public function testExtraFieldErrorForPrivateAuth()
    {
        $this->ba->privateAuth();

        $this->runForType(AuthType::PRIVILEGE_AUTH, ExtraFieldsException::class);
    }

    /*
     *  Helpers
     */

    /**
     * Run tests for given entity, assert success.
     * Third param exception if set makes sure same
     * exception is thrown from Fetch::processFetchParams.
     *
     * @param string $entity
     * @param string $type
     * @param null $exception
     */
    protected function runForEntityAndType(string $entity, string $type, $exception = null)
    {
        $tests = $this->getTestDataForEntityAndType($entity, $type);

        foreach ($tests as $test)
        {
            if ($exception === null)
            {
                $this->entityList[$entity]->processFetchParams($test);

                $this->assertArrayHasKey('count', $test, $entity);
            }
            else
            {
                try
                {
                    $this->entityList[$entity]->processFetchParams($test);
                }
                catch (\Exception $e)
                {
                    $this->assertInstanceOf($exception, $e, $entity);

                    continue;
                }

                $this->fail('Exception not throw : ' . $entity);
            }
        }
    }

    /**
     * Read all entities and run tests for given type.
     * Apart from AuthTypes from type here, we can use
     * custom types like, <AuthType>+<ExceptionClass>
     *
     * @param $type
     * @param null $exception
     */
    protected function runForType($type, $exception = null)
    {
        foreach ($this->entityList as $entity => $fetch)
        {
            $this->runForEntityAndType($entity, $type, $exception);
        }
    }

    /**
     * All entities we need to test, Uses reflection class to resolve entities,
     * Note: Php caches the reflection class, thus time complexity is negligible
     *
     * @return array
     */
    protected function getEntitiesToTest()
    {
        $allEntities = (new \ReflectionClass(E::class))->getConstants();

        $entityFetchList = [];

        foreach ($allEntities as $entity)
        {
            $fetch = E::getEntityFetch($entity);

            if (empty($fetch) === false)
            {
                $entityFetchList[$entity] = $fetch;
            }
        }

        return $entityFetchList;
    }

    protected function getEntityFetch(string $entity)
    {
        return E::getEntityFetch($entity);
    }

    /**
     * Returns test data for given entity and type. Currently it reads data
     * from self::$testData, but for more complex entities like payments
     * we can have separate testData file to keep contain readable and modular.
     *
     * @param string $entity
     * @param string $type
     * @return array
     */
    protected function getTestDataForEntityAndType(string $entity, string $type)
    {
        if (isset($this->testData[$entity]) === false)
        {
            $this->fail('Entity needs to decalared in fetch Test Data : '. $entity);
        }

        $entityTests = $this->testData[$entity];

        return $entityTests[$type] ?? [];
    }

    protected function validateRuleTypes(Fetch $fetch)
    {
        $ruleTypes = $fetch::RULES;

        $invalidRules = array_diff(array_keys($ruleTypes), $this->validRuleTypes);

        if (count($invalidRules) > 0)
        {
            $this->fail('Invalid rule types :' . implode(', ', $invalidRules));
        }

        // This check makes sure that each rule is called
        foreach ($ruleTypes as $type => $rules)
        {
            $this->assertTrue(is_array($rules), 'Rules is not array for ' . get_class($fetch));

            $this->validateRuleDefinition($fetch, $rules);
        }
    }

    /**
     * Checks each rules definition one by one with dummy value(0)
     * Thrown exception other than BadRequestValidationFailureException,
     * or BadRequestException signifies that rule definition is wrong
     *
     * @param Fetch $fetch
     * @param array $rules
     */
    protected function validateRuleDefinition(Fetch $fetch, array $rules)
    {
        foreach ($rules as $param => $rule)
        {
            try
            {
                $this->validator->caller($fetch)
                                ->rules([$param => $rule])
                                ->validate([$param => 0]);
            }
            catch (BadRequestValidationFailureException $e)
            {
                continue;
            }
            catch (BadRequestException $e)
            {
                continue;
            }
        }
    }

    protected function validateAccessTypes(Fetch $fetch)
    {
        $accessTypes = $fetch::ACCESSES;

        $invalidAccesses = array_diff(array_keys($accessTypes), $this->validRuleTypes);

        if (count($invalidAccesses) > 0)
        {
            $this->fail('Invalid rule types :' . implode(', ', $invalidAccesses));
        }

        $mergedAccesses = [];

        foreach ($accessTypes as $type => $accesses)
        {
            $this->assertTrue(is_array($accesses), 'Accesses is not array for ' . get_class($fetch));

            if (array_values($accesses) !== $accesses)
            {
                $this->fail('Accesses must be non associative for ' . get_class($fetch));
            }

            $duplicate = array_intersect($mergedAccesses, $accesses);

            if (count($duplicate) > 0)
            {
                $duplicateAccesses = implode(',', $duplicate);
                $this->fail('Duplicate accesses for ' . get_class($fetch) . ' : ' . $duplicateAccesses);
            }

            $mergedAccesses = array_merge($mergedAccesses, $accesses);
        }
    }

}
