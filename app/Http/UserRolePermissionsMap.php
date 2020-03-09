<?php

namespace RZP\Http;

use RZP\Models\User\BankingRole;
use RZP\Models\Admin\Permission\Name as Permission;

class UserRolePermissionsMap
{
    private static $rolePermissions;

    private static function init()
    {
         $rolePermissions = [
             BankingRole::OWNER => [
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
                 Permission::RETRY_FUND_ACCOUNT_VALIDATION,
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
             ],

             BankingRole::ADMIN => [
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
                 Permission::RETRY_FUND_ACCOUNT_VALIDATION,
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
             ],

             BankingRole::FINANCE_L1 => [
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
                 Permission::RETRY_FUND_ACCOUNT_VALIDATION,
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
             ],

             BankingRole::OPERATIONS => [
                 Permission::CREATE_USER_OTP,
                 Permission::UPDATE_USER_PROFILE,
                 Permission::VIEW_USER,
                 Permission::USER_PASSWORD_RESET,
                 Permission::VIEW_REPORTING,
                 Permission::VIEW_PAYOUT,
                 Permission::VIEW_PAYOUT_LINKS,
                 Permission::CREATE_PAYOUT_LINKS,
                 Permission::CANCEL_PAYOUT_LINKS,
                 Permission::ONBOARDING_PAYOUT_LINKS,
                 Permission::SUMMARY_PAYOUT_LINKS,
                 Permission::SETTINGS_PAYOUT_LINKS,
                 Permission::DASHBOARD_PAYOUT_LINKS,
                 Permission::RESEND_PAYOUT_LINKS,
                 Permission::MERCHANT_CONFIG_LOGO,
             ],

             BankingRole::VIEW_ONLY => [
                 Permission::VIEW_PAYOUT,
                 Permission::VIEW_PAYOUT_PURPOSE,
                 Permission::VIEW_PAYOUT_REVERSAL,
                 Permission::VIEW_PAYOUT_SUMMARY,
                 Permission::VIEW_PAYOUT_LINKS,
                 Permission::VIEW_PAYOUT_WORKFLOW_SUMMARY,
                 Permission::VIEW_PAYOUT_LINKS,
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
                 Permission::VIEW_TRANSACTION_STATEMENT,
                 Permission::GET_SELF_SERVE_REPORT,
                 Permission::MERCHANT_PRODUCT_SWITCH,
             ],
        ];

        $rolePermissions[BankingRole::FINANCE_L2] = $rolePermissions[BankingRole::FINANCE_L1];
        $rolePermissions[BankingRole::FINANCE_L3] = $rolePermissions[BankingRole::FINANCE_L1];

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
