<?php

namespace RZP\Models\Admin\Role;

/**
 * List of special-purpose roles, that allow us to classify admin users into different
 * tenants.
 *
 * Created initially for the RX/PG separation project.
 */
class TenantRoles
{
    const ENTITY_PAYMENTS          = 'tenant:payments';
    const ENTITY_BANKING           = 'tenant:banking';
    const ENTITY_PAYMENTS_EXTERNAL = 'tenant:payments_external';
}
