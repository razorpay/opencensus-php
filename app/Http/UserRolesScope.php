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

            // batch routes
            'batch_create'         => Role::READER_ROLES,
            'batch_download_file'  => Role::READER_ROLES,
            'batch_fetch_by_id'    => Role::READER_ROLES,
            'batch_fetch_multiple' => Role::READER_ROLES,

            // payment routes
            'payment_capture'        => Role::WRITER_ROLES,
            'payment_fetch_by_id'    => Role::allExceptSellerRole(),
            'payment_fetch_multiple' => Role::allExceptSellerRole(),
            'payment_refund'         => Role::WRITER_ROLES,

            // order routes
            'order_fetch'       => Role::allExceptSellerRole(),
            'order_fetch_by_id' => Role::allExceptSellerRole(),
            'order_payments'    => Role::allExceptSellerRole(),

            // invitation routes
            'invitation_create' => [Role::OWNER],
            'invitation_delete' => [Role::OWNER],
            'invitation_edit'   => [Role::OWNER],
            'invitation_resend' => [Role::OWNER],

            // merchant routes
            'balance_fetch'                       => Role::allExceptSellerRole(),
            'bank_account_fetch'                  => Role::allExceptSellerRole(),
            'merchant_activation_details'         => [
                                                        Role::OWNER,
                                                        Role::MANAGER,
                                                        Role::ADMIN,
                                                        Role::OPERATIONS,
                                                        Role::FINANCE,
                                                        Role::LINKED_ACCOUNT_OWNER,
                                                        Role::LINKED_ACCOUNT_ADMIN
                                                    ],
            'merchant_create_key'                 => [Role::OWNER, Role::ADMIN],
            'merchant_edit_config_logo'           => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'merchant_fetch_config'               => Role::allExceptSellerRole(),
            'merchant_fetch_referrals'            => Role::allExceptSellerRole(),
            'merchant_sub_create'                 => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'merchant_replace_key'                => [Role::OWNER, Role::ADMIN],
            'merchant_add_bank_account'           => [Role::OWNER, Role::ADMIN],
            'merchant_bank_account_change_status' => [Role::OWNER, Role::ADMIN],

            // webhook routes
            'webhook_create'         => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'webhook_fetch_multiple' => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'webhook_edit'           => [Role::OWNER, Role::MANAGER, Role::ADMIN],

            // settlemnets route
            'setl_fetch_multiple' => Role::READER_ROLES,
            'setl_fetch_by_id'    => Role::READER_ROLES,

            // invoice routes
            'invoice_create'         => array_merge(Role::WRITER_ROLES, [Role::SELLERAPP]),
            'invoice_delete'         => array_merge(Role::WRITER_ROLES, [Role::SELLERAPP]),
            'invoice_edit'           => array_merge(Role::WRITER_ROLES, [Role::SELLERAPP]),
            'invoice_fetch'          => Role::ALL_ROLES,
            'invoice_fetch_multiple' => Role::ALL_ROLES,
            'invoice_issue_by_batch' => Role::WRITER_ROLES,

            // payment link routes
            'payment_link_get'        => Role::WRITER_ROLES,
            'payment_link_list'       => Role::WRITER_ROLES,
            'payment_link_create'     => Role::WRITER_ROLES,
            'payment_link_update'     => Role::WRITER_ROLES,
            'payment_link_notify'     => Role::WRITER_ROLES,
            'payment_link_deactivate' => Role::WRITER_ROLES,
            'payment_link_activate'   => Role::WRITER_ROLES,

            // customer routes
            'customer_fetch_multiple' => Role::allExceptSellerRole(),
            'customer_create'         => Role::WRITER_ROLES,

            // item routes
            'item_create'         => Role::WRITER_ROLES,
            'item_delete'         => Role::WRITER_ROLES,
            'item_fetch_multiple' => Role::allExceptSellerRole(),
            'item_update'         => Role::WRITER_ROLES,

            // marketplace
            'transfer_fetch_multiple' => Role::READER_ROLES,

            // oauth
            'oauth_application_create'         => [Role::OWNER],
            'oauth_application_delete'         => [Role::OWNER],
            'oauth_application_fetch'          => [Role::OWNER],
            'oauth_application_fetch_multiple' => [Role::OWNER],
            'oauth_application_update'         => [Role::OWNER],
            'oauth_token_fetch_multiple'       => [Role::OWNER],
            'oauth_token_revoke'               => [Role::OWNER],

            // va
            'virtual_account_create'         => Role::WRITER_ROLES,
            'virtual_account_fetch'          => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'virtual_account_fetch_multiple' => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'virtual_account_fetch_payments' => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'virtual_account_update'         => Role::WRITER_ROLES,

            // subscriptions
            'plan_account_fetch'          => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'plan_create'                 => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'plan_delete'                 => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'plan_fetch_multiple'         => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'plan_update'                 => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_account_fetch'  => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_cancel'         => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_create'         => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_delete'         => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_fetch_multiple' => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_manual_retry'   => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_test_charge'    => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'subscription_update'         => [Role::OWNER, Role::MANAGER, Role::ADMIN],
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
