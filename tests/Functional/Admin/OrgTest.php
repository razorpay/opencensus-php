<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class OrgTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/OrgData.php';

        parent::setUp();

        $this->ba->adminAuth('test');
    }

    public function testCreateOrg()
    {

        $org = $this->fixtures->create('org',
            [
                'hostname'      => 'hello.com',
                'email_domains' => 'hello.com,fbapi.com',
                'email'         => 'test@hello.com',
                'display_name'  => 'Hello Bank',
                'business_name' => 'Hello Bank Public Limited',
                'auth_type'     => 'password'
            ]);

        $this->startTest();

        $admins = $org->admins();

        $this->assertEquals(1, count($admins));
    }

    public function testEditOrg()
    {
        $org = $this->fixtures->create('org');

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testDeleteOrg()
    {
        $org = $this->fixtures->create('org');

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId();

        $this->startTest();
    }

    public function testfetchMultipleOrg()
    {
        $this->startTest();
    }

    public function testCreateOrgInvalidAuthType()
    {
        $this->startTest();
    }

    public function testCreateOrgInvalidHostname()
    {
        $this->startTest();
    }

    public function testCreateOrgNotUniqueHostname()
    {
        $this->startTest();
    }

    public function testGetOrg()
    {
        $this->ba->appAuth();

        $org = $this->fixtures->create('org', ['email' => 'sreeram12@gmail.com']);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId() . '/self';

        $this->startTest();
    }

    public function testGetOrgByHostname()
    {
        $this->ba->appAuth();

        $this->startTest();
    }
}
