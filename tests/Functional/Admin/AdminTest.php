<?php

namespace RZP\Tests\Functional\Admin;

use Cache;
use Carbon\Carbon;
use Hash;
use Mail;
use Mockery;

use RZP\Mail\Admin\Account as AdminMail;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Models\Admin\Role;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class AdminTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/AdminData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->orgId = $this->org->getId();

        $this->hostName = 'testing.testing.com';

        $this->orgHostName = $this->fixtures->create('org_hostname', [
            'org_id'        => $this->orgId,
            'hostname'      => $this->hostName,
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->repo = (new Admin\Repository);
    }

    public function testCreateAdmin()
    {
        Mail::fake();

        $superAdminRole = Role\Entity::getSignedId(Org::ADMIN_ROLE);

        $this->testData[__FUNCTION__]['request']['content']['roles'] = (array) $superAdminRole;

        $group = Group\Entity::getSignedId(Org::DEFAULT_GRP);

        $this->testData[__FUNCTION__]['request']['content']['groups'] = (array) $group;

        $result = $this->startTest();

        $this->assertEquals($result['roles'][0]['id'], $superAdminRole);

        $this->assertEquals($result['groups'][0]['id'], $group);

        Mail::assertSent(AdminMail\Create::class, function ($mail)
        {
            $testData = [
                'user' => [
                    'email' => 'xyz@rzp.com',
                    'password' => 'random!12#'
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            return $mail->hasTo('xyz@rzp.com');
        });
    }

    public function testCreateAdminWithWrongEmailDomain()
    {
        $this->startTest();
    }

    public function testCreateAdminWithExistingEmail()
    {
        $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID  => $this->orgId,
            Admin\Entity::EMAIL   => 'xyz@rzp.com',
        ]);

        $this->startTest();
    }

    public function testCreateAdminWithExistingEmailOfDeletedAdmin()
    {
        $admin = $this->testDeleteAdmin();

        $this->testData[__FUNCTION__]['request']['content']['email'] = $admin->getEmail();

        $this->startTest();
    }

    public function testGetAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID  => $this->orgId,
            Admin\Entity::EMAIL   => 'testadmin@rzp.com',
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('groups', $result);
    }

    public function testEditAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $dummyGrp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $admin->groups()->sync([$dummyGrp->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $managerRole = Role\Entity::getSignedId(Org::MANAGER_ROLE);

        $this->testData[__FUNCTION__]['request']['content']['roles'] = (array) $managerRole;

        $group = Group\Entity::getSignedId(Org::DEFAULT_GRP);

        $this->testData[__FUNCTION__]['request']['content']['groups'] = (array) $group;

        $result = $this->startTest();

        $this->assertEquals($result['roles'][0]['id'], $managerRole);

        $this->assertEquals($result['groups'][0]['id'], $group);
    }

    public function testDeleteAllRolesAdmin()
    {
        $this->markTestSkipped();

        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $dummyGrp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);

        $admin->groups()->sync([$dummyGrp->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $admin = $this->getAdmin(
            $this->org->getPublicId(),
            $admin->getPublicId(),
            $this->authToken);

        $this->assertEquals(0, count($admin['roles']));
    }

    public function testDeleteAllGroupsAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $admin = $this->getAdmin(
            $this->org->getPublicId(),
            $admin->getPublicId(),
            $this->authToken);

        $this->assertEquals(0, count($admin['groups']));
    }

    public function testDeleteAdmin()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
            Admin\Entity::EMAIL  => 'xyz@rzp.com'
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        return $admin;
    }

    public function testDeleteAdminFailed()
    {
        $admin = $this->fixtures->create('admin', ['org_id' => $this->orgId]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetMultipleAdmin()
    {
        $admin = $this->fixtures->times(3)->create(
            'admin', ['org_id' => $this->orgId]);

        $result = $this->startTest();
    }

    public function testGetCurrentAdmin()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId]);

        $admin->groups()->sync([Org::DEFAULT_GRP]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $adminToken = $this->fixtures->create('admin_token', [
            'id'        => 'AdminToken1234',
            'token'     => Hash::make('secondToken'),
            'admin_id'  => $admin->getId(),
        ]);

        $this->ba->appAuth();

        $result = $this->startTest();

        $this->assertEquals($admin->getPublicId(), $result['id']);
    }

    public function testLockUnusedAccounts()
    {
        $now = Carbon::now();
        $now_minus_120 = $now->subDays(120);
        $now_minus_40 = $now->subDays(40);

        $admins = $this->fixtures->times(2)->create('admin', [
            'org_id' => $this->orgId,
            'last_login_at' => $now_minus_120->timestamp,
            'created_at' => $now_minus_120->timestamp,
            'updated_at' => $now_minus_120->timestamp,
        ]);

        // Unactivated Accounts
        $this->fixtures->times(2)->create('admin', [
            'org_id' => $this->orgId,
            'last_login_at' => null,
            'created_at' => $now_minus_40->timestamp,
            'updated_at' => $now_minus_40->timestamp,
        ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testDisabledAdminAccess()
    {
        $admin = $this->fixtures->create('admin', [
            'disabled' => true,
            'org_id' => $this->orgId
        ]);

        $now = Carbon::now();

        $token = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'created_at' => $now->timestamp,
            'token'      => Hash::make('ThisIsATokenForTest'),
            'expires_at' => $now->addYear(1)->timestamp,
        ]);

        // Token Generation is bearer + principal (Id).
        $token = 'ThisIsATokenForTest' . $token->getId();

        // Replace auth with this route
        $this->ba->adminAuth('test', $token);

        $this->testData[__FUNCTION__]['request']['url'] = '/admin/' . $admin->getPublicId();

        $this->startTest();
    }

    public function testLockedAdminAccess()
    {
        $admin = $this->fixtures->create('admin', [
            'locked' => true,
            'org_id' => $this->orgId
        ]);

        $now = Carbon::now();

        $token = $this->fixtures->create('admin_token', [
            'admin_id'   => $admin->getId(),
            'created_at' => $now->timestamp,
            'token'      => Hash::make('ThisIsATokenForTest'),
            'expires_at' => $now->addYear(1)->timestamp,
        ]);

        // Token Generation is bearer + principal (Id).
        $token = 'ThisIsATokenForTest' . $token->getId();

        // Replace auth with this route
        $this->ba->adminAuth('test', $token);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetMerchantIds()
    {
        $grp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);
        $subGrp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);

        $subGrp->parents()->attach($grp);

        $merchantsGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);
        $merchantsSubGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);

        $grp->merchants()->attach($merchantsGrp);
        $subGrp->merchants()->attach($merchantsSubGrp);

        $adminGrp = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId]);
        $adminSubGrp = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId]);

        $adminGrp->merchants()->attach($merchantsGrp);
        $adminSubGrp->merchants()->attach($merchantsSubGrp);

        $grp->admins()->attach($adminGrp);
        $subGrp->admins()->attach($adminSubGrp);

        $merchantsAdminGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);
        $merchantsAdminSubGrp = $this->fixtures->create(
            'merchant', ['org_id' => $this->orgId]);

        $adminGrp->merchants()->attach($merchantsAdminGrp);
        $adminSubGrp->merchants()->attach($merchantsAdminSubGrp);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId(), $adminGrp->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertEquals(count($result), 4);
    }

    public function testLoginUserDoesNotExist()
    {
        $this->ba->appAuth();

        $this->startTest();
    }

    public function testLoginOauth()
    {
        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'test@email.com',
            'oauth_access_token' => 'test oauth token',
            'oauth_provider_id'  => 'test oauth provider id',
        ]);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testFailedLoginOauth()
    {
        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'test@email.com',
            'oauth_access_token' => 'test oauth token 2',
            'oauth_provider_id'  => 'test oauth provider id',
        ]);

        $this->ba->appAuth();

        $this->startTest();

        $admin = $this->getEntityById('admin', $admin->getId(), true);

        $this->assertEquals($admin['failed_attempts'], 1);
    }

    public function testSelfEditAdminFailed()
    {
        $admin = $this->ba->getAdmin();

        $org = $admin->org;

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testSelfDeleteAdminFailed()
    {
        $admin = $this->ba->getAdmin();

        $data = $this->testData['testSelfEditAdminFailed'];

        $this->runRequestResponseFlow($data, function() use ($admin)
        {
            $this->deleteAdmin(
                $admin->getPublicOrgId(),
                $admin->getPublicId(),
                $this->authToken);
        });
    }

    public function testSuperAdmin()
    {
        $admin = $this->ba->getAdmin();

        $this->assertEquals($admin->isSuperAdmin(), true);
    }

    public function testForgotPasswordSuccess()
    {
        Mail::fake();

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        Mail::assertSent(AdminMail\ForgotPassword::class, function ($mail)
        {
            $this->assertArrayHasKey('firstName', $mail->viewData);

            $this->assertArrayHasKey('resetUrl', $mail->viewData);

            $this->assertArrayHasKey('orgName', $mail->viewData);

            return $mail->hasTo('abc@razorpay.com');
        });
    }

    public function testForgotPasswordInvalidUser()
    {
        $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testForgotPasswordResetUrlBlank()
    {
        $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->ba->appAuth();

        $this->startTest();
    }

    public function testPasswordResetSuccess()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->adminForgotPassword($admin->getEmail());

        $key = sprintf(Admin\Service::ADMIN_PASSWORD_RESET_TOKEN_KEY, $this->orgId, $admin->getId());

        $token = Cache::get($key);

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token;

        $newPassword = $this->testData[__FUNCTION__]['request']['content']['password'];

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check($newPassword, $admin['password']));

        // Check that token has then been expired
        $token = Cache::get($key);

        $this->assertNull($token);
    }

    public function testAdminUnlockOnResetPasswordSuccess()
    {
        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'abc@razorpay.com',
            'failed_attempts' => 10,
            'locked' => true
        ]);

        $this->adminForgotPassword($admin->getEmail());

        $key = sprintf(Admin\Service::ADMIN_PASSWORD_RESET_TOKEN_KEY, $this->orgId, $admin->getId());

        $token = Cache::get($key);

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token;

        $newPassword = $this->testData[__FUNCTION__]['request']['content']['password'];

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check($newPassword, $admin['password']));

        $this->assertEquals(0, $admin['failed_attempts']);

        $this->assertEquals(0, $admin['locked']);

        // Check that token has then been expired
        $token = Cache::get($key);

        $this->assertNull($token);
    }

    public function testAdminUnlockFailOnPasswordResetFail()
    {
        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'abc@razorpay.com',
            'failed_attempts' => 10,
            'locked' => 1
        ]);

        $this->adminForgotPassword($admin->getEmail());

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertEquals(10, $admin['failed_attempts']);

        $this->assertEquals(1, $admin['locked']);
    }

    public function testPasswordResetTokenMismatch()
    {
        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'abc@razorpay.com',
        ]);

        $this->adminForgotPassword($admin->getEmail());

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('test123456', $admin['password']));
    }

    public function testPasswordResetPasswordMismatch()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->adminForgotPassword($admin->getEmail());

        $key = sprintf(
            Admin\Service::ADMIN_PASSWORD_RESET_TOKEN_KEY, $this->orgId,
            $admin->getId());

        $token = Cache::get($key);

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token;

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('test123456', $admin['password']));
    }

    public function testPasswordResetInvalidPassword()
    {
        // This test checks if auth policy rules apply when new password is
        // given for resetting the old password

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->adminForgotPassword($admin->getEmail());

        $key = sprintf(
            Admin\Service::ADMIN_PASSWORD_RESET_TOKEN_KEY,
            $this->orgId, $admin->getId());

        $token = Cache::get($key);

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token;

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('test123456', $admin['password']));
    }

    public function testPasswordResetMaxRetain()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->adminForgotPassword($admin->getEmail());

        $key = sprintf(
            Admin\Service::ADMIN_PASSWORD_RESET_TOKEN_KEY,
            $this->orgId, $admin->getId());

        $token = Cache::get($key);

        $oldPwd = 'M!2#uWdx';

        $admin->setPassword($oldPwd);

        $this->repo->saveOrFail($admin);

        $this->testData[__FUNCTION__]['request']['content']['token'] = $token;

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check($oldPwd, $admin['password']));
    }

    public function testPasswordResetInvalidAuthType()
    {
        $org = $this->fixtures->create('org', ['auth_type' => 'google_auth']);

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $org->getId(), 'email' => 'abc@razorpay.com']);

        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('test123456', $admin['password']));
    }

    public function testAdminLogout()
    {
        $now = Carbon::now();

        $admin = $this->fixtures->create(
            'admin',
            [
                'name'     => 'test admin',
                'org_id'   => Org::RZP_ORG,
                'username' => 'ram@razorpay.com',
                'password' => 'Heimdall!432',
            ]);

        $adminPublicId = $admin->getPublicId();

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        // Create some admin tokens
        $adminTokens[] = $this->fixtures->create(
            'admin_token',
            [
                'admin_id'   => $admin->getId(),
                'created_at' => $now->timestamp,
                'token'      => Hash::make('ThisIsATokenForTest'),
                'expires_at' => $now->addYear(1)->timestamp,
            ]);

        $adminTokens[] = $this->fixtures->create(
            'admin_token',
            [
                'admin_id'   => $admin->getId(),
                'created_at' => $now->timestamp,
                'token'      => Hash::make('ThisIsASecondAdminToken'),
                'expires_at' => $now->addYear(1)->timestamp,
            ]);

        $adminToken = $adminTokens[0];

        $token = 'ThisIsATokenForTest' . $adminToken->getId();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'org_' . Org::RZP_ORG);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        // Replace auth with this route
        $this->ba->adminAuth('test', $token);

        $admin = $this->ba->getAdmin()->toArray();

        $this->assertEquals($admin['name'], 'test admin');

        $this->startTest();

        // Check if the associated token is deleted on logout
        $allTokens = $this->getEntities('admin_token', [], true);

        $remainingTokenIds = [];

        foreach ($allTokens['items'] as $t)
        {
            if ($t['admin_id'] === $adminPublicId)
            {
                $remainingTokenIds[] = $t['id'];
            }
        }

        $this->assertArrayNotHaskey($adminToken->getId(), $remainingTokenIds);
    }

    public function testGetAdminByEmailOnAppAuth()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID  => $this->orgId,
            Admin\Entity::EMAIL   => 'testadmin@rzp.com',
            Admin\Entity::NAME    => 'test admin app auth',
        ]);

        $this->ba->appAuth();

        $result = $this->startTest();
    }

    public function testEditAdminOnAppAuth()
    {
        $this->ba->appAuth('rzp_test', '', $this->hostName);

        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $dummyGrp = $this->fixtures->create(
            'group', ['org_id' => $this->orgId]);

        $admin->roles()->sync([Org::ADMIN_ROLE]);

        $admin->groups()->sync([$dummyGrp->getId()]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $managerRole = Role\Entity::getSignedId(Org::MANAGER_ROLE);

        $this->testData[__FUNCTION__]['request']['content']['roles'] = (array) $managerRole;

        $group = Group\Entity::getSignedId(Org::DEFAULT_GRP);

        $this->testData[__FUNCTION__]['request']['content']['groups'] = (array) $group;

        $result = $this->startTest();

        $this->assertEquals($result['roles'][0]['id'], $managerRole);

        $this->assertEquals($result['groups'][0]['id'], $group);
    }

    public function testCreateAdminWithoutPassword()
    {
        $this->startTest();
    }

    public function testCreateAdminWithOAuth()
    {
        $org = $this->fixtures->create('org', [
            'email'         => 'random@abc.com',
            'email_domains' => 'abc.com',
            'auth_type'     => 'google_auth',
        ]);

        $orgId = $org->getId();

        $authToken = $this->getAuthTokenForOrg($org);

        $this->ba->adminAuth('test', $authToken, $org->getPublicId());

        $this->startTest();
    }

    public function testConfigKeys()
    {
        $this->ba->appAuth();

        $request = $this->testData['testConfigKeysSet']['request'];

        $this->assertArraySelectiveEquals(
                $this->testData['testConfigKeysSet']['response'],
                $this->makeRequestAndGetContent($request)
            );

        $request = $this->testData['testConfigKeysFetch']['request'];

        $this->assertArraySelectiveEquals(
                $this->testData['testConfigKeysFetch']['response'],
                $this->makeRequestAndGetContent($request)
            );
    }

    public function testConfigKeysSetSensitive()
    {
        // Test to ensure that sensitive config keys do not get traced
        // Skipped because there are currently no sensitive config keys
        $this->markTestSkipped('No sensitive config keys at the moment');

        $this->ba->appAuth();

        $trace = Mockery::mock('RZP\Trace\Trace')->makePartial();
        $trace->shouldNotReceive('info');
        $this->app->instance('trace', $trace);

        $request = [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'sensitive_config_key' => '1',
            ],
        ];

        $this->makeRequestAndGetContent($request);
    }
}
