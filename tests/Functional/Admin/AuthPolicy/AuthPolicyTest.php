<?php

namespace RZP\Tests\Functional\Admin\AuthPolicy;

use Hash;
use Carbon\Carbon;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Role\Repository as RoleRepository;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

use RZP\Models\Admin\Admin;

class AuthPolicyTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/AuthPolicyData.php';

        parent::setUp();

        $this->org = $this->createOrg();

        $this->hostName = 'testing.testing.com';

        $this->orgHostName = $this->fixtures->create('org_hostname', [
            'org_id'        => $this->org->getId(),
            'hostname'      => $this->hostName,
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken, $this->org->getPublicId());

        $this->adminRepo = (new Admin\Repository);
    }

    public function testAdminLogin()
    {
        $this->ba->dashboardGuestAppAuth();

        $result = $this->startTest();

        $this->assertArrayHasKey('token', $result);
    }

    public function testAdminLoginWhenLocked()
    {
        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'randomemail@rzp.com',
            'org_id' => $this->org->getId(),
            'failed_attempts' => 10,
            'locked' => true
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $admin = $this->getEntityById('admin', $admin->getId(), true);

        $this->assertNull($admin['last_login_at']);
    }

    public function testWeakPassword()
    {
        $this->startTest();
    }

    public function testSpecialCharactersInPassword()
    {

        $testData = $this->testData['testWeakPassword'];

        $password = "Rzp93Random879";

        $testData['request']['content']['password'] = $password;

        $testData['request']['content']['password_confirmation'] = $password;

        $testData['response']['content']['error']['description'] = 'Password must have special characters';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testUpperLowerCaseInPassword()
    {

        $testData = $this->testData['testWeakPassword'];

        $password = "rzp@93random879";

        $testData['request']['content']['password'] = $password;

        $testData['request']['content']['password_confirmation'] = $password;

        $testData['response']['content']['error']['description'] = 'Password must have combination of uppercase and lowercase characters';

        $this->testData[__FUNCTION__] = $testData;

        $this->startTest();
    }

    public function testShortPassword()
    {
        $this->startTest();
    }

    public function testLongPassword()
    {
        $this->startTest();
    }

    public function testMaxFailedLoginAttempts()
    {
        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'randomemail@rzp.com',
            'org_id' => $this->org->getId(),
            'failed_attempts' => 10
        ]);

        $this->startTest();

        $admin = (new Admin\Repository)->findOrFailPublic($admin->getId());

        $this->assertNull($admin['last_login_at']);
        $this->assertEquals(true, $admin['locked']);
        $this->assertEquals(11, $admin['failed_attempts']);
    }

    public function testPasswordRetainPolicy()
    {
        $oldPasswords = [
            // 123456
            '$2y$10$Iu5YElMOC8ZRKRhQh46.SODijpx0UQfUfnVvUHG4XZfS4jOQKFjkW',
            // test123456
            '$2y$10$kcwfoCfrgZChRwHISglsIeYzPwyh6TNuSaCeGMc9C51AjCEOOy/HK',
            // toughpassword
            '$2y$10$IdcBm3wGwfy2HCLkqrlWFevwfzfynwptNArJ9ACrlwx2mddbK15TS',
            // @#12$%^&dfgh
            '$2y$10$xbRt8IF86kfdyqt2X7aQp.iX1C69HNSvCezRV2mTsYTo/3MG.7H.6',
            // qwerty123456
            '$2y$10$mUWQe/ATmMOBS.6ehmo5W.GlztIhcxFXH5JcQgdQ5sdeDgT8w103S',
            // zxcvbnasdf2345
            '$2y$10$vax680GhSwRUOZiBGK.Yke1uSkcpNcg6JXTcjLxDkIT/TUZo3Q2RK',
            // 98765432poiuyt
            '$2y$10$UWm513HnYiGkncMTKWbhoOGtvrK3MeGSNsbSrHBFs7VjpZJUOJVUy',
            // *&^%@#$%
            '$2y$10$P2Mj.MKwSsiPoDvTm0pPceFW38B/OeN.lfZu4PQQsy.5lAYpzF1uO',
            // iuytrsdfh
            '$2y$10$d208fXY5jBW9c0fNdifVpeGn..lPzCJCDmFwm/z4g4HCebskcwNvK',
            // randompassword
            '$2y$10$lqX9S.Gpr4ZVQHK0iEjSnO/AMOAfDhclolNoOvhhFMvrwPqY3sQke',
        ];

        $admin = $this->fixtures->create('admin', [
            'email' => 'randomemail@rzp.com',
            'org_id' => $this->org->getId(),
            'old_passwords' => $oldPasswords
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $admin = $this->adminRepo->findOrFailPublic($admin->getId());

        $this->assertFalse(Hash::check('@#12$%^&dfgh', $admin['password']));
    }

    public function testPasswordRetainPolicyWithNewPassword()
    {
        $oldPasswords = [
            // 123456
            '$2y$10$Iu5YElMOC8ZRKRhQh46.SODijpx0UQfUfnVvUHG4XZfS4jOQKFjkW',
            // test123456
            '$2y$10$kcwfoCfrgZChRwHISglsIeYzPwyh6TNuSaCeGMc9C51AjCEOOy/HK',
            // toughpassword
            '$2y$10$IdcBm3wGwfy2HCLkqrlWFevwfzfynwptNArJ9ACrlwx2mddbK15TS',
            // @#12$%^&dfgh
            '$2y$10$xbRt8IF86kfdyqt2X7aQp.iX1C69HNSvCezRV2mTsYTo/3MG.7H.6',
            // qwerty123456
            '$2y$10$mUWQe/ATmMOBS.6ehmo5W.GlztIhcxFXH5JcQgdQ5sdeDgT8w103S',
            // zxcvbnasdf2345
            '$2y$10$vax680GhSwRUOZiBGK.Yke1uSkcpNcg6JXTcjLxDkIT/TUZo3Q2RK',
            // 98765432poiuyt
            '$2y$10$UWm513HnYiGkncMTKWbhoOGtvrK3MeGSNsbSrHBFs7VjpZJUOJVUy',
            // *&^%@#$%
            '$2y$10$P2Mj.MKwSsiPoDvTm0pPceFW38B/OeN.lfZu4PQQsy.5lAYpzF1uO',
            // iuytrsdfh
            '$2y$10$d208fXY5jBW9c0fNdifVpeGn..lPzCJCDmFwm/z4g4HCebskcwNvK',
            // randompassword
            '$2y$10$lqX9S.Gpr4ZVQHK0iEjSnO/AMOAfDhclolNoOvhhFMvrwPqY3sQke',
        ];

        $admin = $this->fixtures->create('admin', [
            'email' => 'randomemail@rzp.com',
            'org_id' => $this->org->getId(),
            'old_passwords' => $oldPasswords
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $admin->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();

        $admin = $this->adminRepo->findOrFailPublic($admin->getId());

        $this->assertTrue(Hash::check('@#12$%^&Dfghq', $admin['password']));
        $this->assertFalse(
            in_array(
                '$2y$10$Iu5YElMOC8ZRKRhQh46.SODijpx0UQfUfnVvUHG4XZfS4jOQKFjkW',
                $admin['old_passwords']
            )
        );
    }

    public function testPasswordChangedAtPolicy()
    {
        $this->ba->dashboardGuestAppAuth();

        $passwordChangedAt = Carbon::now()->subDays(40)->timestamp;

        $admin = $this->fixtures->create('admin', [
            'email'               => 'randomemail2@rzp.com',
            'org_id'              => Org::RZP_ORG,
            'password_changed_at' => $passwordChangedAt
        ]);

        $this->startTest();
    }

    protected function createOrg()
    {
        return $this->fixtures->create('org', [
                    'email'         => 'random@rzp.com',
                    'email_domains' => 'rzp.com',
        ]);
    }

    public function testAccessWithWrongOrg()
    {
        // Sign In using razorpay org
        $org = $this->fixtures->create('org', [
            'email'         => 'random@testemail.com',
            'email_domains' => 'rzp.com',
        ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;
    }

    public function testSuperAdminLockOnMaxFailedAttempts()
    {
        $this->ba->dashboardGuestAppAuth($this->hostName);

        $admin = $this->fixtures->create('admin', [
            'email' => 'randomemail@rzp.com',
            'org_id' => $this->org->getId(),
            'failed_attempts' => 10
        ]);

        $superAdminRole = (new RoleRepository())->getSuperAdminRoleByOrgId($this->org->getId());

        $admin->roles()->attach($superAdminRole);

        $this->startTest();

        $admin = (new Admin\Repository)->findOrFailPublic($admin->getId());

        $this->assertNull($admin['last_login_at']);
        // Super admins account will not be locked even failed attempts increase by default.
        $this->assertEquals(false, $admin['locked']);
        $this->assertEquals(11, $admin['failed_attempts']);
    }
}
