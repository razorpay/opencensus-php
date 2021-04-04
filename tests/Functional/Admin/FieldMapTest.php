<?php

namespace RZP\Tests\Functional\Admin;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class FieldMapTest extends TestCase
{
    use RequestResponseFlowTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/FieldMapTestData.php';

        parent::setUp();

        $this->org = $this->fixtures->create('org', [
            'email'         => 'random@rzp.com',
            'email_domains' => 'rzp.com',
            'auth_type'     => 'password',
        ]);

        $this->orgId = $this->org->getId();

        $this->orgPublicId = $this->org->getPublicId();

        $this->authToken = $this->getAuthTokenForOrg($this->org);

        $this->ba->adminAuth('test', $this->authToken);
    }

    public function testCreateFieldMap()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testInvalidFieldMap()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testInvalidEntityForFieldMap()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }


    protected function createDefaultFieldMap()
    {
        return $this->fixtures->create(
            'org_field_map',
            [
                'org_id'      => $this->orgId,
                'entity_name' => 'org',
                'fields'      => ['display_name', 'business_name'],
            ]);
    }

    public function testGetFieldMap()
    {
        $fieldMap = $this->createDefaultFieldMap();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $fieldMap->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test', $this->authToken, $this->orgPublicId);

        $this->startTest();
    }

    public function testPutFieldMap()
    {
        $fieldMap = $this->createDefaultFieldMap();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $fieldMap->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test', $this->authToken, $this->orgPublicId);

        $this->startTest();
    }

    public function testDeleteFieldMap()
    {
        $fieldMap = $this->createDefaultFieldMap();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $fieldMap->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test', $this->authToken, $this->orgPublicId);

        $result = $this->startTest();
    }

    public function testFieldMapMultiple()
    {
        $this->fixtures->create(
            'org_field_map',
            [
                'org_id'      => $this->orgId,
                'entity_name' => 'org',
                'fields'      => ['display_name', 'business_name'],
            ]);

        $this->fixtures->create(
            'org_field_map',
            [
                'org_id'      => $this->orgId,
                'entity_name' => 'admin',
                'fields'      => ['name', 'email'],
            ]);

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test', $this->authToken, $this->orgPublicId);

        $result = $this->startTest();

        $this->assertEquals(2, count($result['items']));
    }

    public function testGetFieldMapByEntity()
    {
        $fieldMap = $this->createDefaultFieldMap();

        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $fieldMap->getNameOfEntity());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->ba->adminAuth('test', $this->authToken, $this->orgPublicId);

        $this->startTest();
    }

    public function testCreateFieldMapForPasswordAuth()
    {
        $url = $this->testData[__FUNCTION__]['request']['url'];

        $url = sprintf($url, $this->org->getPublicId());

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }
}
