<?php

namespace RZP\Tests\Functional\UserRole\Banking\Dashboard;

use RZP\Models\Merchant;
use RZP\Services\RazorXClient;
use RZP\Models\User\BankingRole;
use RZP\Models\Admin\Permission\Name;

trait BankingRoleTrait
{
    private function getUserRoleRouteMap()
    {
        return [
            BankingRole::OWNER => [
                'payout_bulk_create',
                'payout_create_with_otp',
                'undo_payout_creation',
                'resume_payout_creation',
                'payout_approve_bulk',
                'payout_reject_bulk',
                'payout_approve',
                'payout_reject',
                'payout_fetch_by_id',
                'payout_fetch_multiple',
                'payout_cancel',
                'payout_purpose_get',
                'payout_purpose_post',
                'payout_fetch_reversals',
                'payouts_summary',
                'payouts_workflow_summary',
                'payout_links_fetch_multiple',
                'payout_links_fetch_by_id',
                'payout_links_create',
                'payout_links_cancel',
                'merchant_edit_config_logo',
                'payout_links_merchant_settings_get',
                'payout_links_merchant_settings_post',
                'payout_links_merchant_on_boarding_status',
                'payout_links_merchant_summary',
                'payout_links_resend_notification',
                'contact_get',
                'contact_list',
                'contact_create',
                'bulk_contact_create',
                'contact_update',
                'contact_types_get',
                'contact_types_post',
                'fund_account_validate',
                'fund_account_validate_fetch',
                'fund_account_validate_fetch_by_id',
                'fund_account_get',
                'fund_account_list',
                'fund_account_create',
                'fund_account_update',
                'fund_account_bulk_create',
                'merchant_create_key',
                'merchant_fetch_keys',
                'merchant_replace_key',
                'merchant_analytics',
                'merchant_balance_fetch',
                'merchant_invoice_fetch_multiple',
                'user_edit_self',
                'user_fetch',
                'merchant_fetch_users',
                'webhook_create',
                'webhook_edit',
                'webhook_delete',
                'webhook_fetch',
                'webhook_fetch_events',
                'webhook_fetch_multiple',
                'oauth_app_webhook_create',
                'reporting_log_get',
                'reporting_log_list',
                'reporting_log_create',
                'transaction_statement_fetch',
                'transaction_statement_fetch_multiple',
                'invitation_create',
                'invitation_fetch_by_token',
                'invitation_fetch',
                'invitation_resend',
                'invitation_edit',
                'invitation_delete',
                'invitation_action',
                'reporting_config_edit',
                'reporting_config_delete',
                'reporting_log_get',
                'reporting_log_list',
                'reporting_log_create',
                'reporting_log_update',
                'bank_transfer_process_test',
                'merchant_document_upload',
                'merchant_document_delete',
                'merchant_bank_account_change_status',
                'merchant_2fa_change_setting',
                'user_account_unlock',
                'vendor_payment_reporting_info',
                'vendor_payment_bulk_invoice_download',
                'vendor_payment_get_invoice_zip_file'
            ],
            BankingRole::ADMIN => [
                'payout_bulk_create',
                'payout_create_with_otp',
                'undo_payout_creation',
                'resume_payout_creation',
                'payout_approve_bulk',
                'payout_reject_bulk',
                'payout_approve',
                'payout_reject',
                'payout_fetch_by_id',
                'payout_fetch_multiple',
                'payout_cancel',
                'payout_purpose_get',
                'payout_purpose_post',
                'payout_fetch_reversals',
                'payouts_summary',
                'payouts_workflow_summary',
                'payout_links_fetch_multiple',
                'payout_links_fetch_by_id',
                'payout_links_create',
                'payout_links_cancel',
                'payout_links_merchant_settings_get',
                'payout_links_merchant_settings_post',
                'payout_links_merchant_on_boarding_status',
                'payout_links_merchant_summary',
                'payout_links_resend_notification',
                'merchant_edit_config_logo',
                'contact_get',
                'contact_list',
                'contact_create',
                'bulk_contact_create',
                'contact_update',
                'contact_types_get',
                'contact_types_post',
                'fund_account_validate',
                'fund_account_validate_fetch',
                'fund_account_validate_fetch_by_id',
                'fund_account_get',
                'fund_account_list',
                'fund_account_create',
                'fund_account_update',
                'fund_account_bulk_create',
                'merchant_create_key',
                'merchant_fetch_keys',
                'merchant_replace_key',
                'merchant_analytics',
                'merchant_balance_fetch',
                'merchant_invoice_fetch_multiple',
                'user_edit_self',
                'user_fetch',
                'merchant_fetch_users',
                'webhook_create',
                'webhook_edit',
                'webhook_delete',
                'webhook_fetch',
                'webhook_fetch_events',
                'webhook_fetch_multiple',
                'oauth_app_webhook_create',
                'reporting_log_get',
                'reporting_log_list',
                'reporting_log_create',
                'transaction_statement_fetch',
                'transaction_statement_fetch_multiple',
                'reporting_config_edit',
                'reporting_config_delete',
                'reporting_log_get',
                'reporting_log_list',
                'reporting_log_create',
                'reporting_log_update',
                'bank_transfer_process_test',
                'merchant_features_update',
                'merchant_2fa_change_setting',
                'user_account_unlock',
            ],
            BankingRole::FINANCE_L1 => [
                'payout_bulk_create',
                'payout_create_with_otp',
                'undo_payout_creation',
                'resume_payout_creation',
                'payout_approve_bulk',
                'payout_reject_bulk',
                'payout_approve',
                'payout_reject',
                'payout_fetch_by_id',
                'payout_fetch_multiple',
                'payout_cancel',
                'payout_purpose_get',
                'payout_purpose_post',
                'payout_fetch_reversals',
                'payouts_summary',
                'payouts_workflow_summary',
                'payout_links_fetch_multiple',
                'payout_links_fetch_by_id',
                'payout_links_create',
                'payout_links_cancel',
                'payout_links_merchant_settings_get',
                'payout_links_merchant_settings_post',
                'payout_links_merchant_on_boarding_status',
                'payout_links_merchant_summary',
                'payout_links_resend_notification',
                'merchant_edit_config_logo',
                'contact_get',
                'contact_list',
                'contact_create',
                'bulk_contact_create',
                'contact_update',
                'contact_types_get',
                'contact_types_post',
                'fund_account_validate',
                'fund_account_validate_fetch',
                'fund_account_validate_fetch_by_id',
                'fund_account_get',
                'fund_account_list',
                'fund_account_create',
                'fund_account_update',
                'fund_account_bulk_create',
                'merchant_create_key',
                'merchant_analytics',
                'merchant_balance_fetch',
                'user_edit_self',
                'user_fetch',
                'merchant_fetch_users',
                'webhook_create',
                'webhook_edit',
                'webhook_delete',
                'webhook_fetch',
                'webhook_fetch_events',
                'webhook_fetch_multiple',
                'oauth_app_webhook_create',
                'reporting_log_get',
                'reporting_log_list',
                'reporting_log_create',
                'transaction_statement_fetch',
                'transaction_statement_fetch_multiple',
                'reporting_config_edit',
                'reporting_config_delete',
                'reporting_log_get',
                'reporting_log_list',
                'reporting_log_create',
                'reporting_log_update',
                'bank_transfer_process_test',
                'invitation_create'
            ],

            BankingRole::OPERATIONS => [
                'user_otp_create',
                'user_edit_self',
                'user_fetch',
                'reporting_log_get',
                'payout_fetch_by_id',
                'payout_links_fetch_multiple',
                'payout_links_create',
                'payout_links_cancel',
                'payout_links_merchant_settings_get',
                'payout_links_merchant_settings_post',
                'payout_links_merchant_on_boarding_status',
                'payout_links_merchant_summary',
                'payout_links_resend_notification',
                'merchant_edit_config_logo',
            ],

            BankingRole::VIEW_ONLY => [
                'payout_fetch_by_id',
                'payout_purpose_get',
                'payout_fetch_reversals',
                'payouts_summary',
                'payout_links_fetch_multiple',
                'payout_links_fetch_by_id',
                'payouts_workflow_summary',
                'contact_get',
                'contact_list',
                'contact_types_get',
                'fund_account_validate_fetch',
                'fund_account_get',
                'fund_account_list',
                'merchant_fetch_keys',
                'merchant_analytics',
                'merchant_balance_fetch',
                'merchant_invoice_fetch_multiple',
                'user_fetch',
                'merchant_fetch_users',
                'webhook_fetch',
                'webhook_fetch_multiple',
                'webhook_fetch_events',
                'reporting_log_get',
                'reporting_log_list',
                'transaction_statement_fetch',
                'transaction_statement_fetch_multiple',
                'reporting_config_get',
                'reporting_config_list',
                'reporting_log_create',
                'merchant_product_switch',
                'payout_links_merchant_summary',
                'payout_links_merchant_on_boarding_status',
                'user_edit_self',
            ],

            BankingRole::CHARTERED_ACCOUNTANT => [
                'reporting_config_list',
                'reporting_log_get',
                'reporting_log_list',
                'vendor_payment_reporting_info',
                'vendor_payment_bulk_invoice_download',
                'vendor_payment_get_invoice_zip_file',
                'merchant_analytics',
                'merchant_fetch_users',
                'user_edit_self'
            ]
        ];
    }

    private function getUserRolePermissionMap()
    {
        return [
            BankingRole::OWNER => [
                Name::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_FINANCEX_REPORT,
                Name::CREATE_FINANCEX_REPORT,
                Name::ACCOUNTS_RECEIVABLE_ADMIN,
                Name::BILL_PAYMENTS_VIEW,
                Name::BILL_PAYMENTS_CREATE_ACCOUNT,
                Name::BILL_PAYMENTS_FETCH_BILL,
            ],

            BankingRole::ADMIN => [
                Name::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_FINANCEX_REPORT,
                Name::CREATE_FINANCEX_REPORT,
                Name::ACCOUNTS_RECEIVABLE_ADMIN,
                Name::BILL_PAYMENTS_VIEW,
                Name::BILL_PAYMENTS_CREATE_ACCOUNT,
                Name::BILL_PAYMENTS_FETCH_BILL,
            ],

            BankingRole::FINANCE_L1 => [
                Name::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                Name::ACCOUNTS_RECEIVABLE_ADMIN,
                Name::BILL_PAYMENTS_VIEW,
                Name::BILL_PAYMENTS_CREATE_ACCOUNT,
                Name::BILL_PAYMENTS_FETCH_BILL,
            ],

            BankingRole::FINANCE_L2 => [
                Name::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                Name::ACCOUNTS_RECEIVABLE_ADMIN,
                Name::BILL_PAYMENTS_VIEW,
                Name::BILL_PAYMENTS_CREATE_ACCOUNT,
                Name::BILL_PAYMENTS_FETCH_BILL,
            ],

            BankingRole::FINANCE_L3 => [
                Name::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                Name::ACCOUNTS_RECEIVABLE_ADMIN,
                Name::BILL_PAYMENTS_VIEW,
                Name::BILL_PAYMENTS_CREATE_ACCOUNT,
                Name::BILL_PAYMENTS_FETCH_BILL,
            ],

            BankingRole::OPERATIONS => [
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                Name::ACCOUNTS_RECEIVABLE_ADMIN,
                Name::BILL_PAYMENTS_VIEW,
                Name::BILL_PAYMENTS_CREATE_ACCOUNT,
                Name::BILL_PAYMENTS_FETCH_BILL,
            ],

            BankingRole::VIEW_ONLY => [
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                Name::BILL_PAYMENTS_VIEW,
            ],

            BankingRole::CHARTERED_ACCOUNTANT => [
                Name::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                Name::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
            ]
        ];
    }

    protected function getUserRolePermissibleRouteMap(string $role)
    {
        return $this->getUserRoleRouteMap()[$role] ?? null;
    }

    protected function mockRazorXTreatmentAccessDenyUnauthorised($value = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                        ->setConstructorArgs([$this->app])
                        ->setMethods(['getTreatment'])
                        ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->will($this->returnCallback(
                                    function ($mid, $feature, $mode) use ($value)
                                    {
                                        if ($feature === Merchant\RazorxTreatment::RAZORPAY_X_ACL_DENY_UNAUTHORISED)
                                        {
                                            return $value;
                                        }

                                        return 'off';
                                    }));
    }

    protected function disableRazorXTreatmentCAC()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function ($mid, $feature, $mode) {
                    if ($feature === 'rx_custom_access_control_disabled')
                    {
                        return 'on';
                    }

                    return 'control';
                }));
    }

    protected function getLegacyRoles()
    {
        return [
            BankingRole::OWNER,
            BankingRole::ADMIN,
            BankingRole::FINANCE_L1,
            BankingRole::FINANCE_L2,
            BankingRole::FINANCE_L3,
            BankingRole::OPERATIONS,
            BankingRole::VIEW_ONLY,
            BankingRole::CHARTERED_ACCOUNTANT
        ];
    }

    protected function getUserRolePermissiblePermissions(string $role)
    {
        return $this->getUserRolePermissionMap()[$role] ?? null;
    }
}
