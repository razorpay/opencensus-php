<?php

namespace RZP\Http;

use RZP\Models\User\BankingRole;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\SubVirtualAccount\Constants as SubVaConstants;

class UserRolePermissionsMap
{
    private static $rolePermissions;

    private static function init()
    {
         $rolePermissions = [
             BankingRole::OWNER => [
                 Permission::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::CREATE_FINANCEX_REPORT,
                 Permission::VIEW_FINANCEX_REPORT,
                 Permission::CREATE_PAYOUT,
                 Permission::CREATE_PAYOUT_BULK,
                 Permission::APPROVE_PAYOUT_BULK,
                 Permission::REJECT_PAYOUT_BULK,
                 Permission::APPROVE_PAYOUT,
                 Permission::REJECT_PAYOUT,
                 Permission::VIEW_PAYOUT,
                 Permission::UPDATE_PAYOUT_ATTACHMENT,
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
                 Permission::APPROVE_PAYOUT_LINKS,
                 Permission::REJECT_PAYOUT_LINKS,
                 Permission::BULK_APPROVE_PAYOUT_LINKS,
                 Permission::BULK_REJECT_PAYOUT_LINKS,
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
                 Permission::MERCHANT_IP_WHITELIST,
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
                 Permission::VIEW_BUSINESS_REPORTS,
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
                 Permission::VIEW_TDS_CATEGORIES,
                 Permission::GET_SIGNED_URL,
                 Permission::GENERATE_VP_INVOICE_ZIP,
                 Permission::CREATE_VENDOR_PAYMENTS,
                 Permission::CREATE_VENDOR_PAYMENTS_EMAIL,
                 Permission::ENABLE_EMAIL_IMPORT,
                 Permission::INVITE_VENDOR,
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
                 Permission::GENERATE_TDS_CHALLAN_ZIP,
                 Permission::UPDATE_TAX_PAYMENT_SETTINGS,
                 Permission::UPDATE_TAX_PAYMENT_SETTINGS_AUTO,
                 Permission::VIEW_TAX_PAYMENTS,
                 Permission::VIEW_TAX_PAYMENT_SETTINGS,
                 Permission::MERCHANT_VIEW_FREE_PAYOUTS_ATTRIBUTES,
                 Permission::UPDATE_BULK_PAYOUT_AMOUNT_TYPE,
                 Permission::UPDATE_MERCHANT_PREFERENCE,
                 Permission::VIEW_MERCHANT_PREFERENCE,
                 Permission::UPDATE_MERCHANT_DETAILS,

                 Permission::CREATE_ACCOUNTING_INTEGRATION,
                 Permission::DELETE_ACCOUNTING_INTEGRATION,
                 Permission::SYNC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_ACCOUNTING_INTEGRATION,
                 Permission::WAITLIST_ACCOUNTING_INTEGRATION,
                 Permission::UPDATE_ACCOUNTING_INTEGRATION,

                 Permission::MERCHANT_FETCH_SUB_VIRTUAL_ACCOUNT,
                 Permission::MERCHANT_SUB_VIRTUAL_ACCOUNT_TRANSFER,

                 Permission::COUPON_VALIDATE,

                 Permission::VIEW_TAX_STATES,

                 Permission::ADD_MERCHANT_EMAIL,
                 Permission::MERCHANT_GET_OAUTH_TOKEN,
                 Permission::UPDATE_USER_ROLE,

                 // Download payout attachments
                 Permission::DOWNLOAD_PAYOUT_ATTACHMENTS,

                 Permission::UPDATE_USER_ROLE,
                 Permission::VIEW_PUBLIC_PROFILE,
                 Permission::VIEW_MANAGE_TEAM,
                 Permission::VIEW_DEVELOPER_CONTROLS,
                 Permission::VIEW_BANKING,
                 Permission::CREATE_BANKING,
                 Permission::VIEW_BILLING,
                 Permission::UPDATE_SECURITY_SETTINGS,
                 Permission::UPDATE_BUSINESS_SETTINGS,
                 Permission::EDIT_WORKFLOW,
                 Permission::INTEGRATE_SHOPIFY_TOOL,
                 Permission::VIEW_KYC,
                 Permission::FILL_KYC,
                 Permission::DOWNLOAD_REPORTING,
                 Permission::CREATE_LOW_BALANCE_ALERTS,
                 Permission::VIEW_PAYOUTS_REPORT,
                 Permission::HAS_APP_ACCESS,

                 //self serve workflow
                 Permission::SELF_SERVE_WORKFLOW_CONFIG,

                 Permission::VIEW_ROLE,
                 Permission::CREATE_ROLE,
                 Permission::DELETE_ROLE,
                 Permission::EDIT_ROLE,
                 Permission::VIEW_PRIVILEGES,
                 Permission::VIEW_ALL_ROLES,
                 Permission::CUSTOMER_SUPPORT_FULL_ACCESS,

                 Permission::ACCOUNTS_RECEIVABLE_ADMIN,
                 Permission::BILL_PAYMENTS_VIEW,
                 Permission::BILL_PAYMENTS_CREATE_ACCOUNT,
                 Permission::BILL_PAYMENTS_FETCH_BILL,

                 Permission::MOB_SERVICE_READ,
                 Permission::MOB_SERVICE_WRITE,

                 Permission::BANKING_ACCOUNT_READ,
                 Permission::BANKING_ACCOUNT_WRITE,

                 Permission::MERCHANT_ONBOARDING_WRITE,

                 Permission::MERCHANT_USER_WRITE,

                 Permission::MERCHANT_ONBOARDING,
             ],

             BankingRole::ADMIN => [
                 Permission::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::CREATE_FINANCEX_REPORT,
                 Permission::VIEW_FINANCEX_REPORT,
                 Permission::CREATE_PAYOUT,
                 Permission::CREATE_PAYOUT_BULK,
                 Permission::APPROVE_PAYOUT_BULK,
                 Permission::REJECT_PAYOUT_BULK,
                 Permission::APPROVE_PAYOUT,
                 Permission::REJECT_PAYOUT,
                 Permission::VIEW_PAYOUT,
                 Permission::UPDATE_PAYOUT_ATTACHMENT,
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
                 Permission::APPROVE_PAYOUT_LINKS,
                 Permission::REJECT_PAYOUT_LINKS,
                 Permission::BULK_APPROVE_PAYOUT_LINKS,
                 Permission::BULK_REJECT_PAYOUT_LINKS,
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
                 Permission::MERCHANT_IP_WHITELIST,
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
                 Permission::VIEW_BUSINESS_REPORTS,
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
                 Permission::VIEW_TDS_CATEGORIES,
                 Permission::GET_SIGNED_URL,
                 Permission::GENERATE_VP_INVOICE_ZIP,
                 Permission::CREATE_VENDOR_PAYMENTS,
                 Permission::CREATE_VENDOR_PAYMENTS_EMAIL,
                 Permission::ENABLE_EMAIL_IMPORT,
                 Permission::INVITE_VENDOR,
                 Permission::EDIT_VENDOR_PAYMENTS,
                 Permission::CANCEL_VENDOR_PAYMENTS,
                 Permission::PROCESS_PAYOUT_SCHEDULED,

                 Permission::CREATE_ACCOUNTING_INTEGRATION,
                 Permission::DELETE_ACCOUNTING_INTEGRATION,
                 Permission::SYNC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_ACCOUNTING_INTEGRATION,
                 Permission::WAITLIST_ACCOUNTING_INTEGRATION,
                 Permission::UPDATE_ACCOUNTING_INTEGRATION,

                 Permission::PAY_TAX_PAYMENTS,
                 Permission::CREATE_TAX_PAYMENTS,
                 Permission::GENERATE_TDS_CHALLAN_ZIP,
                 Permission::UPDATE_TAX_PAYMENT_SETTINGS,
                 Permission::UPDATE_TAX_PAYMENT_SETTINGS_AUTO,
                 Permission::VIEW_TAX_PAYMENTS,
                 Permission::VIEW_TAX_PAYMENT_SETTINGS,
                 Permission::MERCHANT_VIEW_FREE_PAYOUTS_ATTRIBUTES,
                 Permission::UPDATE_MERCHANT_PREFERENCE,
                 Permission::VIEW_MERCHANT_PREFERENCE,

                 Permission::VIEW_TAX_STATES,

                 Permission::MERCHANT_FETCH_SUB_VIRTUAL_ACCOUNT,
                 Permission::MERCHANT_SUB_VIRTUAL_ACCOUNT_TRANSFER,
                 // Download payout attachments
                 Permission::DOWNLOAD_PAYOUT_ATTACHMENTS,
                 Permission::UPDATE_MERCHANT_DETAILS,

                 Permission::VIEW_PUBLIC_PROFILE,
                 Permission::VIEW_MANAGE_TEAM,
                 Permission::VIEW_DEVELOPER_CONTROLS,
                 Permission::VIEW_BANKING,
                 Permission::CREATE_BANKING,
                 Permission::VIEW_BILLING,
                 Permission::UPDATE_SECURITY_SETTINGS,
                 Permission::EDIT_WORKFLOW,
                 Permission::INTEGRATE_SHOPIFY_TOOL,
                 Permission::VIEW_KYC,
                 Permission::FILL_KYC,
                 Permission::DOWNLOAD_REPORTING,
                 Permission::HAS_APP_ACCESS,
                 Permission::VIEW_PRIVILEGES,
                 Permission::VIEW_ROLE,
                 Permission::VIEW_ALL_ROLES,
                 Permission::CUSTOMER_SUPPORT_FULL_ACCESS,

                 Permission::ACCOUNTS_RECEIVABLE_ADMIN,
                 Permission::BILL_PAYMENTS_VIEW,
                 Permission::BILL_PAYMENTS_CREATE_ACCOUNT,
                 Permission::BILL_PAYMENTS_FETCH_BILL,


                 Permission::MOB_SERVICE_READ,
                 Permission::MOB_SERVICE_WRITE,

                 Permission::BANKING_ACCOUNT_READ,
                 Permission::BANKING_ACCOUNT_WRITE,

                 Permission::MERCHANT_ONBOARDING_WRITE,

                 Permission::MERCHANT_USER_WRITE,

                 Permission::MERCHANT_ONBOARDING,
             ],

             BankingRole::FINANCE_L1 => [
                 Permission::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::CREATE_PAYOUT,
                 Permission::CREATE_PAYOUT_BULK,
                 Permission::APPROVE_PAYOUT_BULK,
                 Permission::REJECT_PAYOUT_BULK,
                 Permission::APPROVE_PAYOUT,
                 Permission::REJECT_PAYOUT,
                 Permission::VIEW_PAYOUT,
                 Permission::UPDATE_PAYOUT_ATTACHMENT,
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
                 Permission::APPROVE_PAYOUT_LINKS,
                 Permission::REJECT_PAYOUT_LINKS,
                 Permission::BULK_APPROVE_PAYOUT_LINKS,
                 Permission::BULK_REJECT_PAYOUT_LINKS,
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
                 Permission::VIEW_BUSINESS_REPORTS,
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
                 Permission::VIEW_TDS_CATEGORIES,
                 Permission::GET_SIGNED_URL,
                 Permission::GENERATE_VP_INVOICE_ZIP,
                 Permission::CREATE_VENDOR_PAYMENTS,
                 Permission::CREATE_VENDOR_PAYMENTS_EMAIL,
                 Permission::ENABLE_EMAIL_IMPORT,
                 Permission::INVITE_VENDOR,
                 Permission::EDIT_VENDOR_PAYMENTS,
                 Permission::CANCEL_VENDOR_PAYMENTS,
                 Permission::PROCESS_PAYOUT_SCHEDULED,
                 Permission::UPDATE_TAX_PAYMENT_SETTINGS,

                 Permission::PAY_TAX_PAYMENTS,
                 Permission::CREATE_TAX_PAYMENTS,
                 Permission::GENERATE_TDS_CHALLAN_ZIP,
                 Permission::VIEW_TAX_PAYMENTS,
                 Permission::VIEW_TAX_PAYMENT_SETTINGS,

                 Permission::CREATE_ACCOUNTING_INTEGRATION,
                 Permission::SYNC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_ACCOUNTING_INTEGRATION,
                 Permission::WAITLIST_ACCOUNTING_INTEGRATION,
                 Permission::UPDATE_ACCOUNTING_INTEGRATION,

                 Permission::VIEW_TAX_STATES,

                 Permission::CREATE_INVITATION,
                 Permission::VIEW_MERCHANT_PREFERENCE,
                 // Download payout attachments
                 Permission::DOWNLOAD_PAYOUT_ATTACHMENTS,
                 Permission::UPDATE_MERCHANT_DETAILS,

                 Permission::VIEW_MANAGE_TEAM,
                 Permission::VIEW_BANKING,
                 Permission::CREATE_BANKING,
                 Permission::VIEW_KYC,
                 Permission::FILL_KYC,
                 Permission::DOWNLOAD_REPORTING,
                 Permission::HAS_APP_ACCESS,
                 Permission::VIEW_PRIVILEGES,
                 Permission::VIEW_ROLE,
                 Permission::VIEW_ALL_ROLES,
                 Permission::CUSTOMER_SUPPORT_FULL_ACCESS,

                 Permission::ACCOUNTS_RECEIVABLE_ADMIN,
                 Permission::BILL_PAYMENTS_VIEW,
                 Permission::BILL_PAYMENTS_CREATE_ACCOUNT,
                 Permission::BILL_PAYMENTS_FETCH_BILL,

                 Permission::MOB_SERVICE_READ,
                 Permission::MOB_SERVICE_WRITE,

                 Permission::BANKING_ACCOUNT_READ,
                 Permission::BANKING_ACCOUNT_WRITE,

                 Permission::MERCHANT_ONBOARDING_WRITE,

                 Permission::MERCHANT_USER_WRITE,

                 Permission::MERCHANT_ONBOARDING,
             ],

             BankingRole::OPERATIONS => [
                 Permission::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::CREATE_USER_OTP,
                 Permission::UPDATE_USER_PROFILE,
                 Permission::VIEW_USER,
                 Permission::USER_PASSWORD_RESET,
                 Permission::VIEW_PAYOUT,
                 Permission::UPDATE_PAYOUT_ATTACHMENT,
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
                 Permission::VIEW_TDS_CATEGORIES,
                 Permission::GET_SIGNED_URL,
                 Permission::GENERATE_VP_INVOICE_ZIP,
                 Permission::CREATE_VENDOR_PAYMENTS_EMAIL,
                 Permission::ENABLE_EMAIL_IMPORT,
                 Permission::INVITE_VENDOR,

                 Permission::VIEW_TAX_STATES,

                 Permission::VIEW_TAX_PAYMENTS,
                 Permission::VIEW_TAX_PAYMENT_SETTINGS,
                 Permission::GENERATE_TDS_CHALLAN_ZIP,
                 Permission::SYNC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_ACCOUNTING_INTEGRATION,
                 Permission::WAITLIST_ACCOUNTING_INTEGRATION,
                 Permission::UPDATE_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_PAYOUT_WORKFLOW_SUMMARY,
                 Permission::VIEW_MERCHANT_PREFERENCE,

                 // Download payout attachments
                 Permission::DOWNLOAD_PAYOUT_ATTACHMENTS,

                 Permission::VIEW_BANKING,
                 Permission::VIEW_WORKFLOW,
                 Permission::VIEW_KYC,
                 Permission::FILL_KYC,
                 Permission::HAS_APP_ACCESS,
                 Permission::VIEW_PRIVILEGES,
                 Permission::VIEW_ALL_ROLES,
                 Permission::CUSTOMER_SUPPORT_FULL_ACCESS,

                 Permission::ACCOUNTS_RECEIVABLE_ADMIN,
                 Permission::BILL_PAYMENTS_VIEW,
                 Permission::BILL_PAYMENTS_CREATE_ACCOUNT,
                 Permission::BILL_PAYMENTS_FETCH_BILL,

                 Permission::MOB_SERVICE_READ,
                 Permission::MOB_SERVICE_WRITE,

                 Permission::BANKING_ACCOUNT_READ,
                 Permission::BANKING_ACCOUNT_WRITE,

                 Permission::MERCHANT_ONBOARDING_WRITE,

                 Permission::MERCHANT_USER_WRITE,

                 Permission::MERCHANT_ONBOARDING,
             ],

             BankingRole::CHARTERED_ACCOUNTANT => [
                 Permission::CREATE_GENERIC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_GENERIC_ACCOUNTING_INTEGRATION,

                 //Miscellaneous API's needed for any user
                 Permission::VIEW_MERCHANT_ANALYTICS,
                 Permission::VIEW_MERCHANT_USER,
                 Permission::UPDATE_USER_PROFILE,

                 //BankingRoute permissions for reporting API's
                 Permission::GET_SELF_SERVE_REPORT,
                 Permission::VIEW_REPORTING,
                 Permission::VIEW_BUSINESS_REPORTS,
                 Permission::CREATE_REPORTING,
                 Permission::DOWNLOAD_REPORTING,

                 //BankingRoute permissions for VP Zip download API's
                 Permission::GENERATE_VP_INVOICE_ZIP,
                 Permission::GENERATE_TDS_CHALLAN_ZIP,
                 Permission::GET_SIGNED_URL,

                 //View tax payments
                 Permission::VIEW_TAX_PAYMENTS,
                 Permission::VIEW_TAX_PAYMENT_SETTINGS,
                 Permission::VIEW_TRANSACTION_STATEMENT,

                 // Download payout attachments
                 Permission::DOWNLOAD_PAYOUT_ATTACHMENTS,

                 Permission::VIEW_PAYOUTS_REPORT,
                 Permission::HAS_APP_ACCESS,
                 Permission::VIEW_PRIVILEGES,
                 Permission::VIEW_ALL_ROLES,
                 Permission::CUSTOMER_SUPPORT_FULL_ACCESS,

                 Permission::CREATE_ACCOUNTING_INTEGRATION,
                 Permission::DELETE_ACCOUNTING_INTEGRATION,
                 Permission::SYNC_ACCOUNTING_INTEGRATION,
                 Permission::VIEW_ACCOUNTING_INTEGRATION,
                 Permission::UPDATE_ACCOUNTING_INTEGRATION,

                 Permission::MOB_SERVICE_READ,
                 Permission::MOB_SERVICE_WRITE,

                 Permission::BANKING_ACCOUNT_READ,
                 Permission::BANKING_ACCOUNT_WRITE,

                 Permission::MERCHANT_ONBOARDING_WRITE,

                 Permission::MERCHANT_USER_WRITE,

                 Permission::MERCHANT_ONBOARDING,
             ],

             BankingRole::VIEW_ONLY => [
                 Permission::VIEW_GENERIC_ACCOUNTING_INTEGRATION,
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
                 Permission::VIEW_BANKING,
                 Permission::VIEW_MERCHANT_USER,
                 Permission::VIEW_WEBHOOK,
                 Permission::VIEW_WEBHOOK_EVENT,
                 Permission::VIEW_REPORTING,
                 Permission::VIEW_BUSINESS_REPORTS,
                 Permission::CREATE_REPORTING,
                 Permission::GENERATE_BANKING_ACCOUNT_STATEMENT,
                 Permission::VIEW_TRANSACTION_STATEMENT,
                 Permission::GET_SELF_SERVE_REPORT,
                 Permission::MERCHANT_PRODUCT_SWITCH,
                 Permission::UPDATE_USER_PROFILE,
                 Permission::ONBOARDING_PAYOUT_LINKS,
                 Permission::SUMMARY_PAYOUT_LINKS,
                 Permission::VIEW_VENDOR_PAYMENTS,
                 Permission::VIEW_TDS_CATEGORIES,
                 Permission::GET_SIGNED_URL,
                 Permission::GENERATE_VP_INVOICE_ZIP,
                 Permission::VIEW_TAX_PAYMENTS,
                 Permission::VIEW_TAX_PAYMENT_SETTINGS,
                 Permission::GENERATE_TDS_CHALLAN_ZIP,
                 Permission::VIEW_ACCOUNTING_INTEGRATION,
                 Permission::WAITLIST_ACCOUNTING_INTEGRATION,
                 Permission::CREATE_USER_OTP,
                 Permission::VIEW_TAX_STATES,
                 Permission::VIEW_MERCHANT_PREFERENCE,
                 // Download payout attachments
                 Permission::DOWNLOAD_PAYOUT_ATTACHMENTS,

                 Permission::HAS_APP_ACCESS,
                 Permission::DOWNLOAD_REPORTING,
                 Permission::VIEW_PRIVILEGES,
                 Permission::VIEW_ALL_ROLES,
                 Permission::CUSTOMER_SUPPORT_FULL_ACCESS,

                 Permission::BILL_PAYMENTS_VIEW,

                 Permission::MOB_SERVICE_READ,

                 Permission::BANKING_ACCOUNT_READ,

                 Permission::MERCHANT_USER_WRITE,

                 Permission::MERCHANT_ONBOARDING,
             ],

             BankingRole::VENDOR => [
                 Permission::VENDOR_PORTAL_PERMISSION,

                 //API's needed to fetch and edit self user details
                 Permission::VIEW_USER,
                 Permission::UPDATE_USER_PROFILE,
                 Permission::HAS_APP_ACCESS,
                 Permission::DOWNLOAD_REPORTING,
                 Permission::VIEW_PRIVILEGES,
                 Permission::VIEW_ALL_ROLES,
                 Permission::CUSTOMER_SUPPORT_FULL_ACCESS,

                 Permission::MERCHANT_USER_WRITE,

                 Permission::MERCHANT_ONBOARDING,
             ],
        ];

        $rolePermissions[BankingRole::FINANCE_L2] = $rolePermissions[BankingRole::FINANCE_L1];
        $rolePermissions[BankingRole::FINANCE_L3] = $rolePermissions[BankingRole::FINANCE_L1];

        self::$rolePermissions = $rolePermissions;
    }

    /** @var array Permissions which are restricted for sub merchants on account-sub-account flow */
    public static $restrictedPermissions = [
        SubVaConstants::ACCOUNT_SUB_ACCOUNT => [
            SubVaConstants::SUB_MERCHANT => [
                Permission::PAY_TAX_PAYMENTS,
                Permission::CREATE_TAX_PAYMENTS,
                Permission::GENERATE_TDS_CHALLAN_ZIP,
                Permission::UPDATE_TAX_PAYMENT_SETTINGS,
                Permission::UPDATE_TAX_PAYMENT_SETTINGS_AUTO,
                Permission::VIEW_TAX_PAYMENTS,
                Permission::VIEW_TAX_PAYMENT_SETTINGS,
                Permission::VIEW_TAX_STATES,
                Permission::CREATE_VENDOR_PAYMENTS,
                Permission::EDIT_VENDOR_PAYMENTS,
                Permission::CANCEL_VENDOR_PAYMENTS,
                Permission::VIEW_VENDOR_PAYMENTS,
                Permission::VIEW_FINANCEX_REPORT,
                Permission::CREATE_FINANCEX_REPORT,
            ]
        ],
    ];

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
