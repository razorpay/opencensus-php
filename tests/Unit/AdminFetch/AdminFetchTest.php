<?php

namespace Unit\AdminFetch;

use Cache;

use RZP\Tests\TestCase;
use RZP\Constants\AdminFetch;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;

class AdminFetchTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigKey::resetFetchedKeys();

        $this->testDataFilePath = __DIR__ . '/AdminFetchTestData.php';

        parent::setUp();
    }

    public function testFilterEntitiesByRoleMapEmpty()
    {
        $data = $this->testData[__FUNCTION__];

        // Case where the EntityRoleMap is empty, so nothing should be filtered.

        $result = AdminFetch::filterEntitiesByRole($data['entities'], $data['admin_roles']);

        $this->assertEquals($data['entities'], $result, 'Failed to filter entities for tentant roles');
    }

    public function testFilterEntitiesByRoleSuccess()
    {
        $data = $this->testData[__FUNCTION__];

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn($data['entity_roles_map']);

        $result = AdminFetch::filterEntitiesByRole($data['entities'], $data['admin_roles']);

        $this->assertEquals($data['entities'], $result, 'Failed to filter entities for tentant roles');
    }

    public function testFilterEntitiesByRoleNotMapped()
    {
        $data = $this->testData[__FUNCTION__];

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn($data['entity_roles_map']);

        $result = AdminFetch::filterEntitiesByRole($data['entities'], $data['admin_roles']);

        $this->assertEquals($data['entities'], $result, 'Failed to filter entities for tentant roles');
    }

    public function testFilterEntitiesByRoleFiltered()
    {
        $data = $this->testData[__FUNCTION__];

        Cache::shouldReceive('get')
             ->once()
             ->with(ConfigKey::TENANT_ROLES_ENTITY)
             ->andReturn($data['entity_roles_map']);

        $result = AdminFetch::filterEntitiesByRole($data['entities'], $data['admin_roles']);

        $this->assertEquals([E::BANK_ACCOUNT, E::MERCHANT], array_keys($result), 'Failed to filter entities for tentant roles');
    }
}
