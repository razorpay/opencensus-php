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
        $this->startTest();
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

        $orgHosts = $this->fixtures->times(2)->create('org_hostname',
            ['org_id' => $org->getId()]);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId() . '/self';

        $result = $this->startTest();

        $this->assertNotEmpty($result['hostname']);

        $hostnames = $result['hostname'];
        $hostnames = explode(',', $result['hostname']);

        $this->assertEquals(2, count($hostnames));
    }

    public function testGetOrgByHostname()
    {
        $this->ba->appAuth();

        $this->startTest();
    }
}
