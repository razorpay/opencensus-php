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

    public function testOrgMultiple()
    {
        $this->ba->adminAuth();

        $org = $this->fixtures->create('org', ['email' => 'sreeram12@gmail.com']);
        $org = $this->fixtures->create('org', ['email' => 'sreeram@gmail.com']);

        $this->startTest();
    }

    public function testGetOrg()
    {
        $this->ba->appAuth();

        $org = $this->fixtures->create('org', ['email' => 'sreeram12@gmail.com']);

        $this->testData[__FUNCTION__]['request']['url'] .= '/' . $org->getPublicId() . '/self';

        $this->startTest();
    }
}
