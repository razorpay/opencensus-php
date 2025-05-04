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

    private function createIdamAdmins(string $orgId, int $noOfAdmins)
    {
        return $this->fixtures->times($noOfAdmins)->create('admin', [
            Admin\Entity::ORG_ID                => $orgId,
            Admin\Entity::EMAIL                 => 'randomadmin123@rzp.com',
            Admin\Entity::NAME                  => 'Test User',
            Admin\Entity::USERNAME              => 'testadmin',
            Admin\Entity::LAST_LOGIN_AT         => null,
            Admin\Entity::DISABLED              => false,
            Admin\Entity::ALLOW_ALL_MERCHANTS   => false,
        ]);

    }
    private function createAdminsMeta(string $adminId)
    {
        return $this->fixtures->create(Entity::ADMINS_META,
            [
                AdminsMeta\Entity::ADMIN_ID          => $adminId,
                AdminsMeta\Entity::UNIQUE_IDENTIFIER => 'xv6vxwe7',
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
}
