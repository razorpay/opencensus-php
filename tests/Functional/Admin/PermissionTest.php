<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\TestCase;

class PermissionTest extends TestCase
{
    use HeimdallTrait;

    const TOTAL_PERMISSIONS = 120;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/PermissionData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
        ]);

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken);
    }

    public function testGetPermission()
    {
        $perm = $this->fixtures->create(
            'permission', ['name' => 'test permission']);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/'. $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testCreatePermission()
    {
        $this->startTest();
    }

    public function testDeletePermission()
    {
        $perm = $this->fixtures->create(
            'permission');

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGetMultipleForRazorpayOrg()
    {
        $this->ba->adminAuth();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $orgId = 'org_' . Org::RZP_ORG;

        $url = sprintf($url, $orgId);

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $result = $this->startTest();

        $this->assertEquals(self::TOTAL_PERMISSIONS, $result['count']);
    }

    public function testEditPermission()
    {
        $perm = $this->fixtures->create(
            'permission');

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = $url . '/' . $perm->getPublicId();

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
}
