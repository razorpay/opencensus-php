<?php

namespace RZP\Http;

use RZP\Models\User\Role;

class UserRolesScope
{
    /**
     * @see https://docs.razorpay.com/v1/page/team-support
     */
    protected $routeUserRoleMap = [];

    public function __construct()
    {
        $this->setRouteUserRoleMap();
    }

    /**
     * Sets Route User Role Map.
     */
    public function setRouteUserRoleMap()
    {
        $this->routeUserRoleMap = [
            'team_users_list'      => Role::OWNER,
            'batch_fetch_multiple' => Role::READER_ROLES,
            'batch_fetch_by_id'    => Role::READER_ROLES,
        ];
    }

    /**
     * @param $routeName
     *
     * @return array|mixed
     */
    public function getRouteUserRoles($routeName)
    {
        return $this->routeUserRoleMap[$routeName] ?? [];
    }
}

