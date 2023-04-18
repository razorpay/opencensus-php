<?php

namespace RZP\Tests\Functional\Admin;

use DB;
use Hash;
use Mail;
use Cache;
use Mockery;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\Admin\Role;
use RZP\Models\Base\EsDao;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Group;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;
use RZP\Tests\Functional\Helpers\MocksDiagTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Base\UniqueIdEntity;
use Illuminate\Support\Facades\Crypt;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Admin\Role\TenantRoles;
use RZP\Mail\Admin\Account as AdminMail;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Admin\Org\Repository as OrgRepository;
use RZP\Tests\Functional\Helpers\Workflow\WorkflowTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\Helpers\Org\CustomBrandingTrait;

class AdminTest extends TestCase
{
    use RequestResponseFlowTrait;
    use CustomBrandingTrait;
    use WorkflowTrait;
    use HeimdallTrait;
    use DbEntityFetchTrait;
    use MocksDiagTrait;

    protected $esDao;

    protected $config;

    protected $esClient;

    protected function setUp(): void
    {
        ConfigKey::resetFetchedKeys();

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

        $this->esDao = new EsDao();

        $this->esClient =  $this->esDao->getEsClient()->getClient();
    }

    public function testCreateAdmin()
    {
        Mail::fake();

        $superAdminRole = Role\Entity::getSignedId(Org::ADMIN_ROLE);

        $group = Group\Entity::getSignedId(Org::DEFAULT_GRP);

        $email = 'xyz@razorpay.com';

        $this->setRequestResponseForCreateAdminTest(__FUNCTION__,$superAdminRole, $group, $email);

        $this->ba->adminAuth();

        $result = $this->startTest();

        $this->assertEquals($result['roles'][0]['id'], $superAdminRole);

        $this->assertEquals($result['groups'][0]['id'], $group);

        Mail::assertQueued(AdminMail\Create::class, function ($mail) use ($email)
        {
            $testData = [
                'user' => [
                    'email'    => $email,
                    'password' => 'Random!12#'
                ]
            ];

            $this->assertArraySelectiveEquals($testData, $mail->viewData);

            $this->assertRazorpayOrgMailData($mail->viewData['data']);

            return $mail->hasTo($email);
        });
    }

    public function testCreateAdminESPasswordEncrypted()
    {
        $testData = $this->testData['testCreateAdminESPasswordEncrypted'];

        $action = $this->getCreateAdminESData($testData);

        $this->assertEquals($testData['request']['content']['password'], Crypt::decrypt($action['payload']['password']));

        $this->assertEquals($testData['request']['content']['password'], Crypt::decrypt($action['payload']['password_confirmation']));

        $this->assertEquals('admin', $action['entity_name']);

        $this->assertEquals('https://api.razorpay.com/v1/admins', $action['url']);

        $this->assertArraySelectiveEquals($testData['response']['content'],$action);
    }

    public function testCreateAdminESAfterAcceptanceOfApproval()
    {
        $testData = $this->testData['testCreateAdminESAfterAcceptanceOfApproval'];

        $action = $this->getCreateAdminESData($testData);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $this->performWorkflowAction($workflowAction['id'], true);

        $adminCreated1 = $this->getLastEntity('admin', true);

        $this->assertArraySelectiveEquals([
            'name'                  => 'test admin',
            'email'                 => 'xyz@razorpay.com',
            'username'              => 'harshil',
            'employee_code'         => "rzp_1",
            'branch_code'           => "krmgla",
            'supervisor_code'       => "shk",
            'location_code'         => "560030",
            'department_code'       => "tech"
        ],$adminCreated1);

        // to check the password
        $adminCreated = $this->repo->findByOrgIdAndEmail($adminCreated1['org_id'], 'xyz@razorpay.com');

        $isSame = Hash::check("Random!12#", $adminCreated->getPassword());

        $this->assertEquals(true, $isSame);
    }

    public function testCreateAdminESAfterDenialOfApproval()
    {
        $testData = $this->testData['testCreateAdminESAfterDenialOfApproval'];

        $adminToCreateToVerify = [
            'org_id'             => Org::RZP_ORG,
            'email'              => 'test@email.com',
            'oauth_access_token' => 'test oauth token',
            'oauth_provider_id'  => 'test oauth provider id',
        ];

        $this->fixtures->create('admin', $adminToCreateToVerify);

        $action = $this->getCreateAdminESData($testData);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        $response = $this->performWorkflowAction($workflowAction['id'], false);

        $adminCreated = $this->getLastEntity('admin', true);

        // verifies that new Admin was not created
        $this->assertEquals('test@email.com',$adminCreated['email'] );

        $this->assertEquals('rejected', $response['state'] );
    }

    public function testCreateAdminMailForCustomBrandingOrg()
    {
        Mail::fake();

        $superAdminRole = Role\Entity::getSignedId(Org::ADMIN_ROLE);

        $group = Group\Entity::getSignedId(Org::DEFAULT_GRP);

        $email = 'xyz@rzp.com';

        $this->setRequestResponseForCreateAdminTest(__FUNCTION__, $superAdminRole, $group, $email);

        $org = $this->fixtures->edit('org', $this->orgId,[
            'email_logo_url'    => 'https://www.xyz.com/email_logo.png',
            'display_name'      => 'random_org_name',
            'checkout_logo_url' => 'https://www.xyz.com/checkout_logo.png'
        ]);

        $this->enableCustomBrandingForOrg($org);

        $this->startTest();

        Mail::assertQueued(AdminMail\Create::class, function ($mail) use ($org)
        {
            $viewData = $mail->viewData['data'];

            $this->assertCustomBrandingMailViewData($org, $viewData);

            return true;
        });
    }

    protected function setRequestResponseForCreateAdminTest($functionName, $superAdminRole, $group, $email)
    {
        $this->testData[$functionName] = $this->testData['testCreateAdmin'];

        $this->testData[$functionName]['request']['content']['roles'] = (array) $superAdminRole;

        $this->testData[$functionName]['request']['content']['groups'] = (array) $group;

        $this->testData[$functionName]['request']['content']['email'] = $email;

        $this->testData[$functionName]['response']['content']['email'] = $email;
    }

    public function testCreateAdminWithWrongEmailDomain()
    {
        $this->startTest();
    }

    public function testCreateAdminWithExistingEmailSameOrg()
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

    public function testCreateAdminWithExistingEmailDifferentOrg()
    {
        $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID  => $this->orgId,
            Admin\Entity::EMAIL   => 'xyz@rzp.com',
        ]);

        // create new org
        $newOrg = $this->fixtures->create('org', [
            'email'         => 'random1@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->fixtures->create('org_hostname', [
            'org_id'        => $newOrg->getId(),
            'hostname'      => 'testing2.testing.com',
        ]);

        $newAuthToken = $this->getAuthTokenForOrg($newOrg);

        $this->ba->adminAuth('test', $newAuthToken, $newOrg->getPublicId());

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

    public function testAdmin2faLogin()
    {
        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'testadmin@rzp.com',
            'org_id' => $this->org->getId(),
            'password' => 'Heimdall!234',
        ]);
        $result = $this->startTest();
        $this->assertArrayHasKey('email', $result);
    }

    public function testAdmin2faLoginFailure()
    {
        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'testadmin@rzp.com',
            'org_id' => $this->org->getId(),
            'password' => 'Heimdall!23',
        ]);
        $this->startTest();
    }

    protected function enableRazorXTreatmentForFeature($featureUnderTest, $value = 'on')
    {
        $mock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $mock->method('getTreatment')
            ->will(
                $this->returnCallback(
                    function (string $mid, string $feature, string $mode) use ($featureUnderTest, $value)
                    {
                        return $feature === $featureUnderTest ? $value : 'control';
                    }));

        $this->app->instance('razorx', $mock);

    }

    public function testAdminVerify2faFlagOtp()
    {

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'testadmin@rzp.com',
            'org_id' => $this->org->getId(),
            'password' => 'Heimdall!234',
           'wrong_2fa_attempts' => 4,
        ]);
        $result = $this->startTest();
        $this->assertArrayHasKey('email', $result);
    }

    public function testAdminVerify2faFlagOtpFailure()
    {

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'testadmin@rzp.com',
            'org_id' => $this->org->getId(),
            'password' => 'Heimdall!23',
            'wrong_2fa_attempts' => 4,
        ]);
         $this->startTest();
    }

    public function testAdminResend2faFlagOtp()
    {
        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'testadmin@rzp.com',
            'org_id' => $this->org->getId(),
            'password' => 'Heimdall!234',
        ]);
        $result = $this->startTest();
        $this->assertArrayHasKey('otp_send', $result);
    }

    public function testAdminEdit2faFlagOrg()
    {
        $result = $this->startTest();
        $this->assertArrayHasKey('admin_second_factor_auth', $result);
        $this->assertEquals(true, $result['admin_second_factor_auth']);
    }

    public function testAdminUnlockAccount()
    {
        $admin = $this->fixtures->create('admin', [
            Admin\Entity::ORG_ID => $this->orgId,
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, 'admin_'.$admin->getId()."/unlock");

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertArrayHasKey('locked', $result);
        $this->assertEquals(false, $result['locked']);

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

        $this->ba->adminAuth();

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

        $this->ba->cronAuth();

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

    public function testLoginUserDoesNotExist()
    {
        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();
    }

    public function testLoginOauth()
    {
        $admin = $this->fixtures->create('admin', [
            'org_id'             => Org::RZP_ORG,
            'email'              => 'test@email.com',
            'oauth_access_token' => 'test oauth token',
            'oauth_provider_id'  => 'test oauth provider id',
        ]);

        $this->ba->dashboardInternalAppAuth();

        $this->app['config']->set('oauth.admin_google_oauth_client_mock', true);


        $this->startTest();
    }

    public function testLoginOauthWithIncorrectAccessToken()
    {
        $admin = $this->fixtures->create('admin', [
            'org_id'             => Org::RZP_ORG,
            'email'              => 'test@email.com',
            'oauth_access_token' => 'test oauth token',
            'oauth_provider_id'  => 'test oauth provider id',
        ]);

        $this->app['config']->set('oauth.admin_google_oauth_client_mock', false);

        $this->ba->dashboardInternalAppAuth();

        $this->startTest();
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

        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $diagMock = Mockery::mock('RZP\Services\DiagClient');
        $diagMock->shouldReceive('trackOnboardingEvent')->andReturn([]);
        $this->app->instance('diag', $diagMock);

        $this->startTest();

        Mail::assertQueued(AdminMail\ForgotPassword::class, function ($mail)
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

        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();
    }

    public function testForgotPasswordResetUrlBlank()
    {
        $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();
    }

    public function testPasswordResetSuccess()
    {
        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $this->adminForgotPassword($admin->getEmail());

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->testData[__FUNCTION__]['request']['content']['token'] = $admin->getPasswordResetToken();

        $newPassword = $this->testData[__FUNCTION__]['request']['content']['password'];

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check($newPassword, $admin['password']));

        $tokenExpiry = $admin->getPasswordResetExpiry();

        $this->assertGreaterThan($tokenExpiry,Carbon::now()->addSecond()->timestamp);
    }

    public function testAdminUnlockOnResetPasswordSuccess()
    {
        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'abc@razorpay.com',
            'failed_attempts' => 10,
            'locked' => true
        ]);

        $this->adminForgotPassword($admin->getEmail());

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->testData[__FUNCTION__]['request']['content']['token'] = $admin->getPasswordResetToken();

        $newPassword = $this->testData[__FUNCTION__]['request']['content']['password'];

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check($newPassword, $admin['password']));

        $this->assertEquals(0, $admin['failed_attempts']);

        $this->assertEquals(0, $admin['locked']);

        $tokenExpiry = $admin->getPasswordResetExpiry();

        $this->assertGreaterThan($tokenExpiry,Carbon::now()->addSecond()->timestamp);
    }

    public function testAdminUnlockFailOnPasswordResetFail()
    {
        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'abc@razorpay.com',
            'failed_attempts' => 10,
            'locked' => 1
        ]);

        $this->adminForgotPassword($admin->getEmail());

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertEquals(10, $admin['failed_attempts']);

        $this->assertEquals(1, $admin['locked']);
    }

    public function testPasswordResetTokenMismatch()
    {
        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $admin = $this->fixtures->create('admin', [
            'org_id' => $this->orgId,
            'email' => 'abc@razorpay.com',
        ]);

        $this->adminForgotPassword($admin->getEmail());

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('test123456', $admin['password']));
    }

    public function testPasswordResetPasswordMismatch()
    {
        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->adminForgotPassword($admin->getEmail());

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->testData[__FUNCTION__]['request']['content']['token'] = $admin->getPasswordResetToken();

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('test123456', $admin['password']));
    }

    public function testPasswordResetInvalidPassword()
    {
        // This test checks if auth policy rules apply when new password is
        // given for resetting the old password

        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->adminForgotPassword($admin->getEmail());

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->testData[__FUNCTION__]['request']['content']['token'] = $admin->getPasswordResetToken();

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('test123456', $admin['password']));
    }

    public function testPasswordResetMaxRetain()
    {
        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $this->orgId, 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $this->orgId, 'email' => 'abc@razorpay.com']);

        $this->adminForgotPassword($admin->getEmail());

        $oldPwd = 'M!2#uWdx';

        $admin->setPassword($oldPwd);

        $this->repo->saveOrFail($admin);

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->testData[__FUNCTION__]['request']['content']['token'] = $admin->getPasswordResetToken();

        $this->ba->dashboardGuestAppAuth($this->hostName);

        $this->startTest();

        $admin = $this->repo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check($oldPwd, $admin['password']));
    }

    public function testPasswordResetInvalidAuthType()
    {
        // Password reset should not work on google auth

        $org = $this->fixtures->create('org', ['auth_type' => 'google_auth']);

        $admin = $this->fixtures->create(
            'admin', ['org_id' => $org->getId(), 'email' => 'abc@razorpay.com']);

        $feature = $this->fixtures->create(
            'feature', ['entity_id' => $org->getId(), 'entity_type' => 'org', 'name' => \RZP\Models\Feature\Constants::ORG_ADMIN_PASSWORD_RESET]);


        $hostName = 'newtesting.newtesting.com';

        $this->orgHostName = $this->fixtures->create('org_hostname', [
            'org_id'        => $org->getId(),
            'hostname'      => $hostName,
        ]);


        $this->ba->dashboardGuestAppAuth($hostName);

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

    // This test is for SuperAdmin to set config keys.
    // A SuperAdmin should be able to set
    // any key since he/she has 'update_config_key' permission.
    // (Although they have all permissions anyway so it doesn't matter)
    public function testConfigKeys()
    {
        $this->ba->adminAuth();

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

    /**
     * @param $permissions
     * @return string
     *
     * Create an new admin with only the permissions specified in arguments
     */
    public function createAdminWithRedisConfigPermissions($permissions) : string
    {
        $admin = $this->fixtures->create('admin', [
            'id' => 'RzrpyRndAdmnId',
            'org_id' => Org::RZP_ORG,
            'name' => 'test admin'
        ]);

        $this->fixtures->create('admin_token', [
            'id'        => 'AdminToken1234',
            'token'     => Hash::make('secondToken'),
            'admin_id'  => $admin->getId(),
        ]);

        $role = $this->fixtures->create('role', [
            'id'     => 'setRedisConfId',
            'org_id' => '100000razorpay',
            'name'   => 'Set Config admin',
        ]);

        foreach ($permissions as $permission)
        {
            $permissionEntity = $this->fixtures->create('permission',[
                'name'   => $permission
            ]);

            $role->permissions()->attach($permissionEntity->getId());
        }

        $admin->roles()->attach($role);

        return 'secondTokenAdminToken1234';
    }

    public function createRazorpayOrgAdminForTenantRoleChecks(array $permissions, array $roles = []) : string
    {
        $admin = $this->fixtures->create('admin', [
            'id' => 'RzrpyOrgAdmnId',
            'org_id' => Org::RZP_ORG,
            'name' => 'Test admin'
        ]);

        $this->fixtures->create('admin_token', [
            'id'        => 'AdminToken1234',
            'token'     => Hash::make('secondToken'),
            'admin_id'  => $admin->getId(),
        ]);

        $defaultRole = $this->fixtures->create('role', [
            'org_id' => Org::RZP_ORG,
            'name'   => 'Tenant admin role',
        ]);

        foreach ($permissions as $permission)
        {
            $permissionEntity = $this->fixtures->create('permission',[
                'name'   => $permission
            ]);

            $defaultRole->permissions()->attach($permissionEntity->getId());
        }

        $admin->roles()->attach($defaultRole);

        foreach ($roles as $role)
        {
            $roleEntity = $this->fixtures->create('role', [
                'org_id' => Org::RZP_ORG,
                'name'   => $role,
            ]);

            $admin->roles()->attach($roleEntity);
        }

        return 'secondTokenAdminToken1234';
    }

    public function testConfigKeysSetWithAdminWithOnlyUpdateConfigKeyPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'update_config_key',
            'set_config_keys'
        ]);

        $this->ba->adminAuth('test', $token);

        $request = $this->testData['testConfigKeysSetWithAdminWithOnlyUpdateConfigKeyPermission']['request'];

        $this->assertArraySelectiveEquals(
            $this->testData['testConfigKeysSetWithAdminWithOnlyUpdateConfigKeyPermission']['response'],
            $this->makeRequestAndGetContent($request)
        );
    }

    public function testTaxPaymentAdminRouteHitsServiceMethod()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
                                                                  'tax_payment_admin_auth_execute'
                                                              ]);

        $this->ba->adminAuth('test', $token);

        $tpMock = Mockery::mock('RZP\Services\TaxPayments\Service');

        $tpMock->shouldReceive('adminActions')->andReturn([]);

        $this->app->instance('tax-payments', $tpMock);

        $this->startTest();

        $tpMock->shouldHaveReceived('adminActions');
    }

    public function testTaxPaymentAdminRouteFailsWithoutPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
                                                                  'some_other_permission'
                                                              ]);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    public function testOAuthApplicationUpdateServiceMethod()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'manage_bulk_feature_mapping'
        ]);

        $this->ba->adminAuth('test', $token);

        $authServiceMock = Mockery::mock('RZP\Services\AuthService');

        $authServiceMock->shouldReceive('updateApplication')->andReturn([]);

        $this->app->instance('authservice', $authServiceMock);

        $this->startTest();

        $authServiceMock->shouldHaveReceived('updateApplication');
    }

    public function testConfigKeysSetWithSpecificKeyPermissions()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'set_rx_account_prefix',
            'set_shared_account_allowed_channels',
            'set_config_keys'
        ]);

        $this->ba->adminAuth('test',$token);

        $request = $this->testData['testConfigKeysSetWithSpecificKeyPermissions']['request'];

        $this->assertArraySelectiveEquals(
            $this->testData['testConfigKeysSetWithSpecificKeyPermissions']['response'],
            $this->makeRequestAndGetContent($request)
        );
    }

    // In this the admin should not have 'update_config_key'
    // permission and has a wrong permission
    public function testConfigKeysSetWithCompletelyWrongPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'wrong_permission'
        ]);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    // Permissions for certain keys is missing
    public function testConfigKeysSetWithMissingPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'set_rx_account_prefix'
        ]);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    public function testConfigKeysSetConfigWithNoPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'set_rx_account_prefix'
        ]);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
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

    public function testConfigKeysSetRupayCaptureDelay()
    {
        $this->ba->adminAuth();

        $request = [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:delay_rupay_capture' => '1',
            ],
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals('1', $content[0]['new_value']);
    }

    public function testAdminAllEntitiesApi()
    {
        $result = $this->startTest();

        $this->assertCount(11, $result['fields']);

        $this->assertGreaterThan(351, $result['entities']);
    }

    public function testAdminAllEntitiesApiNoPaymentTenantRolesNonRzpOrg()
    {
        // The admin is from a non-razorpay org, and should see the payment entity on the list.
        $store = Cache::store('redis');

        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->zeroOrMoreTimes()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([E::PAYMENT => [Role\TenantRoles::ENTITY_PAYMENTS]]);

        $result = $this->startTest();

        $this->assertCount(11, $result['fields']);

        $this->assertGreaterThan(351, $result['entities']);

        $this->assertContains('payment', array_keys($result['entities']));
    }

    public function testAdminAllEntitiesApiNoPaymentTenantRoles()
    {
        $store = Cache::store('redis');

        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([E::PAYMENT => [Role\TenantRoles::ENTITY_PAYMENTS]]);

        $token = $this->createRazorpayOrgAdminForTenantRoleChecks([Permission::VIEW_ALL_ENTITY]);
        $this->ba->adminAuth('test', $token);

        $result = $this->startTest();

        $this->assertCount(11, $result['fields']);

        $this->assertGreaterThan(351, $result['entities']);

        $this->assertNotContains('payment', array_keys($result['entities']));
    }

    public function testAdminAllEntitiesApiWithPaymentsTenantRole()
    {
        $store = Cache::store('redis');

        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS]]);

        $token = $this->createRazorpayOrgAdminForTenantRoleChecks([Permission::VIEW_ALL_ENTITY], [TenantRoles::ENTITY_PAYMENTS]);
        $this->ba->adminAuth('test', $token);

        $result = $this->startTest();

        $this->assertCount(11, $result['fields']);

        $this->assertGreaterThan(351, $result['entities']);

        $this->assertContains('payment', array_keys($result['entities']));
    }

    public function testAdminAllEntitiesApiWithPaymentsExternalTenantRole()
    {
        $store = Cache::store('redis');

        Cache::shouldReceive('store')
             ->withAnyArgs()
             ->andReturn($store);

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn([E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS, TenantRoles::ENTITY_PAYMENTS_EXTERNAL]]);

        $token = $this->createRazorpayOrgAdminForTenantRoleChecks([Permission::VIEW_ALL_ENTITY], [TenantRoles::ENTITY_PAYMENTS_EXTERNAL]);
        $this->ba->adminAuth('test', $token);

        $result = $this->startTest();

        $this->assertCount(11, $result['fields']);

        $this->assertGreaterThan(351, $result['entities']);

        $this->assertContains('payment', array_keys($result['entities']));
    }

    public function testFetchSoftDeletedEntityForAdmin()
    {
        $org = $this->fixtures
                    ->org
                    ->create(
                        [
                            'auth_type'  => 'google_auth',
                            'deleted_at' => time(),
                        ]);

        $testData = & $this->testData[__FUNCTION__];

        // Case 1: When no deleted parameter sent, should return 0 entity
        $content = $this->startTest();
        $this->assertSame(0, $content['count']);

        // Case 2: When deleted=0 is sent, should return 0 entity
        $testData['request']['content']['deleted'] = 0;
        $content = $this->startTest();
        $this->assertSame(0, $content['count']);

        // Case 3: When deleted=1 is sent, should return 1 entity
        $testData['request']['content']['deleted'] = '1';
        $content = $this->startTest();
        $this->assertSame(1, $content['count']);

        //
        // Case 4: When invalid value is sent for deleted parameter, should
        //         throw validation exception.
        //
        $testData['request']['content']['deleted'] = 11;
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'The selected deleted is invalid.');
    }

    public function testFindSoftDeletedEntityForAdmin()
    {
        $org = $this->fixtures
                    ->org
                    ->create(
                        [
                            'id'         => '10000000000001',
                            'auth_type'  => 'google_auth',
                            'deleted_at' => time(),
                        ]);

        $testData = & $this->testData[__FUNCTION__];

        // Case 1: When no deleted parameter sent, should throw exception
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_INVALID_ID);

        // Case 2: When deleted=0 is sent, should return 0 entity
        $testData['request']['content']['deleted'] = 0;
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestException::class,
            PublicErrorDescription::BAD_REQUEST_INVALID_ID);

        // Case 3: When deleted=1 is sent, should return entity
        $testData['request']['content']['deleted'] = 1;
        $content = $this->startTest();
        $this->assertSame($org['public_id'], $content['id']);

        // Case 4: When invalid deleted sent, should throw exception
        $testData['request']['content']['deleted'] = 'true';
        $this->makeRequestAndCatchException(
            function() use ($testData)
            {
                $this->runRequestResponseFlow($testData);
            },
            \RZP\Exception\BadRequestValidationFailureException::class,
            'The selected deleted is invalid.');
    }

    public function testUpdateGeoIps()
    {
        $this->app['config']->set('services.geolocation.mocked', true);

        //Local IP
        $geoIp1 = $this->fixtures->create('geo_ip', [
            'ip' => '127.0.0.1'
        ]);

        //Invalid IP
        $geoIp2 = $this->fixtures->create('geo_ip', [
            'ip' => 'INVALID_IP'
        ]);

        $geoIp3 = $this->fixtures->create('geo_ip', [
            'ip' => '106.51.22.240'
        ]);

        $this->startTest();

        $this->assertSame('NONE', $geoIp1->reload()->country);
        $this->assertSame('NONE', $geoIp2->reload()->country);
        $this->assertSame('Bangalore', $geoIp3->reload()->city);

        // Same entities should not be picked again
        $response = [
            'content' => [
                'total'     => 0,
                'success'   => 0,
            ],
        ];
        $this->testData[__FUNCTION__]['response'] = $response;

        $this->startTest();
    }

    public function testDbMetaDataQuery()
    {
        $this->startTest();
    }

    public function testDbMetaDataQueryWithInvalidQuery()
    {
        $this->startTest();
    }

    public function testPayoutLinkAdminRouteHitsServiceMethod()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            Permission::TAX_PAYMENT_ADMIN_AUTH_EXECUTE
        ]);

        $this->ba->adminAuth('test', $token);

        $plMock = Mockery::mock('RZP\Services\PayoutLinks');

        $plMock->shouldReceive('adminActions')->andReturn([]);

        $this->app->instance('payout-links', $plMock);

        $this->startTest();

        $plMock->shouldHaveReceived('adminActions');
    }

    public function testPayoutLinkPullStatusRouteReachesServiceWithPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            Permission::PAYOUT_LINK_ADMIN_AUTH_EXECUTE
        ]);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    public function testPayoutLinkPullStatusRouteFailsWithoutPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'some_other_permission'
        ]);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }


    public function testPayoutLinkAdminRouteFailsWithoutPermission()
    {
        $token = $this->createAdminWithRedisConfigPermissions([
            'some_other_permission'
        ]);

        $this->ba->adminAuth('test', $token);

        $this->startTest();
    }

    public function testAdminP2pEntitiesApi()
    {
        $result = $this->startTest();

        $this->assertArrayKeysExist($result['entities']['p2p_device'], ['contact', 'customer_id']);
        $this->assertArrayKeysExist($result['entities']['p2p_device_token'], ['device_id']);
        $this->assertArrayKeysExist($result['entities']['p2p_register_token'], ['device_id']);
        $this->assertArrayKeysExist($result['entities']['p2p_bank_account'], ['device_id', 'account_number']);
        $this->assertArrayKeysExist($result['entities']['p2p_vpa'], ['device_id', 'username', 'bank_account_id']);
        $this->assertArrayKeysExist($result['entities']['p2p_beneficiary'], ['device_id']);
        $this->assertArrayKeysExist($result['entities']['p2p_transaction'], ['device_id', 'customer_id', 'status']);
        $this->assertArrayKeysExist($result['entities']['p2p_upi_transaction'], ['device_id', 'rrn']);
        $this->assertArrayKeysExist($result['entities']['p2p_concern'], ['device_id', 'transaction_id', 'status']);
    }

    protected function setupAdminForExternalAdminEntityFetchTest()
    {
        $this->addPermissionToBaAdmin('external_admin_view_all_entity');
    }

    protected function setupAdminEntitySyncByIDTest()
    {
        $this->addPermissionToBaAdmin('sync_entity_by_id');
    }

    protected function setUpFixturesForExternalAdminEntityFetchTest()
    {
        $payment = $this->fixtures->create('payment:captured');

        $paymentId = $payment['id'];

        $upiId = $this->fixtures->create('upi', [
            'payment_id'    => $paymentId,
        ])['id'];

        $payment = $this->fixtures->create('payment:captured');

        $refundId = $this->fixtures->create('refund:from_payment', ['payment' => $payment])['id'];

        $disputeId = $this->fixtures->create('dispute')['id'];

        DB::connection('test')->table('billdesk')->insert([
            'id'            => 1,
            'payment_id'    => $paymentId,
            'action'        => 'dummy',
            'MerchantId'    => '10000000000000',
            'CustomerId'    => 'customerId',
            'TxnAmount'     => 123,
            'CurrencyType'  => 'INR',
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);

        $billdeskId = Db::connection('test')->table('billdesk')->first()->id;

        DB::connection('test')->table('netbanking')->insert([
            'payment_id'        => $paymentId,
            'action'            => 'dummy',
            'amount'            => 123,
            'bank'              => 'SBIN',
            'caps_payment_id'   => $paymentId,
            'created_at'        => time(),
            'updated_at'        => time(),
        ]);

        $netbankingId = Db::connection('test')->table('netbanking')->first()->id;

        DB::connection('test')->table('bank_transfers')->insert([
           'id'             => UniqueIdEntity::generateUniqueId(),
           'merchant_id'    => '10000000000000',
           'payee_account'  => '123',
           'payee_ifsc'     => 'SBIN00001',
           'gateway'        => 'bt_icic',
           'amount'         => 123,
           'mode'           => 0,
           'utr'            => '123',
           'time'           => time(),
           'created_at'     => time(),
           'updated_at'     => time(),
        ]);

        $bankTransferId = Db::connection('test')->table('bank_transfers')->first()->id;


        DB::connection('test')->table('atom')->insert([
            'id'            => 1,
            'payment_id'    => $paymentId,
            'amount'        => 123,
            'created_at'    => time(),
            'updated_at'    => time(),
        ]);

        $atomId = Db::connection('test')->table('atom')->first()->id;

        $balanceId = $this->getLastEntity('balance', true)['id'];

        $creditsId = $this->fixtures->create('credits')->getId();

        $this->fixtures->create('merchant_detail', ['merchant_id' => '10000000000000',]);

        return [
            'payment'           => $paymentId,
            'upi'               => $upiId,
            'refund'            => $refundId,
            'dispute'           => $disputeId,
            'merchant'          => '10000000000000',
            'billdesk'          => $billdeskId,
            'netbanking'        => $netbankingId,
            'atom'              => $atomId,
            'bank_transfer'     => $bankTransferId,
            'balance'           => $balanceId,
            'merchant_detail'   => '10000000000000',
            'credits'           => $creditsId,
        ];
    }

    public function testExternalAdminGetEntities()
    {
        $this->setupAdminForExternalAdminEntityFetchTest();

        $expectedEntities = $this->getAllowedEntityTypeForExternalAdminEntityFetch();

        $actualEntities = $this->startTest()['entities'];

        $this->assertEquals($expectedEntities, array_keys($actualEntities));
    }

    public function testExternalAdminFetchAllowedEntityById()
    {
        $entityTypeIdMap = $this->setUpFixturesForExternalAdminEntityFetchTest();

        $this->setupAdminForExternalAdminEntityFetchTest();

        foreach ($this->getAllowedEntityTypeForExternalAdminEntityFetch() as $entityType)
        {
            $this->testData[__FUNCTION__]['request']['url'] = "/external_admin/{$entityType}/{$entityTypeIdMap[$entityType]}";

            $actualAttributes = array_values(array_keys($this->startTest()));

            $this->validateAttributesForExternalAdminEntityFetch($entityType, $actualAttributes);
        }
    }

    public function testExternalAdminFetchBlockedEntityByIdShouldFail()
    {
        $terminalId = $this->fixtures->create('terminal')['id'];

        $this->testData[__FUNCTION__]['request']['url'] .= $terminalId;

        $this->setupAdminForExternalAdminEntityFetchTest();

        $this->startTest();
    }

    public function testAdminEntitySyncByIDSuccess()
    {
        $merchantLive = $this->fixtures->on(Mode::LIVE)->edit('merchant', '10000000000000', ['account_code' => '11']);

        $merchantTest = $this->fixtures->on(Mode::TEST)->edit('merchant', '10000000000000', ['account_code' => '12']);

        $this->setupAdminEntitySyncByIDTest();

        $this->startTest();

        $this->assertSame($merchantLive->reload()->account_code, $merchantTest->reload()->account_code);
    }

    public function testAdminEntitySyncByIDFailureByWrongEntity()
    {
        $this->setupAdminEntitySyncByIDTest();

        $this->startTest();
    }

    public function testAdminEntitySyncByIDFailureByWrongMode()
    {
        $this->setupAdminEntitySyncByIDTest();

        $this->startTest();
    }

    public function testAdminEntitySyncByIDFailureByNonSyncEntity()
    {
        $id = '10000000000000';

        $this->fixtures->on(Mode::LIVE)->create('card', ['id' => $id]);

        $this->fixtures->on(Mode::TEST)->create('card', ['id' => $id]);

        $this->setupAdminEntitySyncByIDTest();

        $this->startTest();
    }

    public function testExternalAdminFetchEntityMultipleDisallowedParamsShouldFail()
    {
        $this->setupAdminForExternalAdminEntityFetchTest();

        $this->startTest();
    }

    public function testExternalAdminFetchEntityMultipleBlockedEntityShouldFail()
    {
        $this->setupAdminForExternalAdminEntityFetchTest();

        $this->startTest();
    }

    public function testExternalAdminFetchEntityMultiple()
    {
        $this->setUpFixturesForExternalAdminEntityFetchTest();

        $this->setupAdminForExternalAdminEntityFetchTest();

        foreach ($this->getAllowedEntityTypeForExternalAdminEntityFetch() as $entityType)
        {
            $this->testData[__FUNCTION__]['request']['url'] = "/external_admin/{$entityType}";

            $items = $this->startTest()['items'];

            $this->assertNotEmpty($items);

            foreach ($items as $actualAttributes)
            {
                $actualAttributes = array_values(array_keys($actualAttributes));

                $this->validateAttributesForExternalAdminEntityFetch($entityType, $actualAttributes);
            }
        }
    }

    /**
     * Admin users can search for a user with contact_mobile attribute besides email
     */
    public function testAdminFetchUserByEmail()
    {
        $testData = & $this->testData[__FUNCTION__];

        $user = $this->fixtures->create('user', ['email' => 'random@random.com']);

        $testData['response']['content']['items'][0] = $user->toArrayPublic();

        $this->ba->adminAuth();

        $this->startTest($testData);

    }

    /**
     * Admin users can search for a user with contact_mobile attribute besides email
     */
    public function testAdminFetchUserByMobile()
    {
        $testData = & $this->testData[__FUNCTION__];

        $user = $this->fixtures->create('user', ['contact_mobile' => '9878909877']);

        $testData['response']['content']['items'][0] = $user->toArrayPublic();

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testAdminFetchUserByMobileMultipleMatches()
    {
        $testData = & $this->testData[__FUNCTION__];

        $user1 = $this->fixtures->create('user', ['contact_mobile' => '9878909876']);
        $user2 = $this->fixtures->create('user', ['contact_mobile' => '9878909876']);

        $testData['response']['content']['items'] = [$user2->toArrayPublic(), $user1->toArrayPublic()];

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testAdminFetchUserByMobileNoMatches()
    {
        $this->fixtures->create('user', ['contact_mobile' => '9878909876']);

        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testAdminFetchUserByMobileInvalidMobileNumberFormat()
    {
        $this->ba->adminAuth();

        $this->startTest();

    }

    public function testAdminFetchPaymentFraudByPaymentId()
    {
        $testData = & $this->testData[__FUNCTION__];

        $paymentFraud = $this->fixtures->create('payment_fraud', ['payment_id'    => '100000Razorpay']);

        $testData['response']['content']['items'][0] = $paymentFraud->toArrayPublic();

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testAdminFetchPaymentFraudByArn()
    {
        $testData = & $this->testData[__FUNCTION__];

        $paymentFraud = $this->fixtures->create('payment_fraud', ['arn'    => '100000Razorpay0000']);

        $testData['response']['content']['items'][0] = $paymentFraud->toArrayPublic();

        $this->ba->adminAuth();

        $this->startTest($testData);
    }

    public function testExternalAdminFetchEntityMultipleLimitedCount()
    {
        for ($i = 0; $i < 6; $i++)
        {
            $this->fixtures->create('payment');
        }

        $this->setupAdminForExternalAdminEntityFetchTest();

    }

    protected function setupWorkflowForCreateAdmin(): void
    {
        $org = (new OrgRepository)->getRazorpayOrg();

        $this->fixtures->on('live')->create('org:workflow_users', ['org' => $org]);

        $this->createWorkflow([
            'org_id' => '100000razorpay',
            'name' => 'create Admin',
            'permissions' => ['create_admin'],
            'levels' => [
                [
                    'level' => 1,
                    'op_type' => 'or',
                    'steps' => [
                        [
                            'reviewer_count' => 1,
                            'role_id' => Org::ADMIN_ROLE,
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function getCreateAdminESData($testData){

        $this->ba->adminAuth();

        $this->setupWorkflowForCreateAdmin();

        $request = $testData['request'];

        $this->makeRequestAndGetContent($request);

        $workflowAction = $this->getLastEntity('workflow_action', true);

        $this->esClient->indices()->refresh();

        return $this->esDao->searchByIndexTypeAndActionId('workflow_action_test_testing', 'action',
            substr($workflowAction['id'], 9))[0]['_source'];
    }

    protected function validateAttributesForExternalAdminEntityFetch(string $entityType, array $actualAttributes)
    {
        $expectedAttributes = [
            'payment'         => [
                'id',
                'merchant_id',
                'amount',
                'base_amount',
                'method',
                'status',
                'authorized_at',
                'captured_at',
                'gateway',
                'gateway_captured',
                'late_authorized',
                'created_at',
                'updated_at',
            ],
            'refund'          => [
                'id',
                'payment_id',
                'merchant_id',
                'amount',
                'base_amount',
                'status',
                'gateway',
                'gateway_refunded',
                'speed_requested',
                'speed_processed',
                'last_attempted_at',
                'processed_at',
                'reference1',
                'created_at',
                'updated_at',
            ],
            'upi'             => [
                'id',
                'gateway',
                'payment_id',
                'amount',
                'created_at',
                'updated_at',
            ],
            'dispute'         => [
                'id',
                'merchant_id',
                'payment_id',
                'amount',
                'base_amount',
                'gateway_dispute_id',
                'status',
                'created_at',
                'updated_at',
            ],
            'merchant'        => [
                'id',
                'name',
                'live',
                'hold_funds',
                'website',
                'billing_label',
                'transaction_report_email',
                'fee_credits_threshold',
                'auto_refund_delay',
            ],
            'billdesk'        => [
                'id',
                'payment_id',
                'MerchantID',
                'CustomerID',
                'TxnAmount',
                'CurrencyType',
                'created_at',
                'updated_at',
            ],
            'netbanking'      => [
                'payment_id',
                'amount',
                'bank',
                'status',
                'refund_id',
                'created_at',
                'updated_at',
            ],
            'atom'            => [
                'id',
                'payment_id',
                'refund_id',
                'amount',
                'status',
                'gateway_payment_id',
                'bank_payment_id',
                'method',
                'created_at',
                'updated_at',
            ],
            'bank_transfer'   => [
                'id',
                'payment_id',
                'merchant_id',
                'amount',
                'utr',
                'created_at',
                'updated_at',
            ],
            'merchant_detail' => [
                'merchant_id',
                'activation_progress',
                'activation_status',
            ],
            'balance'         => [
                'id',
                'merchant_id',
                'type',
                'currency',
                'name',
                'balance',
                'locked_balance',
                'credits',
                'fee_credits',
                'updated_at',
            ],
            'credits'         => [
                'id',
                'merchant_id',
                'value',
                'type',
                'used',
                'expired_at',
                'balance_id',

            ],
        ];

        $this->assertEquals($expectedAttributes[$entityType], $actualAttributes);
    }


    protected function getAllowedEntityTypeForExternalAdminEntityFetch(): array
    {
        $entities = [
            'upi',
            'atom',
            'refund',
            'balance',
            'credits',
            'dispute',
            'merchant',
            'billdesk',
            'netbanking',
            'merchant_detail',
            'bank_transfer',
            'payment',
        ];

        return $entities;
    }

    protected function createTenantBankingRoleFor($admin)
    {
        $role = $this->fixtures->create('role', [
            'org_id'    => Org::RZP_ORG,
            'name'      => 'tenant:banking'
        ]);

        DB::table('role_map')->insert([
            'role_id'     => $role->getId(),
            'entity_type' => 'admin',
            'entity_id'   => $admin->getId(),
        ]);
    }

    public function testEnableInstantRefunds()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '20000000000000']);

        $configs = $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
            'id' => 'HObznkBUFSpME2',
            'merchant_id' => '20000000000000',
            'name' => 'late_auth_HObzmYuKMsauzU',
            'config'     => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": null,
                    "automatic_expiry_period": 7200,
                    "refund_speed": "optimum"
                }
            }']);


        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testDisableInstantRefunds()
    {
        $merchant = $this->fixtures->create('merchant', ['id' => '20000000000000']);

        $this->fixtures->merchant->addFeatures(['merchant_enable_refunds'],$merchant["id"]);

        $configs = $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true,
            'id' => 'HObznkBUFSpME2',
            'merchant_id' => '20000000000000',
            'name' => 'late_auth_HObzmYuKMsauzU',
            'config'     => '{
                "capture": "automatic",
                "capture_options": {
                    "manual_expiry_period": null,
                    "automatic_expiry_period": 7200,
                    "refund_speed": "optimum"
                }
            }']);


        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testToggleWhatsappNotificationOn()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'20000000000000']);

        $this->fixtures->user->createUserForMerchant($merchant['id'], ['contact_mobile' =>'1234567890']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testToggleWhatsappNotificationOff()
    {
        $merchant = $this->fixtures->create('merchant',['id'=>'20000000000000']);

        $this->fixtures->merchant->addFeatures(['axis_whatsapp_enable'],$merchant["id"]);

        $this->fixtures->user->createUserForMerchant($merchant['id'], ['contact_mobile' =>'1234567890']);

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testRiskThresholdFieldNotVisibleOnMerchantEntity()
    {
        $this->ba->adminAuth();

        $this->startTest();

        $merchant = $this->getEntityById('merchant', '10000000000000', true);

        $this->assertArrayNotHasKey('risk_threshold', $merchant);

    }

    public function testBulkAssignRole()
    {
        $admin = $this->fixtures->create('admin', [
            'email' => 'testadmin@rzp.com',
            Admin\Entity::ORG_ID => '100000razorpay',
        ]);

        $this->fixtures->create('admin_token', [
            'id'        => 'AdminToken1234',
            'token'     => Hash::make('secondToken'),
            'admin_id'  => $admin->getId(),
        ]);

        $dummyGrp = $this->fixtures->create('group', ['org_id' => '100000razorpay']);
        $admin->groups()->sync([$dummyGrp->getId()]);

        $bulkRolePerm = $this->fixtures->create('permission',[
            'name'   => Permission::ADMIN_BULK_ASSIGN_ROLE
        ]);
        $admin->roles()->sync([Org::MANAGER_ROLE]);
        $admin->roles()->first()->permissions()->attach($bulkRolePerm->getId());

        $checkerRole = Role\Entity::getSignedId(Org::CHECKER_ROLE);
        $this->testData[__FUNCTION__]['request']['content']['roles'] = (array) $checkerRole;
        $this->testData[__FUNCTION__]['request']['content']['emails'] = (array) $admin->getEmail();

        $this->ba->adminAuth('test', 'secondTokenAdminToken1234', Org::RZP_ORG_SIGNED);
        $this->startTest();

        $adminRoles = $admin->roles()->pluck('id');
        $this->assertEquals(2, count($adminRoles));
        $this->assertEquals([Org::CHECKER_ROLE, Org::MANAGER_ROLE], $adminRoles->toArray());
    }

    public function testAdminFetchBankTransfersWithBankingRole()
    {
        $this->ba->adminAuth();

        $testData = & $this->testData[__FUNCTION__];

        $merchantForBanking = $this->fixtures->create('merchant', ['id' => '12345678901234']);
        $merchantForPrimary = $this->fixtures->create('merchant', ['id' => '12345678905678']);

        $balanceTypeBanking = $this->fixtures->create('balance', [
            'type'           => 'banking',
            'account_type'   => 'shared',
            'account_number' => '2224440041626903',
            'merchant_id'    => $merchantForBanking->getId(),
            'balance'        => 300000
        ]);
        $balanceTypePrimary = $this->fixtures->create('balance', [
            'type'           => 'primary',
            'account_type'   => 'shared',
            'account_number' => '2224440041626904',
            'merchant_id'    => $merchantForPrimary->getId(),
            'balance'        => 400000
        ]);

        $bankTransferForBankingMerchant = $this->fixtures->create('bank_transfer', [
            'id'             => "random12345678",
            'utr'            => "1111",
            'balance_id'     => $balanceTypeBanking->getId(),
            'merchant_id'    => $merchantForBanking->getId()
        ]);
        $bankTransferForPrimaryMerchant = $this->fixtures->create('bank_transfer', [
            'id'             => "random87654321",
            'utr'            => "2222",
            'balance_id'     => $balanceTypePrimary->getId(),
            'merchant_id'    => $merchantForPrimary->getId()
        ]);

        // Test when tenant:banking role is not assigned to the admin
        $response = $this->startTest($testData);

        // assert that both the bank transfers are fetched
        $this->assertEquals("bt_" . $bankTransferForPrimaryMerchant->getId(), $response["items"][0]["id"]);
        $this->assertEquals("bt_" . $bankTransferForBankingMerchant->getId(), $response["items"][1]["id"]);

        $admin = $this->ba->getAdmin();
        $this->createTenantBankingRoleFor($admin);

        // Test when tenant:banking role is assigned to the admin
        $response = $this->startTest($testData);

        // assert that only the bank transfer with a banking merchant is fetched
        $this->assertEquals(1, $response["count"]);
        $this->assertEquals($bankTransferForBankingMerchant->getUtr(), $response["items"][0]["utr"]);
    }

    public function testAdminFetchCardsWithBankingRole()
    {
        $this->ba->adminAuth();

        $testData = & $this->testData[__FUNCTION__];

        $merchant = $this->fixtures->create('merchant', ['id' => '12345678901234']);

        $cardForFundAccount = $this->fixtures->create('card', [
            'id'           => 'Jhfh8uSCfIvYgU',
            'merchant_id'  => $merchant->getId(),
            'name'         => 'bankingCard',
            'expiry_month' => 4,
            'expiry_year'  => 2024,
        ]);
        $this->fixtures->create('fund_account', [
            'account_type'   => 'card',
            'account_id'     => $cardForFundAccount->getId(),
            'merchant_id'    => $merchant->getId()
        ]);

        $cardWithoutFundAccount = $this->fixtures->create('card', [
            'id'           => 'Jhfi1KOH0orayB',
            'name'         => 'PrimaryCard',
            'expiry_month' => 2,
            'expiry_year'  => 2024,
        ]);

        // Test when tenant:banking role is not assigned to the admin
        $response = $this->startTest($testData);

        // Assert that both cards are fetched
        $this->assertEquals("card_" . $cardWithoutFundAccount->getId(), $response["items"][0]["id"]);
        $this->assertEquals("card_" . $cardForFundAccount->getId(), $response["items"][1]["id"]);

        $admin = $this->ba->getAdmin();
        $this->createTenantBankingRoleFor($admin);

        // Test when tenant:banking role is assigned to the admin
        $response = $this->startTest($testData);

        // Assert that only the card linked to a fund account is fetched
        $this->assertEquals(1, $response["count"]);
        $this->assertEquals("card_" . $cardForFundAccount->getId(), $response["items"][0]["id"]);
    }

    public function testAdminFetchBankTransferByIdWithBankingRole()
    {
        $this->ba->adminAuth();

        $testData = & $this->testData[__FUNCTION__];

        $merchantForPrimary = $this->fixtures->create('merchant', ['id' => '12345678905678']);
        $balanceTypePrimary = $this->fixtures->create('balance', [
            'type'           => 'primary',
            'account_type'   => 'shared',
            'account_number' => '2224440041626904',
            'merchant_id'    => $merchantForPrimary->getId(),
            'balance'        => 400000
        ]);
        $bankTransferForPrimaryMerchant = $this->fixtures->create('bank_transfer', [
            'id'             => "random87654321",
            'utr'            => "2222",
            'balance_id'     => $balanceTypePrimary->getId(),
            'merchant_id'    => $merchantForPrimary->getId()
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . '/' . $bankTransferForPrimaryMerchant->getId();

        // Test when tenant:banking role is not assigned to the admin
        $response = $this->startTest($testData);

        // Assert that the card is fetched
        $this->assertEquals("bt_" . $bankTransferForPrimaryMerchant->getId(), $response["id"]);

        $admin = $this->ba->getAdmin();
        $this->createTenantBankingRoleFor($admin);

        $testData = $this->testData['testAdminFetchBankTransferByIdNotFound'];
        $testData['request']['url'] = $testData['request']['url'] . '/' . $bankTransferForPrimaryMerchant->getId();

        // Assert that ID not found error is thrown when tenant:banking role is assigned to the admin
        $this->startTest($testData);
    }

    public function testAdminFetchCardByIdWithBankingRole()
    {
        $this->ba->adminAuth();

        $testData = & $this->testData[__FUNCTION__];

        $merchant = $this->fixtures->create('merchant', ['id' => '12345678901234']);

        $cardForFundAccount = $this->fixtures->create('card', [
            'id'           => 'Jhfh8uSCfIvYgU',
            'merchant_id'  => $merchant->getId(),
            'name'         => 'bankingCard',
            'expiry_month' => 4,
            'expiry_year'  => 2024,
        ]);
        $this->fixtures->create('fund_account', [
            'account_type'   => 'card',
            'account_id'     => $cardForFundAccount->getId(),
            'merchant_id'    => $merchant->getId()
        ]);

        $cardWithoutFundAccount = $this->fixtures->create('card', [
            'id'           => 'Jhfi1KOH0orayB',
            'name'         => 'PrimaryCard',
            'expiry_month' => 2,
            'expiry_year'  => 2024,
        ]);

        $this->testData[__FUNCTION__]['request']['url'] = $this->testData[__FUNCTION__]['request']['url'] . '/' . $cardWithoutFundAccount->getId();

        // Test when tenant:banking role is not assigned to the admin
        $response = $this->startTest($testData);

        // Assert that the card is fetched
        $this->assertEquals("card_" . $cardWithoutFundAccount->getId(), $response["id"]);

        $admin = $this->ba->getAdmin();
        $this->createTenantBankingRoleFor($admin);

        $testData = $this->testData['testAdminFetchCardByIdNotFound'];
        $testData['request']['url'] = $testData['request']['url'] . '/' . $cardWithoutFundAccount->getId();

        // Assert that ID not found error is thrown when tenant:banking role is assigned to the admin
        $this->startTest($testData);
    }
}
