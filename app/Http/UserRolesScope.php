<?php

namespace RZP\Http;

use RZP\Models\User\Role;
use RZP\Models\Batch\Type;
use RZP\Models\User\BankingRole;

class UserRolesScope
{
    /**
     * @see https://razorpay.com/docs/v1/page/team-support
     */
    protected $routeUserRoleMap = [];

    protected $batchTypeUserRoleMap = [];

    const DEFAULT = 'default';

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
            'batch_create'         => array_merge(Role::READER_ROLES, [Role::RBL_SUPERVISOR, Role::LINKED_ACCOUNT_OWNER,Role::SELLERAPP, Role::AUTH_LINK_SUPERVISOR], BankingRole::getAllRoles()),
            'batch_download_file'  => array_merge(Role::READER_ROLES, [Role::RBL_SUPERVISOR, Role::LINKED_ACCOUNT_OWNER,Role::SELLERAPP, Role::AUTH_LINK_SUPERVISOR], BankingRole::getAllRoles()),
            'batch_fetch_by_id'    => array_merge(Role::READER_ROLES, [Role::RBL_SUPERVISOR, Role::LINKED_ACCOUNT_OWNER,Role::SELLERAPP, Role::AUTH_LINK_SUPERVISOR], BankingRole::getAllRoles()),
            'batch_fetch_multiple' => array_merge(Role::READER_ROLES, [Role::RBL_SUPERVISOR, Role::LINKED_ACCOUNT_OWNER,Role::SELLERAPP, Role::AUTH_LINK_SUPERVISOR], BankingRole::getAllRoles()),

            // payment routes
            'payment_capture'        => array_merge(Role::WRITER_ROLES, [ROLE::RBL_SUPERVISOR]),
            'payment_fetch_by_id'    => array_merge(Role::allExceptPaymentLinkRoles(), Role::RBL_ROLES, [Role::AGENT]),
            'payment_fetch_multiple' => array_merge(Role::allExceptPaymentLinkRoles(), Role::RBL_ROLES, [Role::AGENT]),
            'payment_refund'         => Role::WRITER_ROLES,

            // refund routes
            'refund_create'              => Role::WRITER_ROLES,
            'refund_fetch_by_id'         => Role::allExceptPaymentLinkRoles(),
            'refund_fetch_multiple'      => Role::allExceptPaymentLinkRoles(),
            'payment_fetch_refunds'      => Role::allExceptPaymentLinkRoles(),
            'payment_fetch_refund_by_id' => Role::allExceptPaymentLinkRoles(),

            // order routes
            'order_fetch'       => array_merge(Role::allExceptPaymentLinkRoles(), Role::RBL_ROLES),
            'order_fetch_by_id' => array_merge(Role::allExceptPaymentLinkRoles(), Role::RBL_ROLES),
            'order_payments'    => array_merge(Role::allExceptPaymentLinkRoles(), Role::RBL_ROLES),

            // invitation routes
            'invitation_create' => [Role::OWNER, Role::LINKED_ACCOUNT_OWNER, Role::RBL_SUPERVISOR],
            'invitation_delete' => [Role::OWNER, Role::LINKED_ACCOUNT_OWNER, Role::RBL_SUPERVISOR],
            'invitation_edit'   => [Role::OWNER, Role::LINKED_ACCOUNT_OWNER, Role::RBL_SUPERVISOR],
            'invitation_resend' => [Role::OWNER, Role::LINKED_ACCOUNT_OWNER, Role::RBL_SUPERVISOR],
            'invitation_fetch'  => [Role::OWNER, Role::LINKED_ACCOUNT_OWNER, Role::RBL_SUPERVISOR],

            // Banking Invoice route
            'reports_monthly_banking_invoice' => BankingRole::getDefaultRoles(),

            // profile routes
            'merchant_gst_fetch' => [Role::OWNER, Role::FINANCE, Role::MANAGER],
            'merchant_gst_edit'  => [Role::OWNER, Role::FINANCE],

            // merchant routes
            'balance_fetch'                       => array_merge(Role::allExceptPaymentLinkRoles(), Role::LINKED_ACCOUNT_ROLES),
            'merchant_balance_fetch'              => array_merge(Role::allExceptPaymentLinkRoles(), Role::LINKED_ACCOUNT_ROLES),
            'bank_account_fetch'                  => Role::allExceptPaymentLinkRoles(),
            'merchant_activation_details'         => array_merge([
                Role::OWNER,
                Role::MANAGER,
                Role::ADMIN,
                Role::OPERATIONS,
                Role::FINANCE,
                Role::LINKED_ACCOUNT_OWNER,
                Role::LINKED_ACCOUNT_ADMIN,
            ], BankingRole::getAllRoles()),
            'merchant_edit_email_la'                => [Role::OWNER, Role::ADMIN],
            'merchant_create_key'                   => [Role::OWNER, Role::ADMIN],
            'merchant_fetch_keys'                   => [Role::OWNER, Role::ADMIN, Role::SELLERAPP],
            'merchant_edit_config_logo'             => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'merchant_fetch_config'                 => Role::allExceptPaymentLinkRoles(),
            'merchant_fetch_referrals'              => Role::allExceptPaymentLinkRoles(),
            'merchant_edit_config'                  => Role::allExceptSellerAppRole(),
            'merchant_sub_create'                   => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'merchant_replace_key'                  => [Role::OWNER, Role::ADMIN],
            'merchant_add_bank_account'             => [Role::OWNER, Role::ADMIN],
            'merchant_bank_account_change_status'   => [Role::OWNER, Role::ADMIN],
            'create_submerchant_user'               => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'merchant_fetch_users'                  => [Role::OWNER, Role::LINKED_ACCOUNT_OWNER, Role::RBL_SUPERVISOR],
            'merchant_gstin_self_serve_status'      => [Role::OWNER, Role::ADMIN],
            'merchant_gstin_self_serve_update'      => [Role::OWNER, Role::ADMIN],
            'merchant_edit_email_self_serve'        => [Role::OWNER],
            'email_user_status_for_email_update'    => [Role::OWNER],
            'merchant_save_business_website'        => [Role::OWNER],
            'merchant_toggle_fee_bearer'            => [Role::OWNER],
            'increase_transaction_limit_self_serve' => [Role::OWNER, Role::ADMIN],
            'add_additional_website_self_serve'     => [Role::OWNER, Role::ADMIN],
            // Merchant user routes
            'user_merchant_mapping_action' => [Role::OWNER, Role::LINKED_ACCOUNT_OWNER, Role::RBL_SUPERVISOR],

            //2fa
            'merchant_2fa_change_setting' => [Role::OWNER],

            // webhook routes
            'webhook_create'            => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'webhook_fetch_multiple'    => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'webhook_edit'              => [Role::OWNER, Role::MANAGER, Role::ADMIN],
            'webhook_delete'            => [Role::OWNER, Role::MANAGER, Role::ADMIN],

            // settlements route
            'setl_fetch_multiple' => array_merge(Role::READER_ROLES,Role::LINKED_ACCOUNT_ROLES,
                [Role::RBL_SUPERVISOR, Role::AGENT]),
            'setl_fetch_by_id'    => array_merge(Role::READER_ROLES,Role::LINKED_ACCOUNT_ROLES,
                [Role::RBL_SUPERVISOR, Role::AGENT]),

            // Invoice routes
            'invoice_create'                    => array_merge(Role::WRITER_ROLES,Role::PL_ROLES, [Role::RBL_SUPERVISOR]),
            'invoice_delete'                    => array_merge(Role::WRITER_ROLES,Role::PL_ROLES, [Role::RBL_SUPERVISOR]),
            'invoice_update'                    => array_merge(Role::WRITER_ROLES,Role::PL_ROLES, [Role::RBL_SUPERVISOR]),
            'invoice_issue'                     => array_merge(Role::WRITER_ROLES,Role::PL_ROLES, Role::RBL_ROLES),
            'invoice_send_notification_private' => array_merge(Role::WRITER_ROLES,Role::PL_ROLES, Role::RBL_ROLES),
            'invoice_cancel'                    => array_merge(Role::WRITER_ROLES,Role::PL_ROLES, [Role::RBL_SUPERVISOR]),
            'invoice_fetch'                     => array_merge(Role::ALL_ROLES, Role::RBL_ROLES),
            'invoice_fetch_multiple'            => array_merge(Role::ALL_ROLES, Role::RBL_ROLES),
            'invoice_issue_by_batch'            => array_merge(Role::WRITER_ROLES, [Role::RBL_SUPERVISOR]),

            // Payment link routes
            'payment_link_get'         => Role::WRITER_ROLES,
            'payment_link_list'        => Role::WRITER_ROLES,
            'payment_link_create'      => Role::WRITER_ROLES,
            'payment_link_update'      => Role::WRITER_ROLES,
            'payment_link_notify'      => Role::WRITER_ROLES,
            'payment_link_deactivate'  => Role::WRITER_ROLES,
            'payment_link_activate'    => Role::WRITER_ROLES,
            'payment_link_slug_exists' => Role::WRITER_ROLES,

            // customer routes
            'customer_fetch_multiple' => array_merge(Role::allExceptPaymentLinkRoles(), [Role::SELLERAPP_PLUS, Role::SELLERAPP]),
            'customer_create'         => Role::WRITER_ROLES,

            // item routes
            'item_create'         => array_merge(Role::WRITER_ROLES, [Role::RBL_SUPERVISOR]),
            'item_delete'         => array_merge(Role::WRITER_ROLES, [Role::RBL_SUPERVISOR]),
            'item_update'         => array_merge(Role::WRITER_ROLES, [Role::RBL_SUPERVISOR]),
            'item_fetch_multiple' => array_merge(Role::allExceptPaymentLinkRoles(), [ROLE::RBL_SUPERVISOR, Role::SELLERAPP_PLUS, Role::SELLERAPP]),

            // marketplace
            'transfer_fetch_multiple'      => Role::READER_ROLES,
            'merchant_dashboard_access_la' => [Role::OWNER, Role::ADMIN, Role::MANAGER],

            // TODO change the role to LA dashboard admin and owner after launch.
            'transfer_fetch_multiple_la'  => Role::LINKED_ACCOUNT_ROLES,
            'transfer_fetch_la'           => Role::LINKED_ACCOUNT_ROLES,
            'transfer_fetch_reversals_la' => Role::LINKED_ACCOUNT_ROLES,
            'reversal_fetch_multiple_la'  => Role::LINKED_ACCOUNT_ROLES,
            'reversal_fetch_la'           => Role::LINKED_ACCOUNT_ROLES,

            // oauth
            'oauth_application_create'         => [Role::OWNER],
            'oauth_application_delete'         => [Role::OWNER],
            'oauth_application_fetch'          => [Role::OWNER],
            'oauth_application_fetch_multiple' => [Role::OWNER],
            'oauth_application_update'         => [Role::OWNER],
            'oauth_token_fetch_multiple'       => [Role::OWNER],
            'oauth_token_revoke'               => [Role::OWNER],

            // va
            'virtual_account_create'                => Role::WRITER_ROLES,
            'virtual_account_fetch'                 => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'virtual_account_fetch_multiple'        => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'virtual_account_fetch_payments'        => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'virtual_account_edit'                  => Role::WRITER_ROLES,
            'virtual_account_close'                 => Role::WRITER_ROLES,
            'virtual_account_add_receivers'         => Role::WRITER_ROLES,
            'virtual_vpa_prefix_save'               => Role::WRITER_ROLES,
            'virtual_account_add_allowed_payer'     => Role::WRITER_ROLES,

            // qr_codes v2
            'qr_code_create'                 => Role::WRITER_ROLES,
            'qr_code_close'                  => Role::WRITER_ROLES,
            'qr_code_fetch'                  => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'qr_code_fetch_multiple'         => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'qr_payments_fetch_multiple'     => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),
            'qr_payment_fetch_for_qr_code'   => array_merge(Role::READER_ROLES, [ROLE::SUPPORT]),


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

            // Partner routes
            'submerchants_fetch'          => Role::allExceptPaymentLinkRoles(),
            'submerchants_fetch_multiple' => Role::allExceptPaymentLinkRoles(),
            'fetch_partner_intent'        => [Role::OWNER],
            'update_partner_intent'       => [Role::OWNER],
            'update_partner_type'         => [Role::OWNER],

            'loc_service'                 => [Role::OWNER, Role::ADMIN],
            'capital_cards_service'       => [Role::OWNER, Role::ADMIN],
            'capital_collections_service' => [Role::OWNER, Role::ADMIN],

            // Reporting
            'reporting_config_get'        => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_config_list'       => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_config_create'     => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_config_edit'       => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_config_delete'     => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_log_get'           => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_log_list'          => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_log_create'        => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_log_update'        => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_schedule_get'      => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_schedule_list'     => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_schedule_create'   => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),
            'reporting_schedule_delete'   => array_merge(Role::ALL_ROLES,Role::LINKED_ACCOUNT_ROLES, [Role::RBL_SUPERVISOR], BankingRole::getAllRoles()),

            // Low Balance Config
            'create_low_balance_config'   => [BankingRole::OWNER],
            'update_low_balance_config'   => [BankingRole::OWNER],
            'delete_low_balance_config'   => [BankingRole::OWNER],
            'disable_low_balance_config'  => [BankingRole::OWNER],
            'enable_low_balance_config'   => [BankingRole::OWNER],

            'create_merchant_notification_config'         => [Role::OWNER],
            'update_merchant_notification_config'         => [Role::OWNER],
            'delete_merchant_notification_config'         => [Role::OWNER],
            'disable_merchant_notification_config'        => [Role::OWNER],
            'enable_merchant_notification_config'         => [Role::OWNER],

            'create_merchant_notification_config_admin'   => [Role::ADMIN],

            'payment_page_images'                      => Role::WRITER_ROLES,
            'payment_page_get'                         => Role::WRITER_ROLES,
            'payment_page_get_details'                 => Role::WRITER_ROLES,
            'payment_page_list'                        => Role::WRITER_ROLES,
            'payment_page_create'                      => Role::WRITER_ROLES,
            'payment_page_update'                      => Role::WRITER_ROLES,
            'payment_page_notify'                      => Role::WRITER_ROLES,
            'payment_page_deactivate'                  => Role::WRITER_ROLES,
            'payment_page_activate'                    => Role::WRITER_ROLES,
            'payment_page_slug_exists'                 => Role::WRITER_ROLES,
            'payment_page_item_update'                 => Role::WRITER_ROLES,
            'payment_page_items_migrate'               => Role::WRITER_ROLES,
            'payment_page_items_migrate_min_purchase'  => Role::WRITER_ROLES,
            'payment_page_set_merchant_details'        => Role::WRITER_ROLES,
            'payment_page_fetch_merchant_details'      => Role::WRITER_ROLES,
            'payment_page_set_receipt_details'         => Role::WRITER_ROLES,
            'payment_page_get_invoice_details'         => Role::WRITER_ROLES,
            'payment_page_send_receipt'                => Role::WRITER_ROLES,
            'payment_page_save_receipt_for_payment'    => Role::WRITER_ROLES,

            'patch_dispute_contest_by_id'              => Role::READER_ROLES,
            'post_dispute_accept_by_id'                => Role::READER_ROLES,
        ];

        /*
         * This array is for batch type level user access roles map
         */
        $this->batchTypeUserRoleMap = [
            Type::PAYMENT_LINK         => array_merge(Role::READER_ROLES, [Role::RBL_SUPERVISOR, Role::LINKED_ACCOUNT_OWNER, Role::SELLERAPP], BankingRole::getAllRoles()),
            Type::AUTH_LINK            => array_merge(Role::READER_ROLES, [Role::RBL_SUPERVISOR, Role::LINKED_ACCOUNT_OWNER, Role::AUTH_LINK_SUPERVISOR], BankingRole::getAllRoles()),
            self::DEFAULT              => array_merge(Role::READER_ROLES, [Role::RBL_SUPERVISOR, Role::LINKED_ACCOUNT_OWNER], BankingRole::getAllRoles()),
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

    /**
     * @param string $batchType
     *
     * @return array
     */
    public function getRouteBatchTypeUserRoles(string $batchType = self::DEFAULT): array
    {
        return $this->batchTypeUserRoleMap[$batchType] ?? $this->batchTypeUserRoleMap[self::DEFAULT];
    }
}
