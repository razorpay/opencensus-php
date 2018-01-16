<?php

namespace RZP\Http;

use RZP\Models\User\Role;

class UserRolesScope
{
    /**
     * @see https://docs.razorpay.com/v1/page/team-support
     */
    protected $routeUserRoleMap = null;

    public function __construct()
    {
        $this->setRouteRoleMap();
    }

    public function setRouteRoleMap()
    {
        $this->routeUserRoleMap = [
            'team_users_list'      => Role::OWNER,
            'batch_fetch_multiple' => Role::$readerRoles,
            'batch_fetch_by_id'    => Role::$readerRoles,
        ];
    }

    public function getRouteRoles($routeName)
    {
        if (empty($this->routeUserRoleMap[$routeName]) === false)
        {
            return $this->routeUserRoleMap[$routeName];
        }

        return [];
    }
}

