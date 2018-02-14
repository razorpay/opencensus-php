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
            'team_users_list'             => [Role::OWNER],
            'webhook_edit'                => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'batch_fetch_multiple'        => Role::READER_ROLES,
            'batch_fetch_by_id'           => Role::READER_ROLES,
            'batch_download_file'         => Role::READER_ROLES,
            'batch_create'                => Role::READER_ROLES,
            'payment_fetch_by_id'         => Role::allExceptSellerRole(),
            'merchant_activation_details' => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'merchant_fetch_config'       => Role::allExceptSellerRole(),
            'webhook_fetch_multiple'      => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'payment_fetch_multiple'      => Role::allExceptSellerRole(),
            'order_fetch'                 => Role::allExceptSellerRole(),
            'order_fetch_by_id'           => Role::allExceptSellerRole(),
            'order_payments'              => Role::allExceptSellerRole(),
            'invitation_resend'           => [Role::OWNER],
        ];
    }

    /**
     * @param string $routeName
     *
     * @return array|mixed
     */
    public function getRouteUserRoles(string $routeName)
    {
        return $this->routeUserRoleMap[$routeName] ?? null;
    }
}
