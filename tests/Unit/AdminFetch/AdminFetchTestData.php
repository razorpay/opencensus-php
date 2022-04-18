<?php

use RZP\Constants\Entity as E;
use RZP\Models\Admin\Role\TenantRoles;

return [
    'testFilterEntitiesByRoleMapEmpty' => [
        'entity_roles_map' => [],
        'entities'         => [
            E::PAYMENT      => [],
            E::REFUND       => [],
            E::BANK_ACCOUNT => [],
        ],
        'admin_roles'      => [
            'random_role',
            TenantRoles::ENTITY_PAYMENTS,
        ],
    ],

    'testFilterEntitiesByRoleSuccess' => [
        'entity_roles_map' => [
            E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS],
            E::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
        ],
        'entities'         => [
            E::PAYMENT      => [],
            E::REFUND       => [],
            E::BANK_ACCOUNT => [],
        ],
        'admin_roles'      => [
            'random_role',
            TenantRoles::ENTITY_PAYMENTS,
        ],
    ],

    'testFilterEntitiesByRoleNotMapped' => [
        'entity_roles_map' => [
            E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS],
            E::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
        ],
        'entities'         => [
            E::BANK_ACCOUNT => [],
        ],
        'admin_roles'      => [
            'random_role'
        ],
    ],

    'testFilterEntitiesByRoleFiltered' => [
        'entity_roles_map' => [
            E::PAYMENT => [TenantRoles::ENTITY_PAYMENTS],
            E::REFUND  => [TenantRoles::ENTITY_PAYMENTS],
        ],
        'entities'         => [
            E::BANK_ACCOUNT => [],
            E::PAYMENT      => [],
            E::REFUND       => [],
            E::MERCHANT     => [],
        ],
        'admin_roles'      => [
            'random_role'
        ],
    ],
];
