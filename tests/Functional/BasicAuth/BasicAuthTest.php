<?php

namespace RZP\Tests\Functional\BasicAuth;

use RZP\Constants\Entity;
use RZP\Http\BasicAuth\Type;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Org;

class BasicAuthTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/BasicAuthData.php';

        parent::setUp();

        $this->ba->privateAuth();
    }

    public function testNoAuth()
    {
        $this->ba->noAuth();

        $this->startTest();

        $this->assertEquals('Basic realm="Razorpay"', $this->response->headers->get('WWW-Authenticate'));
    }

    public function testNoAuthOnJsonpRoute()
    {
        $this->ba->noAuth();

        $this->startTest();

        $this->assertEquals('Basic realm="Razorpay"', $this->response->headers->get('WWW-Authenticate'));
    }

    public function testWrongKeyOnPublicJsonpRoute()
    {
        $this->ba->publicAuth('rzp_test_TheTstWrongKey');

        $this->startTest();
    }

    /**
     * This also checks the effect of providing secret on
     * public route
     */
    public function testPrivateAuthOnPublicRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testAdminAuth()
    {
        $this->ba->adminAuth('test');

        $this->startTest();
    }

    public function testPrivateAuthOnAdminRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testUnauthorizedOnJsonpRoute()
    {
        $this->ba->publicAuth('rzp_test_TheTestAusdKey');

        $this->startTest();
    }

    public function testNoSecretOnPrivateRoute()
    {
        $this->ba->privateAuth(null, '');

        $this->startTest();
    }

    public function testPublicAuthWithWrongKeyId()
    {
        $this->ba->publicAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongKeyId()
    {
        $this->ba->privateAuth('abcdefgh820b0c06208ccd99');

        $this->startTest();
    }

    public function testPrivateAuthWithWrongSecret()
    {
        $this->ba->privateAuth(null, 'somerandomsecre');

        $this->startTest();
    }

    public function testAppAuthWithNoSecret()
    {
        $this->ba->appAuth('rzp_test', null);

        $this->startTest();
    }

    public function testPrivateAuthOnAppRoute()
    {
        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testProxyAuthOnPrivateRouteInCloud()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxyAuthOnPrivateRouteNotInCloud()
    {
        $this->ba->proxyAuth();

        $this->cloud = false;

        $this->startTest();
    }

    public function testPrivateAuthKeyNotExpired()
    {
        $this->ba->privateAuth();

        // Key expires 2 minutes from now
        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() + 120]);

        $this->startTest();
    }

    public function testPrivateAuthKeyExpired()
    {
        $this->ba->privateAuth();

        // Key expired 20 seconds ago
        $this->fixtures->edit('key', 'TheTestAuthKey', ['expired_at' => time() - 20]);

        $this->startTest();
    }

    public function testBasicAuthRealm()
    {
        ;
    }

    public function testAppRoutesWithPrivateAuth()
    {
        $this->ba->privateAuth();

        $internalRoutes = $this->app['api.route']->getApiRouteInCategory('internal');

        foreach ($internalRoutes as $routeName => $routeInfo)
        {
            $testData['request']['method'] = $routeInfo[0];
            $testData['request']['url'] = $routeInfo[1];

            $this->startTest($testData);
        }
    }

    public function testAppRoutesWithInvalidPrivateAuth()
    {
        $this->ba->privateAuth(null, '=');

        $internalRoutes = $this->app['api.route']->getApiRouteInCategory('internal');

        foreach ($internalRoutes as $routeName => $routeInfo)
        {
            $testData['request']['method'] = $routeInfo[0];
            $testData['request']['url'] = $routeInfo[1];

            $this->startTest($testData);
        }
    }

    public function testInvalidMerchantKeyForAppRouteAndNotExistentRoute()
    {
        ;
    }

    public function testValidMerchantKeyForAppRouteAndNonExistentRoute()
    {
        ;
    }

    public function testPublicQueryAuth()
    {
        $this->markTestIncomplete();

        $request = [
            'url' => '/payments/create/jsonp?keyid=rzp_test_TheTestAuthKey',
            'method' => 'GET',
        ];

        $this->ba->noAuth();

        $this->makeRequestAndGetContent($request);
    }

    public function testAppAuthWithAccount()
    {
        $this->ba->appAuth();

        $this->ba->addAccountAuth('10000000000000');

        $this->startTest();
    }

    public function testAdminAuthWithAccount()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $merchant = $this->fixtures->create(
            'merchant', ['org_id' => Org::RZP_ORG]);

        $admin->merchants()->attach($merchant);

        $this->ba->addAccountAuth($merchant->getId());

        $result = $this->startTest();

        $this->assertEquals($merchant->getId(), $result['id']);
    }

    public function testAccountAuthInvalidId()
    {
        $this->ba->appAuth();

        $this->ba->addAccountAuth('12345');

        $this->startTest();
    }

    public function testFetchApisWithAuthType()
    {
        // Create some pre fixtures
        $payment = $this->fixtures->create('payment');

        $entities = [
            Entity::ADDRESS => [
                Type::PRIVILEGE_AUTH => [
                    'entity_id' => str_random(14)
                ],
                'fixture'   => 'address'
            ],
            Entity::ADMIN => [
                Type::PRIVILEGE_AUTH => [
                    'email' => 'void@razorpay.com'
                ],
            ],
            Entity::ADMIN_LEAD => [
                Type::PRIVILEGE_AUTH => [
                    'email' => 'void@razorpay.com'
                ],
            ],
            Entity::GROUP => [
                Type::PRIVILEGE_AUTH => [
                    'name' => 'razarpay'
                ]
            ],
            Entity::ORG_FIELD_MAP => [
                Type::PRIVILEGE_AUTH => [
                    'entity_name' => 'fake'
                ]
            ],
            Entity::ORG_HOSTNAME => [
                Type::PRIVILEGE_AUTH => [
                    'hostname' => 'fake'
                ]
            ],
            Entity::ORG => [
                Type::PRIVILEGE_AUTH => [
                    'auth_type' => 'fake'
                ]
            ],
            Entity::PERMISSION => [
                Type::PRIVILEGE_AUTH => [
                    'category' => 'fake'
                ]
            ],
            Entity::ROLE => [
                Type::PRIVILEGE_AUTH => [
                    'name' => 'fake'
                ],
                Type::ADMIN_AUTH => [
                    'org_id' => str_random(14)
                ]
            ],
            Entity::ADJUSTMENT => [
                Type::PRIVILEGE_AUTH => [
                    'transaction_id' => str_random(14)
                ]
            ],
            Entity::BANK_ACCOUNT => [
                Type::PRIVILEGE_AUTH => [
                    'entity_id' => str_random(14)
                ],
                'fixture'   => 'bank_account'
            ],
            Entity::BANK_TRANSFER => [
                Type::PRIVILEGE_AUTH => [
                    'mode'       => 'fake',
                    'payment_id' => $payment['public_id']
                ],
            ],
            Entity::BATCH => [
                Type::PRIVILEGE_AUTH => [
                    'type'        => 'refund',
                    'merchant_id' => $payment['merchant_id']
                ],
            ],
            Entity::IIN => [
                Type::PRIVILEGE_AUTH => [
                    'type' => 'debit',
                    'iin'  => '110000'
                ],
                'fixture' => 'iin'
            ],
            Entity::CARD => [
                Type::PRIVILEGE_AUTH => [
                    'iin' => '110000'
                ],
                'fixture' => 'card'
            ],
            Entity::COUPON => [
                Type::PRIVILEGE_AUTH => [
                    'entity_id'   => str_random(14),
                    'merchant_id' => $payment['merchant_id'],
                    'entity_type' => 'promotion'
                ],
                'fixture' => 'coupon'
            ]
        ];

        foreach ($entities as $entityName => $entity)
        {
            // These check makes sure repository is reading fetch rules from fetch class
            $entityFetch = Entity::getEntityFetch($entityName);
            $this->assertTrue(
                (($entityFetch !== null) and ($entityFetch->isEnabled())),
                "Entity not enabled for '$entityName'"
            );

            // Check for private access
            if (isset($entity[Type::PRIVATE_AUTH]) === true)
            {
                $privateTestData = $this->testData[__FUNCTION__][Type::PRIVATE_AUTH];

                $privateTestData['request']['url'] .= str_plural($entityName);

                foreach ($entity[Type::PRIVATE_AUTH] as $field => $value)
                {
                    $privateTestData['request']['content'][$field] = $value;
                }

                $this->ba->privateAuth();

                $this->runRequestResponseFlow($privateTestData);
            }

            // Check for privilege access, proxy rules will also come under privilege
            if (isset($entity[Type::PRIVILEGE_AUTH]) === true)
            {
                // Test success for privilege allowed
                if (empty($entity['fixture']) === false)
                {
                    $this->fixtures->create($entity['fixture'], $entity[Type::PRIVILEGE_AUTH]);
                }
                $privilegeTestData = $this->testData[__FUNCTION__][Type::PRIVILEGE_AUTH];

                $privilegeTestData['request']['url'] .= $entityName;

                foreach ($entity[Type::PRIVILEGE_AUTH] as $field => $value)
                {
                    $privilegeTestData['request']['content'][$field] = $value;
                }

                $this->ba->appAuth();

                $content = $this->runRequestResponseFlow($privilegeTestData);

                $this->assertSame('collection',$content['entity']);

                if (empty($entity['fixture']) === false)
                {
                    $this->assertSame(1, $content['count']);
                }
            }
        }
    }

    public function startTest($testDataToReplace = array())
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $name = $trace[1]['function'];

        $testData = $this->testData[$name];

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }
}
