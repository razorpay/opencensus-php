<?php

namespace RZP\Models\Admin;

use RZP\Constants\Entity;
use RZP\Models\Admin\Role\TenantRoles;

/**
 * Maps Entities to roles that can access the entity.
 * Used for RBAC enforcement.
 */
class EntityRoleScope
{
    // Unused, shows the structure of entityRoles array stored
    // in ConfigKey::TENANT_ROLES_ENTITY in Cache.
    protected static $entityRolesMap = [
        Entity::PAYMENT => [TenantRoles::ENTITY_PAYMENTS, TenantRoles::ENTITY_PAYMENTS_EXTERNAL],
        Entity::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
    ];

    /**
     * @param string $entity
     *
     * @return array|null
     */
    public static function getEntityRoles(string $entity): ?array
    {
        $entityRoles = ConfigKey::get(ConfigKey::TENANT_ROLES_ENTITY, []);

        return $entityRoles[$entity] ?? null;
    }
}
