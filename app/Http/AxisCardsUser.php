<?php

namespace RZP\Http;

use RZP\Models\User\AxisUserRole;
use RZP\Models\Admin\Permission\Name as Permission;

class AxisCardsUser
{
    private static $rolePermissions;

    private static function init()
    {
        $rolePermissions = [
            AxisUserRole::AUTHORISED_SIGNATORY => [
                Permission::CREATE_PAYOUT,
                Permission::CREATE_PAYOUT_BULK,
                Permission::APPROVE_PAYOUT_BULK,
                Permission::REJECT_PAYOUT_BULK,
                Permission::APPROVE_PAYOUT,
                Permission::REJECT_PAYOUT,
                Permission::VIEW_PAYOUT,
                Permission::CANCEL_PAYOUT,
                Permission::VIEW_PAYOUT_PURPOSE,
                Permission::CREATE_PAYOUT_PURPOSE,
                Permission::VIEW_PAYOUT_REVERSAL,
                Permission::PROCESS_PAYOUT_QUEUED,
                Permission::VIEW_PAYOUT_SUMMARY,
                Permission::VIEW_PAYOUT_WORKFLOW_SUMMARY,
                Permission::VIEW_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS_BATCH,
                Permission::CANCEL_PAYOUT_LINKS,
                Permission::ONBOARDING_PAYOUT_LINKS,
                Permission::SUMMARY_PAYOUT_LINKS,
                Permission::SETTINGS_PAYOUT_LINKS,
                Permission::DASHBOARD_PAYOUT_LINKS,
                Permission::RESEND_PAYOUT_LINKS,
                Permission::MERCHANT_CONFIG_LOGO,
                Permission::VIEW_CONTACT,
                Permission::CREATE_CONTACT,
                Permission::CREATE_CONTACT_BULK,
                Permission::UPDATE_CONTACT,
                Permission::DELETE_CONTACT,
                Permission::VIEW_CONTACT_TYPE,
                Permission::CREATE_CONTACT_TYPE,
                Permission::FUND_ACCOUNT_VALIDATION,
                Permission::VIEW_FUND_ACCOUNT_VALIDATION,
                Permission::UPDATE_FUND_ACCOUNT,
                Permission::VIEW_FUND_ACCOUNT,
                Permission::CREATE_FUND_ACCOUNT,
                Permission::CREATE_FUND_ACCOUNT_BULK,
                Permission::VALIDATE_FUND_ACCOUNT,
                Permission::CREATE_MERCHANT_KEY,
                Permission::VIEW_MERCHANT_KEY,
                Permission::VIEW_MERCHANT_ANALYTICS,
                Permission::VIEW_MERCHANT_BALANCE,
                Permission::VIEW_MERCHANT_INVOICE,
                Permission::UPDATE_USER_PROFILE,
                Permission::VIEW_USER,
                Permission::VIEW_MERCHANT_USER,
                Permission::UPDATE_WEBHOOK,
                Permission::VIEW_WEBHOOK,
                Permission::VIEW_WEBHOOK_EVENT,
                Permission::VIEW_WEBHOOK,
                Permission::CREATE_WEBHOOK,
                Permission::VIEW_REPORTING,
                Permission::CREATE_REPORTING,
                Permission::UPDATE_REPORTING,
                Permission::VIEW_TRANSACTION_STATEMENT,
                Permission::CREATE_INVITATION,
                Permission::VIEW_INVITATION,
                Permission::RESEND_INVITATION,
                Permission::UPDATE_INVITATION,
                Permission::DELETE_INVITATION,
                Permission::CREATE_SELF_SERVE_REPORT,
                Permission::GET_SELF_SERVE_REPORT,
                Permission::MERCHANT_PRODUCT_SWITCH,
                Permission::CREATE_USER_OTP,
                Permission::GENERATE_BANKING_ACCOUNT_STATEMENT,
                Permission::MERCHANT_INSTANT_ACTIVATION,
                Permission::UPDATE_MERCHANT_FEATURE,
                Permission::CREATE_BATCH,
                Permission::ASSIGN_MERCHANT_HANDLE,
                Permission::USER_PASSWORD_RESET,
                Permission::EDIT_MERCHANT_WEBSITE_DETAIL,
                Permission::UPDATE_PAYOUT,
                Permission::VIEW_WORKFLOW,
                Permission::UPDATE_TEST_MERCHANT_BALANCE,
                Permission::VIEW_VIRTUAL_ACCOUNT,
                Permission::UPLOAD_MERCHANT_DOCUMENT,
                Permission::DELETE_MERCHANT_DOCUMENT,
                Permission::UPDATE_MERCHANT_BANK_ACCOUNT_STATUS,
                Permission::UPDATE_MERCHANT_2FA_SETTING,
                Permission::VIEW_VENDOR_PAYMENTS,
                Permission::CREATE_VENDOR_PAYMENTS,
                Permission::ENABLE_EMAIL_IMPORT,
                Permission::EDIT_VENDOR_PAYMENTS,
                Permission::CANCEL_VENDOR_PAYMENTS,
                Permission::PROCESS_PAYOUT_SCHEDULED,

                Permission::CREATE_LOW_BALANCE_CONFIG,
                Permission::UPDATE_LOW_BALANCE_CONFIG,
                Permission::ENABLE_LOW_BALANCE_CONFIG,
                Permission::DISABLE_LOW_BALANCE_CONFIG,
                Permission::DELETE_LOW_BALANCE_CONFIG,

                Permission::CREATE_MERCHANT_NOTIFICATION_CONFIG,
                Permission::UPDATE_MERCHANT_NOTIFICATION_CONFIG,
                Permission::ENABLE_MERCHANT_NOTIFICATION_CONFIG,
                Permission::DISABLE_MERCHANT_NOTIFICATION_CONFIG,
                Permission::DELETE_MERCHANT_NOTIFICATION_CONFIG,

                Permission::PAY_TAX_PAYMENTS,
                Permission::CREATE_TAX_PAYMENTS,
                Permission::UPDATE_TAX_PAYMENT_SETTINGS,
                Permission::VIEW_TAX_PAYMENTS,
                Permission::MERCHANT_VIEW_FREE_PAYOUTS_ATTRIBUTES,
                Permission::UPDATE_BULK_PAYOUT_AMOUNT_TYPE,
                Permission::UPDATE_MERCHANT_PREFERENCE,
                Permission::VIEW_MERCHANT_PREFERENCE,

                Permission::CREATE_ACCOUNTING_INTEGRATION,
                Permission::DELETE_ACCOUNTING_INTEGRATION,
                Permission::SYNC_ACCOUNTING_INTEGRATION,
                Permission::VIEW_ACCOUNTING_INTEGRATION,
                Permission::WAITLIST_ACCOUNTING_INTEGRATION,

                Permission::MERCHANT_FETCH_SUB_VIRTUAL_ACCOUNT,
                Permission::MERCHANT_SUB_VIRTUAL_ACCOUNT_TRANSFER,

                Permission::COUPON_VALIDATE,
            ],

            AxisUserRole::CC_ADMIN => [
                Permission::CREATE_PAYOUT,
                Permission::CREATE_PAYOUT_BULK,
                Permission::APPROVE_PAYOUT_BULK,
                Permission::REJECT_PAYOUT_BULK,
                Permission::APPROVE_PAYOUT,
                Permission::REJECT_PAYOUT,
                Permission::VIEW_PAYOUT,
                Permission::CANCEL_PAYOUT,
                Permission::VIEW_PAYOUT_PURPOSE,
                Permission::CREATE_PAYOUT_PURPOSE,
                Permission::VIEW_PAYOUT_REVERSAL,
                Permission::PROCESS_PAYOUT_QUEUED,
                Permission::VIEW_PAYOUT_SUMMARY,
                Permission::VIEW_PAYOUT_WORKFLOW_SUMMARY,
                Permission::VIEW_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS_BATCH,
                Permission::CANCEL_PAYOUT_LINKS,
                Permission::ONBOARDING_PAYOUT_LINKS,
                Permission::SUMMARY_PAYOUT_LINKS,
                Permission::SETTINGS_PAYOUT_LINKS,
                Permission::DASHBOARD_PAYOUT_LINKS,
                Permission::RESEND_PAYOUT_LINKS,
                Permission::MERCHANT_CONFIG_LOGO,
                Permission::VIEW_CONTACT,
                Permission::CREATE_CONTACT,
                Permission::CREATE_CONTACT_BULK,
                Permission::UPDATE_CONTACT,
                Permission::DELETE_CONTACT,
                Permission::VIEW_CONTACT_TYPE,
                Permission::CREATE_CONTACT_TYPE,
                Permission::FUND_ACCOUNT_VALIDATION,
                Permission::VIEW_FUND_ACCOUNT_VALIDATION,
                Permission::VALIDATE_FUND_ACCOUNT,
                Permission::VIEW_FUND_ACCOUNT,
                Permission::UPDATE_FUND_ACCOUNT,
                Permission::CREATE_FUND_ACCOUNT,
                Permission::CREATE_FUND_ACCOUNT_BULK,
                Permission::VALIDATE_FUND_ACCOUNT,
                Permission::VIEW_MERCHANT_KEY,
                Permission::CREATE_MERCHANT_KEY,
                Permission::VIEW_MERCHANT_ANALYTICS,
                Permission::VIEW_MERCHANT_BALANCE,
                Permission::VIEW_MERCHANT_INVOICE,
                Permission::UPDATE_USER_PROFILE,
                Permission::VIEW_USER,
                Permission::VIEW_MERCHANT_USER,
                Permission::UPDATE_WEBHOOK,
                Permission::VIEW_WEBHOOK,
                Permission::VIEW_WEBHOOK_EVENT,
                Permission::VIEW_WEBHOOK,
                Permission::CREATE_WEBHOOK,
                Permission::VIEW_REPORTING,
                Permission::CREATE_REPORTING,
                Permission::VIEW_TRANSACTION_STATEMENT,
                Permission::CREATE_INVITATION,
                Permission::CREATE_SELF_SERVE_REPORT,
                Permission::GET_SELF_SERVE_REPORT,
                Permission::MERCHANT_PRODUCT_SWITCH,
                Permission::CREATE_USER_OTP,
                Permission::GENERATE_BANKING_ACCOUNT_STATEMENT,
                Permission::MERCHANT_INSTANT_ACTIVATION,
                Permission::CREATE_BATCH,
                Permission::EDIT_MERCHANT_WEBSITE_DETAIL,
                Permission::UPDATE_PAYOUT,
                Permission::VIEW_WORKFLOW,
                Permission::UPDATE_TEST_MERCHANT_BALANCE,
                Permission::UPDATE_MERCHANT_FEATURE,
                Permission::VIEW_VIRTUAL_ACCOUNT,
                Permission::UPLOAD_MERCHANT_DOCUMENT,
                Permission::DELETE_MERCHANT_DOCUMENT,
                Permission::UPDATE_MERCHANT_BANK_ACCOUNT_STATUS,
                Permission::VIEW_VENDOR_PAYMENTS,
                Permission::CREATE_VENDOR_PAYMENTS,
                Permission::ENABLE_EMAIL_IMPORT,
                Permission::EDIT_VENDOR_PAYMENTS,
                Permission::CANCEL_VENDOR_PAYMENTS,
                Permission::PROCESS_PAYOUT_SCHEDULED,

                Permission::CREATE_ACCOUNTING_INTEGRATION,
                Permission::DELETE_ACCOUNTING_INTEGRATION,
                Permission::SYNC_ACCOUNTING_INTEGRATION,
                Permission::VIEW_ACCOUNTING_INTEGRATION,
                Permission::WAITLIST_ACCOUNTING_INTEGRATION,

                Permission::PAY_TAX_PAYMENTS,
                Permission::CREATE_TAX_PAYMENTS,
                Permission::UPDATE_TAX_PAYMENT_SETTINGS,
                Permission::VIEW_TAX_PAYMENTS,
                Permission::MERCHANT_VIEW_FREE_PAYOUTS_ATTRIBUTES,
                Permission::UPDATE_MERCHANT_PREFERENCE,
                Permission::VIEW_MERCHANT_PREFERENCE,

                Permission::MERCHANT_FETCH_SUB_VIRTUAL_ACCOUNT,
                Permission::MERCHANT_SUB_VIRTUAL_ACCOUNT_TRANSFER,
            ],

            AxisUserRole::CHECKER_L1 => [
                Permission::CREATE_PAYOUT,
                Permission::CREATE_PAYOUT_BULK,
                Permission::APPROVE_PAYOUT_BULK,
                Permission::REJECT_PAYOUT_BULK,
                Permission::APPROVE_PAYOUT,
                Permission::REJECT_PAYOUT,
                Permission::VIEW_PAYOUT,
                Permission::CANCEL_PAYOUT,
                Permission::VIEW_PAYOUT_PURPOSE,
                Permission::CREATE_PAYOUT_PURPOSE,
                Permission::VIEW_PAYOUT_REVERSAL,
                Permission::PROCESS_PAYOUT_QUEUED,
                Permission::VIEW_PAYOUT_SUMMARY,
                Permission::VIEW_PAYOUT_WORKFLOW_SUMMARY,
                Permission::VIEW_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS_BATCH,
                Permission::CANCEL_PAYOUT_LINKS,
                Permission::ONBOARDING_PAYOUT_LINKS,
                Permission::SUMMARY_PAYOUT_LINKS,
                Permission::DASHBOARD_PAYOUT_LINKS,
                Permission::RESEND_PAYOUT_LINKS,
                Permission::MERCHANT_CONFIG_LOGO,
                Permission::VIEW_CONTACT,
                Permission::CREATE_CONTACT,
                Permission::CREATE_CONTACT_BULK,
                Permission::UPDATE_CONTACT,
                Permission::DELETE_CONTACT,
                Permission::VIEW_CONTACT_TYPE,
                Permission::CREATE_CONTACT_TYPE,
                Permission::FUND_ACCOUNT_VALIDATION,
                Permission::VIEW_FUND_ACCOUNT_VALIDATION,
                Permission::VALIDATE_FUND_ACCOUNT,
                Permission::VIEW_FUND_ACCOUNT,
                Permission::UPDATE_FUND_ACCOUNT,
                Permission::CREATE_FUND_ACCOUNT,
                Permission::CREATE_FUND_ACCOUNT_BULK,
                Permission::VALIDATE_FUND_ACCOUNT,
                Permission::VIEW_MERCHANT_ANALYTICS,
                Permission::VIEW_MERCHANT_BALANCE,
                Permission::UPDATE_USER_PROFILE,
                Permission::VIEW_USER,
                Permission::VIEW_MERCHANT_USER,
                Permission::UPDATE_WEBHOOK,
                Permission::VIEW_WEBHOOK,
                Permission::VIEW_WEBHOOK_EVENT,
                Permission::VIEW_WEBHOOK,
                Permission::CREATE_WEBHOOK,
                Permission::VIEW_REPORTING,
                Permission::CREATE_REPORTING,
                Permission::VIEW_TRANSACTION_STATEMENT,
                Permission::CREATE_SELF_SERVE_REPORT,
                Permission::GET_SELF_SERVE_REPORT,
                Permission::MERCHANT_PRODUCT_SWITCH,
                Permission::CREATE_USER_OTP,
                Permission::GENERATE_BANKING_ACCOUNT_STATEMENT,
                Permission::MERCHANT_INSTANT_ACTIVATION,
                Permission::CREATE_BATCH,
                Permission::UPDATE_PAYOUT,
                Permission::VIEW_WORKFLOW,
                Permission::UPDATE_TEST_MERCHANT_BALANCE,
                Permission::VIEW_VIRTUAL_ACCOUNT,
                Permission::VIEW_VENDOR_PAYMENTS,
                Permission::CREATE_VENDOR_PAYMENTS,
                Permission::ENABLE_EMAIL_IMPORT,
                Permission::EDIT_VENDOR_PAYMENTS,
                Permission::CANCEL_VENDOR_PAYMENTS,
                Permission::PROCESS_PAYOUT_SCHEDULED,

                Permission::PAY_TAX_PAYMENTS,
                Permission::CREATE_TAX_PAYMENTS,
                Permission::VIEW_TAX_PAYMENTS,

                Permission::SYNC_ACCOUNTING_INTEGRATION,
                Permission::VIEW_ACCOUNTING_INTEGRATION,
                Permission::WAITLIST_ACCOUNTING_INTEGRATION,
            ],

            AxisUserRole::MAKER => [
                Permission::CREATE_USER_OTP,
                Permission::UPDATE_USER_PROFILE,
                Permission::VIEW_USER,
                Permission::USER_PASSWORD_RESET,
                Permission::VIEW_PAYOUT,
                Permission::VIEW_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS,
                Permission::CREATE_PAYOUT_LINKS_BATCH,
                Permission::CANCEL_PAYOUT_LINKS,
                Permission::ONBOARDING_PAYOUT_LINKS,
                Permission::SUMMARY_PAYOUT_LINKS,
                Permission::DASHBOARD_PAYOUT_LINKS,
                Permission::RESEND_PAYOUT_LINKS,
                Permission::MERCHANT_CONFIG_LOGO,
                /*
                 * Following are the new permissions added for Payout-links
                 */
                Permission::VIEW_PAYOUT_PURPOSE,
                Permission::VIEW_FUND_ACCOUNT,
                Permission::VIEW_MERCHANT_BALANCE,
                Permission::VIEW_PAYOUT_SUMMARY,
                Permission::VIEW_MERCHANT_ANALYTICS,
                Permission::VIEW_CONTACT_TYPE,
                Permission::VIEW_CONTACT,
                Permission::CREATE_CONTACT,
                Permission::UPDATE_CONTACT,
                Permission::DELETE_CONTACT,
                Permission::CREATE_CONTACT_TYPE,
                Permission::VIEW_VIRTUAL_ACCOUNT,
                /*
                 * Following are the new permissions added for Vendor-Payments micro-service
                 */
                Permission::VIEW_VENDOR_PAYMENTS,
                Permission::CREATE_VENDOR_PAYMENTS,
                Permission::ENABLE_EMAIL_IMPORT,
                Permission::EDIT_VENDOR_PAYMENTS,
                Permission::CANCEL_VENDOR_PAYMENTS,

                Permission::VIEW_TAX_PAYMENTS,
                Permission::CREATE_TAX_PAYMENTS,

                Permission::SYNC_ACCOUNTING_INTEGRATION,
                Permission::VIEW_ACCOUNTING_INTEGRATION,
                Permission::WAITLIST_ACCOUNTING_INTEGRATION,
                Permission::VIEW_PAYOUT_WORKFLOW_SUMMARY,
            ],

            AxisUserRole::VIEWER => [
                Permission::VIEW_PAYOUT,
                Permission::VIEW_PAYOUT_PURPOSE,
                Permission::VIEW_PAYOUT_REVERSAL,
                Permission::VIEW_PAYOUT_SUMMARY,
                Permission::VIEW_PAYOUT_LINKS,
                Permission::VIEW_PAYOUT_WORKFLOW_SUMMARY,
                Permission::VIEW_CONTACT,
                Permission::VIEW_CONTACT_TYPE,
                Permission::VIEW_FUND_ACCOUNT_VALIDATION,
                Permission::VIEW_FUND_ACCOUNT,
                Permission::VIEW_MERCHANT_KEY,
                Permission::VIEW_MERCHANT_ANALYTICS,
                Permission::VIEW_MERCHANT_BALANCE,
                Permission::VIEW_MERCHANT_INVOICE,
                Permission::VIEW_USER,
                Permission::VIEW_MERCHANT_USER,
                Permission::VIEW_WEBHOOK,
                Permission::VIEW_WEBHOOK_EVENT,
                Permission::VIEW_REPORTING,
                Permission::CREATE_REPORTING,
                Permission::GENERATE_BANKING_ACCOUNT_STATEMENT,
                Permission::VIEW_TRANSACTION_STATEMENT,
                Permission::GET_SELF_SERVE_REPORT,
                Permission::MERCHANT_PRODUCT_SWITCH,
                Permission::UPDATE_USER_PROFILE,
                Permission::ONBOARDING_PAYOUT_LINKS,
                Permission::SUMMARY_PAYOUT_LINKS,
                Permission::VIEW_VENDOR_PAYMENTS,
                Permission::VIEW_TAX_PAYMENTS,
                Permission::VIEW_ACCOUNTING_INTEGRATION,
                Permission::WAITLIST_ACCOUNTING_INTEGRATION,
            ],
        ];

        $rolePermissions[AxisUserRole::CHECKER_L2] = $rolePermissions[AxisUserRole::CHECKER_L1];
        $rolePermissions[AxisUserRole::CHECKER_L3] = $rolePermissions[AxisUserRole::CHECKER_L1];

        self::$rolePermissions = $rolePermissions;
    }

    private static function getRolePermissionMap()
    {
        if (empty(self::$rolePermissions) === true)
        {
            self::init();
        }

        return self::$rolePermissions;
    }

    public static function isValidRolePermission(string $role, string $permission) : bool
    {
        $rolePermissions = self::getRolePermissions($role);

        if (in_array($permission, $rolePermissions, true))
        {
            return true;
        }

        return false;
    }

    public static function isInvalidRolePermission(string $role, string $permission) : bool
    {
        if (self::isValidRolePermission($role, $permission))
        {
            return false;
        }

        return true;
    }

    public static function getRolePermissions(string $role)
    {
        return self::getRolePermissionMap()[$role] ?? [];
    }
}
