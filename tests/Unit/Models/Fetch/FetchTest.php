<?php

namespace RZP\Tests\Unit\Models\Fetch;

use RZP\Base\Fetch;
use RZP\Constants\Entity;
use RZP\Tests\TestCase;
use RZP\Base\JitValidator;
use RZP\Http\BasicAuth\Type;
use RZP\Exception\ExtraFieldsException;
use RZP\Tests\Unit\Models\Mock\MockApp;
use RZP\Tests\Unit\Models\Mock\MockBasicAuth;
use RZP\Exception\BadRequestValidationFailureException;

class FetchTest extends TestCase
{
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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/FetchTestData.php';

        parent::setUp();

        $this->ba = MockApp::mockAuth();
    }

    public function testValidateFetchClasses()
    {
        $this->validRuleTypes = array_keys(Fetch::DEFAULT_RULES);

        $this->validator = new JitValidator([]);

        foreach ($this->testData as $entity => $auths)
        {
            $fetch = $this->getFetchForEntity($entity);

            $this->assertInstanceOf(Fetch::class, $fetch);

            $this->validateRuleTypes($fetch);

            $this->validateAccessTypes($fetch);
        }

    }

    public function testForPrivateAuth()
    {
        $entities = $this->getTestData(Type::PRIVATE_AUTH);

        $this->ba->appAuth();

        $this->runForEntities($entities);
    }

    public function testForProxyAuth()
    {
        $entities = $this->getTestData(Type::PROXY_AUTH);

        $this->ba->proxyAuth();

        $this->runForEntities($entities);
    }

    public function testForPrivilegeAuth()
    {
        $entities = $this->getTestData(Type::PRIVILEGE_AUTH);

        $this->ba->appAuth();

        $this->runForEntities($entities);
    }

    public function testForAdminAuth()
    {
        $entities = $this->getTestData(Type::ADMIN_AUTH);

        $this->ba->adminAuth();

        $this->runForEntities($entities);
    }

    public function testExtraFieldErrorForPrivateAuth()
    {
        $entities = $this->getTestData(Type::PRIVILEGE_AUTH);

        $this->ba->privateAuth();

        $this->runForEntities($entities, ExtraFieldsException::class);
    }

    /*
     *  Helpers
     */

    protected function runForEntity(string $entity, array $tests, $exception = null)
    {
        $fetch = $this->getFetchForEntity($entity);

        foreach ($tests as $test)
        {
            if ($exception === null)
            {
                $fetch->processFetchParams($test);

                $this->assertArrayHasKey('count', $test);
            }
            else
            {
                try
                {
                    $fetch->processFetchParams($test);
                }
                catch (\Exception $e)
                {
                    $this->assertInstanceOf($exception, $e);

                    continue;
                }

                $this->fail('Exception not throw : ' . $e);
            }
        }
    }

    protected function runForEntities(array $entities, $exception = null)
    {
        foreach ($entities as $entity => $tests)
        {
            $this->runForEntity($entity, $tests, $exception);
        }
    }

    protected function getFetchForEntity(string $entity)
    {
        return Entity::getEntityFetch($entity);
    }

    protected function getTestData(string $auth)
    {
        $data = [];

        foreach ($this->testData as $entity => $auths)
        {
            if (isset($auths[$auth]))
            {
                $data[$entity] = $auths[$auth];
            }
        }

        return $data;
    }

    protected function validateRuleTypes(Fetch $fetch)
    {
        $ruleTypes = $fetch::RULES;

        $invalidRules = array_diff(array_keys($ruleTypes), $this->validRuleTypes);

        if (count($invalidRules) > 0)
        {
            $this->fail('Invalid rule types :' . implode(', ', $invalidRules));
        }

        /*
         * This check makes sure that each rules is called
         */
        foreach ($ruleTypes as $type => $rules)
        {
            $this->assertTrue(is_array($rules), 'Rules is not array for ' . get_class($fetch));

            $filled = $this->getMockedValuesForRules($fetch, $type);

            try
            {
                $this->validator->caller($fetch)
                                ->rules($rules)
                                ->validate($filled);
            }
            catch (BadRequestValidationFailureException $e)
            {
                continue;
            }
        }
    }

    /**
     * Currently, Mocks all the fields with zero, In future if required
     * We can extend this function to read mocked values from mocked fetch class
     *
     * @param Fetch $fetch
     * @param string $type
     * @return array
     */
    protected function getMockedValuesForRules(Fetch $fetch, string $type)
    {
        return array_fill_keys(array_keys($fetch::RULES[$type]), 0);
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

            $duplicateAccesses = array_intersect($mergedAccesses, $accesses);

            if (count($duplicateAccesses) > 0)
            {
                $this->fail('Duplicate accesses : '. implode(',', $duplicateAccesses));
            }
        }
    }

}
