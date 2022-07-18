<?php

namespace RZP\Http;

use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Role\TenantRoles;

/**
 * Maps Routes to roles that can access the route
 */
class RouteRoleScope
{
    // Unused, shows the structure of routeRoles array stored
    // in ConfigKey::TENANT_ROLES_ROUTES in Cache.
    protected static $routeRolesMap = [
        'payment_refund'     => [TenantRoles::ENTITY_PAYMENTS],
        'payment_capture'    => [TenantRoles::ENTITY_PAYMENTS],
        'payment_verify'     => [TenantRoles::ENTITY_PAYMENTS],
        'payment_get_status' => [TenantRoles::ENTITY_PAYMENTS, TenantRoles::ENTITY_PAYMENTS_EXTERNAL],
    ];

    public static function getRoles(string $routeName)
    {
        $routeRoles = ConfigKey::get(ConfigKey::TENANT_ROLES_ROUTES, []);

        return $routeRoles[$routeName] ?? null;
    }
}
