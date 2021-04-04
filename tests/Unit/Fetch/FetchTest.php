<?php

namespace RZP\Tests\Unit\Fetch;

use RZP\Base\Fetch;
use RZP\Tests\TestCase;
use RZP\Base\JitValidator;
use RZP\Constants\Entity as E;
use RZP\Tests\Unit\Mock\BasicAuth;
use RZP\Tests\Unit\MocksAppServices;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Http\BasicAuth\Type as AuthType;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\BadRequestValidationFailureException;

class FetchTest extends TestCase
{
    use MocksAppServices;

    /**
     * @var BasicAuth
     */
    protected $ba;

    /**
     * Allowed keys in valid rules (E.g. defaults, private, private_auth etc.)
     *
     * @var array
     */
    protected $validRuleTypes;

    /**
     * @var JitValidator
     */
    protected $validator;

    /**
     * Contains entity to entityFetch map that we need to run tests for
     *
     * @var array
     */
    protected $entityList;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/FetchTestData.php';

        parent::setUp();

        $this->ba = $this->mockBasicAuth();

        $this->entityList = $this->getEntitiesToTest();
    }

    /**
     * Asserts that the Fetch classes are defined correctly semantic wise.
     * E.g. checks data structure of rules (list / associative array) and valid
     * keys etc.
     */
    public function testValidateFetchClasses()
    {
        //
        // These properties are only required in validateRules and ValidateAccessTypes
        // methods, thus no need to set them from setUp.
        //
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
        $this->ba->privateAuth();

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
     * @param null|string $expectedException
     */
    protected function runForEntityAndType(
        string $entity,
        string $type,
        string $expectedException = null)
    {
        $tests = $this->getTestDataForEntityAndType($entity, $type);

        foreach ($tests as $test)
        {
            $actualException = null;

            try
            {
                $this->entityList[$entity]->processFetchParams($test);

                $this->assertArrayHasKey('count', $test, $entity);
            }
            catch (\Exception $e)
            {
                $actualException = get_class($e);
            }

            $this->assertSame($expectedException, $actualException, $entity);
        }
    }

    /**
     * Read all entities and run tests for given type.
     * Apart from AuthTypes from type here, we can use
     * custom types like, <AuthType>+<ExceptionClass>
     *
     * @param string      $type
     * @param null|string $expectedException
     */
    protected function runForType(string $type, string $expectedException = null)
    {
        foreach ($this->entityList as $entity => $fetch)
        {
            $this->runForEntityAndType($entity, $type, $expectedException);
        }
    }

    /**
     * All entities we need to test, Uses reflection class against E to get
     * defined list of entities. For each of them we get corresponding fetch
     * classes if defined. This way if someone adds new Fetch class basic tests
     * are done without any change in tests.
     *
     * Note: PHP caches the reflection class, thus time complexity is negligible
     *
     * @return array
     */
    protected function getEntitiesToTest()
    {
        $entities = (new \ReflectionClass(E::class))->getConstants();

        $staticVariables = (new \ReflectionClass(E::class))->getStaticProperties();

        $externalEntities = $staticVariables['externalServiceClass'];

        // Have to remove the external entities, as corresponding entities doesn't exist on API
        $entities = array_filter($entities, function ($value, $key) use ($externalEntities) {

            return ((is_array($value) === false) and (isset($externalEntities[$value]) === false));

        }, ARRAY_FILTER_USE_BOTH);

        $fetchs = [];

        array_forget($entities, ['CACHED_ENTITIES', 'KEYLESS_ALLOWED_ENTITIES']);

        foreach ($entities as $entity)
        {
            $fetch = E::getEntityFetch($entity);

            if ($fetch !== null)
            {
                $fetchs[$entity] = $fetch;
            }
        }

        return $fetchs;
    }

    /**
     * Returns test data for given entity and type. Currently it reads data
     * from self::$testData, but for more complex entities like payments
     * we can have separate testData file to keep contents readable and modular.
     *
     * @param string $entity
     * @param string $type
     * @return array
     */
    protected function getTestDataForEntityAndType(string $entity, string $type)
    {
        if (isset($this->testData[$entity]) === false)
        {
            $this->fail('Entity needs to be declared in fetch Test Data : '. $entity);
        }

        $entityTests = $this->testData[$entity];

        return $entityTests[$type] ?? [];
    }

    /**
     * Method validates rules defined in Fetch class. Rules can only have keys
     * from a predefined set (i.e. $this->validRuleTypes).
     *
     * @param Fetch $fetch
     */
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
                //
            }
            catch (BadRequestException $e)
            {
                //
            }
            catch(InvalidArgumentException $e){
                //
            }
        }
    }

    /**
     * Validates ACCESSES defined in Fetch class.
     *
     * @param Fetch $fetch
     */
    protected function validateAccessTypes(Fetch $fetch)
    {
        $accessTypes = $fetch::ACCESSES;

        // Validates keys are valid
        $invalidAccesses = array_diff(array_keys($accessTypes), $this->validRuleTypes);

        if (count($invalidAccesses) > 0)
        {
            $this->fail('Invalid rule types :' . implode(', ', $invalidAccesses));
        }

        // Iterates through accesses and checks that each of them are valid list
        // and there is no duplicate across.
        $mergedAccesses = [];

        foreach ($accessTypes as $type => $accesses)
        {
            $this->assertTrue(is_array($accesses), 'Accesses is not array for ' . get_class($fetch));

            if (array_values($accesses) !== $accesses)
            {
                $this->fail('Accesses must be non associative for ' . get_class($fetch));
            }


            // Removing this as now we require some similar access rules across different auth
            // Example : for dispute fetch, expand_each rule is needed for both in proxy and admin auth.
//            $duplicate = array_intersect($mergedAccesses, $accesses);
//
//            if (count($duplicate) > 0)
//            {
//                $duplicateAccesses = implode(',', $duplicate);
//                $this->fail('Duplicate accesses for ' . get_class($fetch) . ' : ' . $duplicateAccesses);
//            }

            $mergedAccesses = array_merge($mergedAccesses, $accesses);
        }
    }

}
