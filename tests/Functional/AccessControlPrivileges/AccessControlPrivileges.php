<?php

namespace RZP\Tests\Functional\AccessControlPrivileges;

use DB;
use \WpOrg\Requests\Response;

use AuthzAdmin\Client\Model as AuthzAdminModel;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class AccessControlPrivileges extends TestCase
{
    const DEFAULT_X_MERCHANT_ID = '100000merchant';
    const EXISTING_MERCHANT_FOR_INVITED_USER_ID = '10000000000001';
    const DEFAULT_MERCHANT_ID = '10000000000000';
    private $str;

    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->unitTestCase = new \Tests\Unit\TestCase();

        $this->testDataFilePath = __DIR__ . '/helpers/AccessControlPrivilegesTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();

        $this->mockCACMigrationExperiment('inactive');
    }

    public function testFetchAllPrivileges()
    {
        $this->fixtures->create('merchant',[ 'id' => self::DEFAULT_X_MERCHANT_ID ]);

        $this->fixtures->create('merchant_detail', [
            'activation_status' => 'activated',
            'merchant_id'       => self::DEFAULT_X_MERCHANT_ID,
            'business_type'     => '2',
        ]);

        $user1 = $this->fixtures->user->createEntityInTestAndLive('user', []);

        $this->ba->proxyAuth('rzp_test_' . self::DEFAULT_X_MERCHANT_ID, $user1->getId());

        $this->createMerchantUserMappingInLiveAndTest($user1['id'], self::DEFAULT_X_MERCHANT_ID, 'owner');

        $this->createPrivileges();

        $response = $this->startTest();
    }

    public function createPrivileges()
    {
        $privilege1 = $this->fixtures->create('access_control_privileges',
            [
                'id'          => '1000privilege1',
                'name'        => 'Account Setting',
                'label'       => 'account_setting',
                'description' => 'A/c setting test description',
                'parent_id'   => null,
                'visibility'  => 1,
            ]);

        $privilege2 = $this->fixtures->create('access_control_privileges',
            [
                'id'          => '1000privilege2',
                'name'        => 'Tax Setting',
                'label'       => 'tax_setting',
                'description' => 'Tax setting test description',
                'extra_data'  => [
                    'tool_tip'      => 'PRIVILEGE 2',
                ],
                'parent_id'   => $privilege1->getId(),
                'visibility'  => 1,
            ]);

        $this->str = '1000privilege3';
        $privilege3 = $this->fixtures->create('access_control_privileges',
            [
                'id'          => '' . $this->str . '',
                'name'        => 'Business Setting',
                'label'       => 'business_setting',
                'description' => 'Business setting test description',
                'extra_data'  => [
                    'tool_tip'      => 'PRIVILEGE 3',
                ],
                'parent_id'   => $privilege1->getId(),
                'visibility'  => 1,
            ]);

        $accessPolicy1 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege2->getId(),
            'action'        => 'view',
            'authz_roles'   => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
        ]);

        $accessPolicy2 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege2->getId(),
            'action'        => 'create',
            'authz_roles'   => ['authz_roles_1_4', 'authz_roles_1_5'],
        ]);

        $accessPolicy3 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege3->getId(),
            'action'        => 'view',
            'authz_roles'   => ['authz_roles_2_1', 'authz_roles_2_2', 'authz_roles_2_3'],
        ]);

        $accessPolicy4 = $this->fixtures->create('access_policy_authz_roles_map', [
            'privilege_id'  => $privilege3->getId(),
            'action'        => 'create',
            'authz_roles'   => ['authz_roles_2_4', 'authz_roles_2_5'],
        ]);
    }

    protected function createMerchantUserMappingInLiveAndTest(string $userId, string $merchantId, string $role, string $roleId = null)
    {
        DB::connection('live')->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'product'     => 'banking',
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);

        DB::connection('test')->table('merchant_users')
            ->insert([
                'merchant_id' => $merchantId,
                'user_id'     => $userId,
                'role'        => $role,
                'product'     => 'banking',
                'created_at'  => 1493805150,
                'updated_at'  => 1493805150
            ]);
    }

    public function testFetchAllPrivilegesCACMigration()
    {
        $this->ba->proxyAuth();

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // mock is required to pass the basic auth check
        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIListPrivileges')
            ->once()
            ->withArgs(function($orgId, $expandActions, $expandRoles, $paginationToken, $visibility)
            {
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(true, $expandActions);
                $this->assertEquals(1, $visibility);

                return true;
            })
            ->andReturn(new AuthzAdminModel\V1ListPrivilegesResponse([
                'items' => [
                    new AuthzAdminModel\V1Privilege([
                        'id' => 'JqqlbH4S5QzvPt',
                        'name' => 'Account Statement & Balance',
                        'description' => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
                        'label' => 'accountStatement',
                        'parent_id' => null,
                        'org_id' => 'razorpayx',
                        'view_position' => 300,
                        'visibility' => 1,
                        'actions' => [
                            new AuthzAdminModel\V1PrivilegeRoleMapping([
                                'id' => 'JqqlbkKtqdfGbd',
                                'action' => 'view',
                                'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
                                'metadata' => new AuthzAdminModel\V1PrivilegeRoleMappingMetadata([
                                    'label' => 'View',
                                    'tooltip' => '',
                                    'description' => 'View account statement and balance',
                                ]),
                                'org_id' => 'razorpayx',
                                'roles' => null,
                                'privilege_id' => 'JqqlbH4S5QzvPt',
                            ]),
                        ],
                        'extra_data' => null,
                    ]),
                    new AuthzAdminModel\V1Privilege([
                        'id' => 'JqqlbJ1rC3b15x',
                        'name' => 'Payouts',
                        'description' => 'Single, bulk, tally payouts, payout links, payouts on invoices, contacts & fund accounts',
                        'label' => 'payouts',
                        'parent_id' => null,
                        'org_id' => 'razorpayx',
                        'view_position' => 200,
                        'visibility' => 1,
                        'actions' => [
                            new AuthzAdminModel\V1PrivilegeRoleMapping([
                                'id' => 'JqqlbnvU4qTC96',
                                'action' => 'view',
                                'role_ids' => ['authz_roles_2_1', 'authz_roles_2_2', 'authz_roles_2_3'],
                                'metadata' => new AuthzAdminModel\V1PrivilegeRoleMappingMetadata([
                                    'label' => 'View',
                                    'tooltip' => 'View by default on access to create, mark as paid',
                                    'description' => 'View and download payouts',
                                ]),
                                'org_id' => 'razorpayx',
                                'roles' => null,
                                'privilege_id' => 'JqqlbJ1rC3b15x',
                            ]),
                        ],
                        'extra_data' => null,
                    ]),
                    new AuthzAdminModel\V1Privilege([
                        'id' => 'JqqlbH4S5QzvPz',
                        'name' => 'Petty Cash',
                        'description' => 'Manage petty cash transactions',
                        'label' => 'pettyCash',
                        'parent_id' => null,
                        'org_id' => 'razorpayx',
                        'view_position' => 900,
                        'visibility' => 1,
                        'extra_data' => null,
                    ]),
                ],
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $this->startTest();
    }

    public function testFetchAllPrivilegesWithParentCACMigration()
    {
        $this->ba->proxyAuth();

        $this->mockCACMigrationExperiment('active', self::DEFAULT_MERCHANT_ID);

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        // mock is required to pass the basic auth check
        $authzAdminClientMock->shouldReceive('adminAPIGetRole')
            ->withArgs(function($roleId, $orgId, $ownerId, $expandChildren)
            {
                $this->assertEquals('Owner', $roleId);
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(self::DEFAULT_MERCHANT_ID, $ownerId);
                $this->assertTrue($expandChildren);

                return true;
            })
            ->once()
            ->andReturn(new AuthzAdminModel\V1Role([
                'id'            => '100standardRole1',
                'name'          => 'Owner',
                'org_id'        => 'razorpayx',
                'type'          => AuthzAdminModel\V1RolePolicyType::STANDARD,
                'owner_type'    => 'merchant',
                'owner_id'      => self::DEFAULT_MERCHANT_ID,
                'created_by'    => 'MerchantUser01',
                'children'      => null,
                'description'   => 'Perform all tasks',
            ]));

        $authzAdminClientMock->shouldReceive('adminAPIListPrivileges')
            ->once()
            ->withArgs(function($orgId, $expandActions, $expandRoles, $paginationToken, $visibility)
            {
                $this->assertEquals('razorpayx', $orgId);
                $this->assertEquals(true, $expandActions);
                $this->assertEquals(1, $visibility);

                return true;
            })
            ->andReturn(new AuthzAdminModel\V1ListPrivilegesResponse([
                'items' => [
                    new AuthzAdminModel\V1Privilege([
                        'id' => 'JqqlbH4S5QzvPt',
                        'name' => 'Account Statement & Balance',
                        'description' => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
                        'label' => 'accountStatement',
                        'parent_id' => 'JqqlbJ1rC3b15x',
                        'org_id' => 'razorpayx',
                        'view_position' => 300,
                        'visibility' => 1,
                        'actions' => [
                            new AuthzAdminModel\V1PrivilegeRoleMapping([
                                'id' => 'JqqlbkKtqdfGbd',
                                'action' => 'view',
                                'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
                                'metadata' => new AuthzAdminModel\V1PrivilegeRoleMappingMetadata([
                                    'label' => 'View',
                                    'tooltip' => '',
                                    'description' => 'View account statement and balance',
                                ]),
                                'org_id' => 'razorpayx',
                                'roles' => null,
                                'privilege_id' => 'JqqlbH4S5QzvPt',
                            ]),
                        ],
                        'extra_data' => null,
                    ]),
                    new AuthzAdminModel\V1Privilege([
                        'id' => 'JqqlbJ1rC3b15x',
                        'name' => 'Payouts',
                        'description' => 'Single, bulk, tally payouts, payout links, payouts on invoices, contacts & fund accounts',
                        'label' => 'payouts',
                        'parent_id' => null,
                        'org_id' => 'razorpayx',
                        'view_position' => 200,
                        'visibility' => 1,
                        'actions' => [
                            new AuthzAdminModel\V1PrivilegeRoleMapping([
                                'id' => 'JqqlbnvU4qTC96',
                                'action' => 'view',
                                'role_ids' => ['authz_roles_2_1', 'authz_roles_2_2', 'authz_roles_2_3'],
                                'metadata' => new AuthzAdminModel\V1PrivilegeRoleMappingMetadata([
                                    'label' => 'View',
                                    'tooltip' => 'View by default on access to create, mark as paid',
                                    'description' => 'View and download payouts',
                                ]),
                                'org_id' => 'razorpayx',
                                'roles' => null,
                                'privilege_id' => 'JqqlbJ1rC3b15x',
                            ]),
                        ],
                        'extra_data' => null,
                    ]),
                ],
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $this->startTest();
    }

    public function testCreatePrivilegeCACMigration()
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/cac/privileges_authz',
            'content' => [
                'name'          => 'Account Statement & Balance',
                'description'   => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
                'label'         => 'accountStatement',
                'parent_id'     => null,
                'view_position' => 300,
                'visibility'    => 1,
            ],
        ];

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPICreatePrivilege')
            ->once()
            ->andReturn(new AuthzAdminModel\V1Privilege([
                'id' => 'JqqlbH4S5QzvPt',
                'name' => 'Account Statement & Balance',
                'description' => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
                'label' => 'accountStatement',
                'parent_id' => null,
                'org_id' => 'razorpayx',
                'view_position' => 300,
                'visibility' => 1,
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals([
            'id' => 'JqqlbH4S5QzvPt',
            'name' => 'Account Statement & Balance',
            'description' => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
            'label' => 'accountStatement',
            'parent_id' => null,
            'org_id' => 'razorpayx',
            'view_position' => 300,
            'visibility' => 1,
        ], $response);
    }

    public function testUpdatePrivilegeCACMigration()
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'PATCH',
            'url'     => '/cac/privileges_authz/JqqlbH4S5QzvPt',
            'content' => [
                'name'          => 'Account Statement & Balance',
                'description'   => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
                'label'         => 'accountStatement',
                'parent_id'     => null,
                'view_position' => 300,
                'visibility'    => 1,
            ],
        ];

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPIUpdatePrivilege')
            ->once()
            ->andReturn(new AuthzAdminModel\V1Privilege([
                'id' => 'JqqlbH4S5QzvPt',
                'name' => 'Account Statement & Balance',
                'description' => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
                'label' => 'accountStatement',
                'parent_id' => null,
                'org_id' => 'razorpayx',
                'view_position' => 300,
                'visibility' => 1,
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals([
            'id' => 'JqqlbH4S5QzvPt',
            'name' => 'Account Statement & Balance',
            'description' => 'All transactions from RazorpayX (Payouts, Payroll etc.) and outside (via bank portal)',
            'label' => 'accountStatement',
            'parent_id' => null,
            'org_id' => 'razorpayx',
            'view_position' => 300,
            'visibility' => 1,
        ], $response);
    }

    public function testCreatePrivilegeRoleMappingCACMigration()
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/cac/privilege_role_mappings',
            'content' => [
                'action' => 'view',
                'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
                'metadata' => [
                    'label' => 'View',
                    'tooltip' => '',
                    'description' => 'View account statement and balance',
                ],
                'privilege_id' => 'JqqlbH4S5QzvPt',
            ],
        ];


        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPICreatePrivilegeRoleMapping')
            ->once()
            ->andReturn(new AuthzAdminModel\V1PrivilegeRoleMapping([
                'id' => 'JqqlbkKtqdfGbd',
                'action' => 'view',
                'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
                'metadata' => new AuthzAdminModel\V1PrivilegeRoleMappingMetadata([
                    'label' => 'View',
                    'tooltip' => '',
                    'description' => 'View account statement and balance',
                ]),
                'org_id' => 'razorpayx',
                'roles' => null,
                'privilege_id' => 'JqqlbH4S5QzvPt',
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals([
            'id' => 'JqqlbkKtqdfGbd',
            'action' => 'view',
            'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
            'org_id' => 'razorpayx',
            'privilege_id' => 'JqqlbH4S5QzvPt',
            'metadata' => [
                'label' => 'View',
                'tooltip' => '',
                'description' => 'View account statement and balance',
            ],
        ], $response);
    }

    public function testUpdatePrivilegeRoleMappingCACMigration()
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'PATCH',
            'url'     => '/cac/privilege_role_mappings/JqqlbkKtqdfGbd',
            'content' => [
                'action' => 'view',
                'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
                'metadata' => [
                    'label' => 'View',
                    'tooltip' => '',
                    'description' => 'View account statement and balance',
                ],
                'privilege_id' => 'JqqlbH4S5QzvPt',
            ],
        ];

        $authzAdminClientMock = \Mockery::mock(\AuthzAdmin\Client\Api\AdminAPIApi::class);

        $authzAdminClientMock->shouldReceive('adminAPIUpdatePrivilegeRoleMapping')
            ->once()
            ->andReturn(new AuthzAdminModel\V1PrivilegeRoleMapping([
                'id' => 'JqqlbkKtqdfGbd',
                'action' => 'view',
                'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
                'metadata' => new AuthzAdminModel\V1PrivilegeRoleMappingMetadata([
                    'label' => 'View',
                    'tooltip' => '',
                    'description' => 'View account statement and balance',
                ]),
                'org_id' => 'razorpayx',
                'roles' => null,
                'privilege_id' => 'JqqlbH4S5QzvPt',
            ]));

        $this->app->instance('authzXPlatformAdmin', $authzAdminClientMock);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals([
            'id' => 'JqqlbkKtqdfGbd',
            'action' => 'view',
            'role_ids' => ['authz_roles_1_1', 'authz_roles_1_2', 'authz_roles_1_3'],
            'org_id' => 'razorpayx',
            'privilege_id' => 'JqqlbH4S5QzvPt',
            'metadata' => [
                'label' => 'View',
                'tooltip' => '',
                'description' => 'View account statement and balance',
            ],
        ], $response);
    }

    private function mockCACMigrationExperiment($result, $merchantId = null)
    {
        $splitzMock = \Mockery::mock(SplitzService::class, [$this->app])->makePartial();

        $input = [
            'id'            => $merchantId ?? self::DEFAULT_X_MERCHANT_ID,
            'experiment_id' => env('CAC_MIGRATION_SPLITZ_EXPERIMENT_ID'),
        ];

        $response = [
            'response' => [
                'variant' => [
                    'name' => $result,
                ]
            ]
        ];

        $splitzMock->shouldReceive('evaluateRequest')
            ->zeroOrMoreTimes()
            ->with($input)
            ->andReturn($response);

        $this->app->instance('splitzService', $splitzMock);
    }
}
