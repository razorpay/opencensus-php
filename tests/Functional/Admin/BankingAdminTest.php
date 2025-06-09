<?php

namespace RZP\Tests\Functional\Admin;

use DB;
use Hash;
use Mail;
use Cache;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\Entity;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\AdminsMeta;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;
use RZP\Services\Dcs\Configurations\Service as DcsConfigService;

class BankingAdminTest extends TestCase
{
    use RequestResponseFlowTrait;
    use CustomBrandingTrait;
    use WorkflowTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use MocksDiagTrait;

    protected function setUp(): void
    {
        ConfigKey::resetFetchedKeys();

        $this->testDataFilePath = __DIR__ . '/helpers/BankingAdminData.php';

        parent::setUp();

        $this->hostName = 'testing.testing.com';

        $this->idamOrg = $this->fixtures->create('org', [
            'id' => 'CLTnQqDj9Si8bx',
            'email' => 'randomadmin123@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type' => 'adfs',
        ]);

        $this->idamOrgId = $this->idamOrg->getId();

        $this->hostName = 'adfstesting.testing.com';

        $this->idamOrgHostName = $this->fixtures->create('org_hostname', [
            'org_id' => $this->idamOrgId,
            'hostname' => $this->hostName,
        ]);

        $this->adfsAuthToken = $this->getAuthTokenForOrg($this->idamOrg);

        $this->ba->adminAuth('test', $this->adfsAuthToken, $this->idamOrg->getPublicId());
    }

    private function createIdamAdmins(string $orgId, int $noOfAdmins, $expiredAt = null)
    {
        return $this->fixtures->times($noOfAdmins)->create('admin', [
            Admin\Entity::ORG_ID                => $orgId,
            Admin\Entity::EMAIL                 => 'randomadmin123@rzp.com',
            Admin\Entity::NAME                  => 'Test User',
            Admin\Entity::USERNAME              => 'testadmin',
            Admin\Entity::LAST_LOGIN_AT         => null,
            Admin\Entity::EXPIRED_AT            => $expiredAt,
            Admin\Entity::DISABLED              => false,
            Admin\Entity::ALLOW_ALL_MERCHANTS   => false,
        ]);

    }
    private function createAdminsMeta(string $adminId)
    {
        return $this->fixtures->create(Entity::ADMINS_META,
            [
                AdminsMeta\Entity::ADMIN_ID          => $adminId,
                AdminsMeta\Entity::UNIQUE_IDENTIFIER => 'xv6vxwe7@axisbank.com',
                AdminsMeta\Entity::AUTH_MODE         => 'adfs',
                AdminsMeta\Entity::DISABLED_REASON   =>  null,
                AdminsMeta\Entity::USER_DISABLED_AT  =>  null,
            ]);
    }

    private function timestampWithOffset(int $days = null): int
    {
        $now = Carbon::now(Timezone::IST);

        if ($days != null) {
            $now->addDays($days);
        }

        return $now->getTimestamp();
    }

    public function testInactiveIdamAdminLoginDueToDormancy()
    {
        $dormancy = 5;

        $this->mockDcsFetchConfiguration($dormancy);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $adminsMeta =  $this->createAdminsMeta($admin->getId());

        $timestamp = $this->timestampWithOffset(-$dormancy) - 100;

        $updateData = [
            Admin\Entity::LAST_LOGIN_AT => $timestamp,
            Admin\Entity::CREATED_AT    => $timestamp - 30,
            Admin\Entity::UPDATED_AT    => $timestamp - 20,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 1);

        $this->assertEquals($response['failure'] , 0);

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $disabledReason = $adminsMeta->getDisabledReason();

        $this->assertTrue($admin[Admin\Entity::DISABLED]);

        $this->assertNotNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNotNull($adminsMeta[AdminsMeta\Entity::DISABLED_REASON]);

        $this->assertEquals(AdminsMeta\Constant::IDAM_DORMANCY, $disabledReason);

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

    }

    public function testInactiveIdamAdminLogin()
    {
        $dormancy = 5;

        $this->mockDcsFetchConfiguration($dormancy);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $adminsMeta =  $this->createAdminsMeta($admin->getId());

        $updateData = [
            Admin\Entity::DISABLED      => true,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 0);

        $this->assertEquals($response['failure'] , 0);

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->assertTrue($admin[Admin\Entity::DISABLED]);

        $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNull($adminsMeta[AdminsMeta\Entity::DISABLED_REASON]);

        $this->startTest();
    }

    public function testActiveIdamAdminLoginDueToDormancy()
    {
        $dormancy = 5;

        $this->mockDcsFetchConfiguration($dormancy);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $adminsMeta =  $this->createAdminsMeta($admin->getId());

        $currentTimestamp = Carbon::now()->getTimestamp();

        $updateData = [
            Admin\Entity::LAST_LOGIN_AT => $currentTimestamp,
            Admin\Entity::CREATED_AT    => $currentTimestamp - 30,
            Admin\Entity::UPDATED_AT    => $currentTimestamp - 20,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 0);

        $this->assertEquals($response['failure'] , 0);

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $disabledReason = $adminsMeta->getDisabledReason();

        $this->assertFalse($admin[Admin\Entity::DISABLED]);

        $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNull($disabledReason);

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();
    }

    public function testActiveUserWithoutAdminMeta()
    {
        $dormancy = 5;

        $this->mockDcsFetchConfiguration($dormancy);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $currentTimestamp = Carbon::now()->getTimestamp();

        $updateData = [
            Admin\Entity::LAST_LOGIN_AT => $currentTimestamp,
            Admin\Entity::CREATED_AT    => $currentTimestamp - 30,
            Admin\Entity::UPDATED_AT    => $currentTimestamp - 20,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 0);

        $this->assertEquals($response['failure'] , 0);

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->assertFalse($admin[Admin\Entity::DISABLED]);

        $this->startTest();
    }

    public function testOrgAdminsDisableForInactiveUsers()
    {
        $dormancy = 25;

        $adminMetaIds = [];

        $this->mockDcsFetchConfiguration($dormancy);

        $admins = $this->createIdamAdmins($this->idamOrg->getId(), 4);

        $cnt = 0;

        $timestamp = $this->timestampWithOffset(-$dormancy) - 100;

        foreach ($admins as $admin)
        {
            if ($cnt % 2 === 0)
            {
                $this->fixtures->edit(Entity::ADMIN, $admin->getId(),
                    [
                        Admin\Entity::CREATED_AT => $timestamp - 30,
                        Admin\Entity::UPDATED_AT => $timestamp - 20,
                    ]);
            }

            $adminsMeta =  $this->createAdminsMeta($admin->getId());

            $adminMetaIds[] = $adminsMeta[AdminsMeta\Entity::ID];

            $cnt++;
        }

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/org/admins/disable/admin_' . $admins[0][Admin\Entity::ID];

        $this->startTest();

        $cnt = 0;

        foreach ($adminMetaIds as $adminMetaId)
        {
            $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminMetaId);
            $admin = $this->getDbEntityById(Entity::ADMIN, $adminsMeta[AdminsMeta\Entity::ADMIN_ID]);
            $disabledReason = $adminsMeta->getDisabledReason();

            if($cnt % 2 === 0)
            {
                $this->assertTrue($admin[Admin\Entity::DISABLED]);
                $this->assertNotNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);
                $this->assertNotNull($disabledReason);
                $this->assertEquals(AdminsMeta\Constant::IDAM_DORMANCY,$disabledReason);
            }
            else
            {
                $this->assertFalse($admin[Admin\Entity::DISABLED]);
                $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);
                $this->assertNull($adminsMeta[AdminsMeta\Entity::DISABLED_REASON]);
            }

            $cnt++;
        }
    }

    public function testOrgAdminsDisableForActiveUsers()
    {
        $dormancy = 15;

        $this->mockDcsFetchConfiguration($dormancy);

        $admins = $this->createIdamAdmins($this->idamOrg->getId(), 6);

        $adminsMetaIds = [];

        $cnt = 0;

        $timestamp = $this->timestampWithOffset(-$dormancy) - 100;

        $currentTimestamp = Carbon::now()->getTimestamp();

        foreach ($admins as $admin)
        {
            $updateData = ($cnt % 2 === 0) ?
                [
                    Admin\Entity::LAST_LOGIN_AT => $timestamp,
                    Admin\Entity::CREATED_AT    => $timestamp - 30,
                    Admin\Entity::UPDATED_AT    => $timestamp - 20,
                ]
                : [Admin\Entity::LAST_LOGIN_AT => $currentTimestamp];

            $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

            $adminsMetaIds[] = $this->createAdminsMeta($admin->getId())[AdminsMeta\Entity::ID];

            $cnt++;
        }

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/org/admins/disable/admin_' . $admins[0][Admin\Entity::ID];

        $this->startTest();

        $cnt = 0;

        foreach ($adminsMetaIds as $adminsMetaId)
        {
            $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMetaId);
            $admin = $this->getDbEntityById(Entity::ADMIN, $adminsMeta[AdminsMeta\Entity::ADMIN_ID]);
            $disabledReason = $adminsMeta->getDisabledReason();

            if($cnt % 2 === 0)
            {
                $this->assertTrue($admin[Admin\Entity::DISABLED]);
                $this->assertNotNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);
                $this->assertNotNull($disabledReason);
                $this->assertEquals(AdminsMeta\Constant::IDAM_DORMANCY,$disabledReason);
            }
            else
            {
                $this->assertFalse($admin[Admin\Entity::DISABLED]);
                $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);
                $this->assertNull($adminsMeta[AdminsMeta\Entity::DISABLED_REASON]);
            }

            $cnt++;
        }
    }

    public function testOrgAdminsDisableWhenAlreadyDisabled()
    {
        $dormancy = 15;

        $this->mockDcsFetchConfiguration($dormancy);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(),
            [
                Admin\Entity::DISABLED => true,
            ]
        );

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $this->ba->cronAuth();

        $this->testData[__FUNCTION__]['request']['url'] = '/org/admins/disable/admin_' . $admin[Admin\Entity::ID];

        $this->startTest();

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin->getId());
        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta->getId());

        $this->assertTrue($admin[Admin\Entity::DISABLED]);
        $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);
        $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);
    }

    public function testInactiveIdamSamlLogin()
    {
        $dormancy = 15;

        $this->mockDcsFetchConfiguration($dormancy);

        $expireAt = $this->timestampWithOffset(1);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1, $expireAt);

        $updateData = [
            Admin\Entity::DISABLED      => true,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $admin->roles()->attach($role);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 0);

        $this->assertEquals($response['failure'] , 0);

        $testData = $this->testData[__FUNCTION__];

        $admin = $admin->toArray();
        $adminsMeta = $adminsMeta->toArray();

        $testData['request']['content']['email'] = $admin['email'];
        $testData['request']['content']['ad_id'] = $adminsMeta['unique_identifier'];
        $testData['request']['content']['username'] = $admin['username'];
        $testData['request']['content']['userrole'] = array($role->id);
        $testData['request']['content']['expiry_date'] = $admin['expired_at'];
        $testData['request']['headers']['x-org-id'] = "org_" . $this->idamOrg->getId();

        $this->ba->dashboardGuestAppAuth();

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $this->assertTrue($admin[Admin\Entity::DISABLED]);

        $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNull($adminsMeta[AdminsMeta\Entity::DISABLED_REASON]);

        $this->startTest($testData);
    }

    public function testInactiveIdamSamlLoginDueToDormancy()
    {
        $dormancy = 5;

        $this->mockDcsFetchConfiguration($dormancy);

        $expireAt = $this->timestampWithOffset(1);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1, $expireAt);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $timestamp = $this->timestampWithOffset(-$dormancy) - 100;

        $updateData = [
            Admin\Entity::LAST_LOGIN_AT => $timestamp,
            Admin\Entity::CREATED_AT    => $timestamp - 30,
            Admin\Entity::UPDATED_AT    => $timestamp - 20,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $admin->roles()->attach($role);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 1);

        $this->assertEquals($response['failure'] , 0);

        $testData = $this->testData[__FUNCTION__];

        $admin = $admin->toArray();
        $adminsMeta = $adminsMeta->toArray();

        $testData['request']['content']['email'] = $admin['email'];
        $testData['request']['content']['ad_id'] = $adminsMeta['unique_identifier'];
        $testData['request']['content']['username'] = $admin['username'];
        $testData['request']['content']['userrole'] = array($role->id);
        $testData['request']['content']['expiry_date'] = $admin['expired_at'];
        $testData['request']['headers']['x-org-id'] = "org_" . $this->idamOrg->getId();

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $disabledReason = $adminsMeta->getDisabledReason();

        $this->assertTrue($admin[Admin\Entity::DISABLED]);

        $this->assertNotNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNotNull($adminsMeta[AdminsMeta\Entity::DISABLED_REASON]);

        $this->assertEquals(AdminsMeta\Constant::IDAM_DORMANCY, $disabledReason);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);
    }

    public function testDisablesDormantAdminsWithNoLoginSinceCreation()
    {
        $dormancy = 15;

        $this->mockDcsFetchConfiguration($dormancy);

        $expireAt = $this->timestampWithOffset(1);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1, $expireAt);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $timestamp = $this->timestampWithOffset(-$dormancy) - 100;

        $updateData = [
            Admin\Entity::CREATED_AT    => $timestamp - 30,
            Admin\Entity::UPDATED_AT    => $timestamp - 20,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $admin->roles()->attach($role);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 1);

        $this->assertEquals($response['failure'] , 0);

        $testData = $this->testData[__FUNCTION__];

        $admin = $admin->toArray();
        $adminsMeta = $adminsMeta->toArray();

        $testData['request']['content']['email'] = $admin['email'];
        $testData['request']['content']['ad_id'] = $adminsMeta['unique_identifier'];
        $testData['request']['content']['username'] = $admin['username'];
        $testData['request']['content']['userrole'] = array($role->id);
        $testData['request']['content']['expiry_date'] = $admin['expired_at'];
        $testData['request']['headers']['x-org-id'] = "org_" . $this->idamOrg->getId();

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $disabledReason = $adminsMeta->getDisabledReason();

        $this->assertTrue($admin[Admin\Entity::DISABLED]);

        $this->assertNotNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNotNull($adminsMeta[AdminsMeta\Entity::DISABLED_REASON]);

        $this->assertEquals(AdminsMeta\Constant::IDAM_DORMANCY, $disabledReason);

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);
    }

    public function testActiveAdminsWithNoLoginSinceCreation()
    {
        $dormancy = 15;

        $this->mockDcsFetchConfiguration($dormancy);

        $expireAt = $this->timestampWithOffset(1);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1, $expireAt);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $currentTimestamp = Carbon::now()->getTimestamp();

        $updateData = [
            Admin\Entity::CREATED_AT    => $currentTimestamp - 30,
            Admin\Entity::UPDATED_AT    => $currentTimestamp - 20,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $admin->roles()->attach($role);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 0);

        $this->assertEquals($response['failure'] , 0);

        $testData = $this->testData[__FUNCTION__];

        $admin = $admin->toArray();
        $adminsMeta = $adminsMeta->toArray();

        $testData['request']['content']['email'] = $admin['email'];
        $testData['request']['content']['ad_id'] = $adminsMeta['unique_identifier'];
        $testData['request']['content']['username'] = $admin['username'];
        $testData['request']['content']['userrole'] = array($role->id);
        $testData['request']['content']['expiry_date'] = $admin['expired_at'];
        $testData['request']['headers']['x-org-id'] = "org_" . $this->idamOrg->getId();

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $disabledReason = $adminsMeta->getDisabledReason();

        $this->assertFalse($admin[Admin\Entity::DISABLED]);

        $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNull($disabledReason);
    }

    public function testActiveIdamSamlLoginDueToDormancy()
    {
        $dormancy = 15;

        $this->mockDcsFetchConfiguration($dormancy);

        $expireAt = $this->timestampWithOffset(1);

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1, $expireAt);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $currentTimestamp = Carbon::now()->getTimestamp();

        $updateData = [
            Admin\Entity::LAST_LOGIN_AT => $currentTimestamp,
            Admin\Entity::CREATED_AT    => $currentTimestamp - 30,
            Admin\Entity::UPDATED_AT    => $currentTimestamp - 20,
        ];

        $this->fixtures->edit(Entity::ADMIN, $admin->getId(), $updateData);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $admin->roles()->attach($role);

        $response = $this->disableOrgAdminByDormancy($admin[Admin\Entity::ID]);

        $this->assertEquals($response['success'] , 0);

        $this->assertEquals($response['failure'] , 0);

        $testData = $this->testData[__FUNCTION__];

        $admin = $admin->toArray();
        $adminsMeta = $adminsMeta->toArray();

        $testData['request']['content']['email'] = $admin['email'];
        $testData['request']['content']['ad_id'] = $adminsMeta['unique_identifier'];
        $testData['request']['content']['username'] = $admin['username'];
        $testData['request']['content']['userrole'] = array($role->id);
        $testData['request']['content']['expiry_date'] = $admin['expired_at'];
        $testData['request']['headers']['x-org-id'] = "org_" . $this->idamOrg->getId();

        $this->ba->dashboardGuestAppAuth();

        $this->startTest($testData);

        $admin = $this->getDbEntityById(Entity::ADMIN, $admin[Admin\Entity::ID]);

        $adminsMeta = $this->getDbEntityById(Entity::ADMINS_META, $adminsMeta[Admin\Entity::ID]);

        $disabledReason = $adminsMeta->getDisabledReason();

        $this->assertFalse($admin[Admin\Entity::DISABLED]);

        $this->assertNull($adminsMeta[AdminsMeta\Entity::USER_DISABLED_AT]);

        $this->assertNull($disabledReason);

    }

    public function mockDcsFetchConfiguration($dormancy): void
    {
        $dcsConfigService = $this->getMockBuilder( DcsConfigService::class)
            ->setConstructorArgs([$this->app])
            ->getMock();

        $this->app->instance('dcs_config_service', $dcsConfigService);

        $this->app['dcs_config_service']
            ->method('fetchConfiguration')
            ->willReturn(["dormancy_period" => $dormancy]);
    }

    public function testCreateOrgAdminWithoutDomainInUniqueIdentifier()
    {
        $adminField = [
            'name', 'username', 'email', 'allow_all_merchants',
            'oauth_provider_id', 'oauth_access_token', 'disabled',
            'roles', 'groups', 'locked', 'password', 'password_confirmation', 'expired_at'
        ];
        $this->createFieldMapsForOrg($this->idamOrg->getId(), 'admin', $adminField);

        $adminsMeta = [
            'auth_mode', 'unique_identifier', 'expired_at'
        ];
        $this->createFieldMapsForOrg($this->idamOrg->getId(), 'admins_meta', $adminsMeta);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['headers']['X-Org-Id']      = "org_" . $this->idamOrg->getId();
        $testData['request']['headers']['X-Admin-Token'] = $this->adfsAuthToken;
        $testData['request']['content']['user_roles']    = array($role['id']);
        $expireAt                                        = $this->timestampWithOffset(5);
        $testData['request']['content']['expire_at']     = $expireAt;

        $testData['response']['content']['user_roles']   = array("role_" .$role['id']);
        $testData['response']['content']['expire_at']    = $expireAt;

        $this->startTest($testData);
    }

    public function testCreateOrgAdminWithDomainInUniqueIdentifier()
    {
        $adminField = [
            'name', 'username', 'email', 'allow_all_merchants',
            'oauth_provider_id', 'oauth_access_token', 'disabled',
            'roles', 'groups', 'locked', 'password', 'password_confirmation', 'expired_at'
        ];
        $this->createFieldMapsForOrg($this->idamOrg->getId(), 'admin', $adminField);

        $adminsMeta = [
            'auth_mode', 'unique_identifier', 'expired_at'
        ];
        $this->createFieldMapsForOrg($this->idamOrg->getId(), 'admins_meta', $adminsMeta);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['headers']['X-Org-Id']      = "org_" . $this->idamOrg->getId();
        $testData['request']['headers']['X-Admin-Token'] = $this->adfsAuthToken;
        $testData['request']['content']['user_roles']    = array($role['id']);
        $expireAt                                        = $this->timestampWithOffset(5);
        $testData['request']['content']['expire_at']     = $expireAt;


        $testData['response']['content']['user_roles']   = array("role_" .$role['id']);
        $testData['response']['content']['expire_at']    = $expireAt;

        $result = $this->startTest($testData);
    }

    public function testUpdateOrgAdminWithoutDomainInUniqueIdentifier()
    {
        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $role1 = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $role2 = $this->fixtures->create('role',
            [
                'org_id' => $this->idamOrg->getId(),
                'name' => 'Super Admin',
                'description' => 'Super Admins of Roles'
            ]);

        $admin->roles()->attach($role1);
        $admin->roles()->attach($role2);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $updatedExpireAt = $this->timestampWithOffset(2);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $testData['request']['url'] . '/' . $adminsMeta['unique_identifier'];
        $testData['request']['headers']['X-Org-Id'] = "org_" . $this->idamOrg->getId();
        $testData['request']['headers']['X-Admin-Token'] = $this->adfsAuthToken;
        $testData['request']['content']['user_roles'] = array_merge(array("role_" . $role1['id']), array("role_" . $role2['id']));
        $testData['request']['content']['expire_at'] = $updatedExpireAt;

        $testData['response']['content']['user_roles'] = array_merge(array("role_" . $role1['id']), array("role_" . $role2['id']));
        $testData['response']['content']['expire_at'] = $updatedExpireAt;

        $this->startTest($testData);
    }

    public function testUpdateOrgAdminWithDomainInUniqueIdentifier()
    {
        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $role1 = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $role2 = $this->fixtures->create('role',
            [
                'org_id' => $this->idamOrg->getId(),
                'name' => 'Super Admin',
                'description' => 'Super Admins of Roles'
            ]);

        $admin->roles()->attach($role1);
        $admin->roles()->attach($role2);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $updatedExpireAt = $this->timestampWithOffset(2);

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = $testData['request']['url'] . '/' . $adminsMeta['unique_identifier'];
        $testData['request']['headers']['X-Org-Id'] = "org_" . $this->idamOrg->getId();
        $testData['request']['headers']['X-Admin-Token'] = $this->adfsAuthToken;
        $testData['request']['content']['user_roles'] = array_merge(array("role_" . $role1['id']), array("role_" . $role2['id']));
        $testData['request']['content']['expire_at'] = $updatedExpireAt;

        $testData['response']['content']['user_roles'] = array_merge(array("role_" . $role1['id']), array("role_" . $role2['id']));
        $testData['response']['content']['expire_at'] = $updatedExpireAt;

        $this->startTest($testData);
    }

    public function testGetOrgAdminWithoutDomainInUniqueIdentifier()
    {
        // create IDAM admin who can only access this routes
        $this->adfsAuthToken = $this->createIDAMAdminAndGetAdminToken($this->idamOrg->getId());

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $admin->roles()->attach($role);

        $adminsMeta = $this->createAdminsMeta($admin->getId());

        $this->ba->expressAuth('test', 'rzp_test_10000000000000');

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $testData['request']['url'] . '/' . $adminsMeta['unique_identifier'];
        $testData['request']['headers']['X-Org-Id']      = "org_" . $this->idamOrg->getId();
        $testData['request']['headers']['X-Admin-Token'] = $this->adfsAuthToken;

        $testData['response']['content']['user_roles'] = array("role_" .$role['id']);

        $this->startTest($testData);

    }

    public function testGetOrgAdminWithDomainInUniqueIdentifier()
    {
        // create IDAM admin who can only access this routes
        $this->adfsAuthToken = $this->createIDAMAdminAndGetAdminToken($this->idamOrg->getId());

        $admin = $this->createIdamAdmins($this->idamOrg->getId(), 1);

        $role = $this->fixtures->create('role', ['org_id' => $this->idamOrg->getId()]);

        $admin->roles()->attach($role);

        $adminsMeta = $this->createAdminsMeta($admin->getId());
        $this->ba->expressAuth('test', 'rzp_test_10000000000000');

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['url'] = $testData['request']['url'] . '/' . $adminsMeta['unique_identifier'];
        $testData['request']['headers']['X-Org-Id']      = "org_" . $this->idamOrg->getId();
        $testData['request']['headers']['X-Admin-Token'] = $this->adfsAuthToken;

        $testData['response']['content']['user_roles'] = array("role_" .$role['id']);

        $this->startTest($testData);
    }

    private function createFieldMapsForOrg(string $orgId, string $entityName, array $fields)
    {
        return $this->fixtures->create(
            'org_field_map',
            [
                'org_id' => $orgId,
                'entity_name' => $entityName,
                'fields' => $fields,
            ]);
    }

    private function createIDAMAdminAndGetAdminToken(string $orgId): string
    {
        $expireAt = $this->timestampWithOffset(5);

        $admin = $this->createAdmin(
            $orgId,
            $expireAt,
            'admin@axis.com',
            'IDAM Admin',
            'admin',
            true
        );

        $role = $this->fixtures->create('role', [
            'org_id' => $orgId,
            'name'   => 'IDAM Admin Role',
        ]);

        $permission = $this->fixtures->create('permission', [
            'name' => 'banking_idam_admin'
        ]);

        $role->permissions()->attach($permission->getId());

        $admin->roles()->attach($role);

        $token = 'ThisIsATokenForTest';

        $adminToken = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'created_at' => $this->timestampWithOffset(),
            'token'      => Hash::make($token),
            'expires_at' => $expireAt,
        ]);

        return $token . $adminToken->getId();
    }

    private function createAdmin(
        string $orgId,
        int    $expireAt,
        string $email = 'testadmin@axis.com',
        string $name = 'Test User',
        string $username = 'testadmin',
        bool   $allowAllMerchants = false
    )
    {
        return $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID                => $orgId,
            Admin\Entity::EMAIL                 => $email,
            Admin\Entity::NAME                  => $name,
            Admin\Entity::EXPIRED_AT            => $expireAt,
            Admin\Entity::USERNAME              => $username,
            Admin\Entity::ALLOW_ALL_MERCHANTS   => $allowAllMerchants,
        ]);
    }
}
