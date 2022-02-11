<?php

namespace RZP\Http\Response;

use RZP\Models\User\Role;

class ActivationDetailsResponse implements UserRoleBasedResponse
{
    private const     CONTACT_NAME                             = 'contact_name';
    private const     CONTACT_EMAIL                            = 'contact_email';
    private const     CONTACT_MOBILE                           = 'contact_mobile';
    private const     CONTACT_LANDLINE                         = 'contact_landline';
    private const     BUSINESS_TYPE                            = 'business_type';
    private const     BUSINESS_NAME                            = 'business_name';
    private const     BUSINESS_DESCRIPTION                     = 'business_description';
    private const     BUSINESS_DBA                             = 'business_dba';
    private const     BUSINESS_WEBSITE                         = 'business_website';
    private const     BUSINESS_INTERNATIONAL                   = 'business_international';
    private const     BUSINESS_PAYMENTDETAILS                  = 'business_paymentdetails';
    private const     BUSINESS_REGISTERED_ADDRESS              = 'business_registered_address';
    private const     BUSINESS_REGISTERED_ADDRESS_L2           = 'business_registered_address_l2';
    private const     BUSINESS_REGISTERED_COUNTRY              = 'business_registered_country';
    private const     BUSINESS_REGISTERED_STATE                = 'business_registered_state';
    private const     BUSINESS_REGISTERED_CITY                 = 'business_registered_city';
    private const     BUSINESS_REGISTERED_DISTRICT             = 'business_registered_district';
    private const     BUSINESS_REGISTERED_PIN                  = 'business_registered_pin';
    private const     BUSINESS_OPERATION_ADDRESS               = 'business_operation_address';
    private const     BUSINESS_OPERATION_ADDRESS_L2            = 'business_operation_address_l2';
    private const     BUSINESS_OPERATION_COUNTRY               = 'business_operation_country';
    private const     BUSINESS_OPERATION_STATE                 = 'business_operation_state';
    private const     BUSINESS_OPERATION_CITY                  = 'business_operation_city';
    private const     BUSINESS_OPERATION_DISTRICT              = 'business_operation_district';
    private const     BUSINESS_OPERATION_PIN                   = 'business_operation_pin';
    private const     PROMOTER_PAN                             = 'promoter_pan';
    private const     PROMOTER_PAN_NAME                        = 'promoter_pan_name';
    private const     BUSINESS_DOE                             = 'business_doe';
    private const     GSTIN                                    = 'gstin';
    private const     P_GSTIN                                  = 'p_gstin';
    private const     COMPANY_CIN                              = 'company_cin';
    private const     COMPANY_PAN                              = 'company_pan';
    private const     COMPANY_PAN_NAME                         = 'company_pan_name';
    private const     BUSINESS_CATEGORY                        = 'business_category';
    private const     BUSINESS_SUBCATEGORY                     = 'business_subcategory';
    private const     BUSINESS_MODEL                           = 'business_model';
    private const     TRANSACTION_VOLUME                       = 'transaction_volume';
    private const     TRANSACTION_VALUE                        = 'transaction_value';
    private const     WEBSITE_ABOUT                            = 'website_about';
    private const     WEBSITE_CONTACT                          = 'website_contact';
    private const     WEBSITE_PRIVACY                          = 'website_privacy';
    private const     WEBSITE_TERMS                            = 'website_terms';
    private const     WEBSITE_REFUND                           = 'website_refund';
    private const     WEBSITE_PRICING                          = 'website_pricing';
    private const     WEBSITE_LOGIN                            = 'website_login';
    private const     STEPS_FINISHED                           = 'steps_finished';
    private const     ACTIVATION_PROGRESS                      = 'activation_progress';
    private const     LOCKED                                   = 'locked';
    private const     ACTIVATION_STATUS                        = 'activation_status';
    private const     BANK_DETAILS_VERIFICATION_STATUS         = 'bank_details_verification_status';
    private const     POA_VERIFICATION_STATUS                  = 'poa_verification_status';
    private const     POI_VERIFICATION_STATUS                  = 'poi_verification_status';
    private const     CLARIFICATION_MODE                       = 'clarification_mode';
    private const     ARCHIVED                                 = 'archived';
    private const     MARKETPLACE_ACTIVATION_STATUS            = 'marketplace_activation_status';
    private const     VIRTUAL_ACCOUNTS_ACTIVATION_STATUS       = 'virtual_accounts_activation_status';
    private const     SUBSCRIPTIONS_ACTIVATION_STATUS          = 'subscriptions_activation_status';
    private const     SUBMITTED                                = 'submitted';
    private const     SUBMITTED_AT                             = 'submitted_at';
    private const     BANK_ACCOUNT_NUMBER                      = 'bank_account_number';
    private const     BANK_ACCOUNT_NAME                        = 'bank_account_name';
    private const     BANK_ACCOUNT_TYPE                        = 'bank_account_type';
    private const     BANK_BRANCH                              = 'bank_branch';
    private const     BANK_BRANCH_IFSC                         = 'bank_branch_ifsc';
    private const     BANK_BENEFICIARY_ADDRESS1                = 'bank_beneficiary_address1';
    private const     BANK_BENEFICIARY_ADDRESS2                = 'bank_beneficiary_address2';
    private const     BANK_BENEFICIARY_ADDRESS3                = 'bank_beneficiary_address3';
    private const     BANK_BENEFICIARY_CITY                    = 'bank_beneficiary_city';
    private const     BANK_BENEFICIARY_STATE                   = 'bank_beneficiary_state';
    private const     BANK_BENEFICIARY_PIN                     = 'bank_beneficiary_pin';
    private const     ROLE                                     = 'role';
    private const     DEPARTMENT                               = 'department';
    private const     CREATED_AT                               = 'created_at';
    private const     UPDATED_AT                               = 'updated_at';
    private const     ACTIVATION_FLOW                          = 'activation_flow';
    private const     INTERNATIONAL_ACTIVATION_FLOW            = 'international_activation_flow';
    private const     LIVE_TRANSACTION_DONE                    = 'live_transaction_done';
    private const     KYC_CLARIFICATION_REASONS                = 'kyc_clarification_reasons';
    private const     KYC_ADDITIONAL_DETAILS                   = 'kyc_additional_details';
    private const     ESTD_YEAR                                = 'estd_year';
    private const     AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS = 'authorized_signatory_residential_address';
    private const     AUTHORIZED_SIGNATORY_DOB                 = 'authorized_signatory_dob';
    private const     PLATFORM                                 = 'platform';
    private const     FUND_ACCOUNT_VALIDATION_ID               = 'fund_account_validation_id';
    private const     GSTIN_VERIFICATION_STATUS                = 'gstin_verification_status';
    private const     DATE_OF_ESTABLISHMENT                    = 'date_of_establishment';
    private const     ACTIVATION_FORM_MILESTONE                = 'activation_form_milestone';
    private const     COMPANY_PAN_VERIFICATION_STATUS          = 'company_pan_verification_status';
    private const     CIN_VERIFICATION_STATUS                  = 'cin_verification_status';
    private const     COMPANY_PAN_DOC_VERIFICATION_STATUS      = 'company_pan_doc_verification_status';
    private const     PERSONAL_PAN_DOC_VERIFICATION_STATUS     = 'personal_pan_doc_verification_status';
    private const     BANK_DETAILS_DOC_VERIFICATION_STATUS     = 'bank_details_doc_verification_status';
    private const     MSME_DOC_VERIFICATION_STATUS             = 'msme_doc_verification_status';
    private const     SHOP_ESTABLISHMENT_NUMBER                = 'shop_establishment_number';
    private const     SHOP_ESTABLISHMENT_VERIFICATION_STATUS   = 'shop_establishment_verification_status';
    private const     BUSINESS_SUGGESTED_PIN                   = 'business_suggested_pin';
    private const     BUSINESS_SUGGESTED_ADDRESS               = 'business_suggested_address';
    private const     FRAUD_TYPE                               = 'fraud_type';
    private const     BAS_BUSINESS_ID                          = 'bas_business_id';
    private const     SHOP_ESTABLISHMENT_VERIFIABLE_ZONE       = 'shop_establishment_verifiable_zone';
    private const     CAN_SUBMIT                               = 'can_submit';
    private const     ACTIVATED                                = 'activated';
    private const     LIVE                                     = 'live';
    private const     INTERNATIONAL                            = 'international';
    private const     STAKEHOLDER                              = 'stakeholder';
    private const     MERCHANT_AVG_ORDER_VALUE                 = 'merchant_avg_order_value';
    private const     ISDEDUPE                                 = 'isDedupe';
    private const     ISAUTOKYCDONE                            = 'isAutoKycDone';
    private const     ISHARDLIMITREACHED                       = 'isHardLimitReached';
    private const     MERCHANT_BUSINESS_DETAIL                 = 'merchant_business_detail';
    private const     PLAYSTORE_URL                            = 'playstore_url';
    private const     APPSTORE_URL                             = 'appstore_url';
    private const     MERCHANT_VERIFICATION_DETAIL             = 'merchant_verification_detail';
    private const     ACTIVATION_STATUS_CHANGE_LOGS            = "activationStatusChangeLogs";
    private const     ADDITIONAL_WEBSITES                      = 'additional_websites';
    private const     ALLOWED_NEXT_ACTIVATION_STATUSES         = 'allowed_next_activation_statuses';
    private const     CLIENT_APPLICATIONS                      = 'client_applications';
    private const     CREDIT_BALANCE                           = 'credit_balance';
    private const     DOCUMENTS                                = 'documents';
    private const     IS_SUB_MERCHANT                          = 'isSubMerchant';
    private const     TRANSACTION_REPORT_EMAIL                 = 'transaction_report_email';


    //verification object
    private const VERIFICATION_STATUS              = 'verification.status';
    private const VERIFICATION_DISABLED_REASON     = 'verification.disabled_reason';
    private const VERIFICATION_REQUIRED_FIELDS     = 'verification.required_fields';
    private const VERIFICATION_ACTIVATION_PROGRESS = 'verification.activation_progress';
    private const VERIFICATION_OPTIONAL_FIELDS     = 'verification.optional_fields';

    //dedupe object
    private const DEDUPE_ISMATCH       = 'dedupe.isMatch';
    private const DEDUPE_ISUNDERREVIEW = 'dedupe.isUnderReview';

    private const BANKING_ACCOUNT_ACCOUNT_CURRENCY        = "banking_account.account_currency";
    private const BANKING_ACCOUNT_ACCOUNT_IFSC            = "banking_account.account_ifsc";
    private const BANKING_ACCOUNT_ACCOUNT_NUMBER          = "banking_account.account_number";
    private const BANKING_ACCOUNT_ACCOUNT_TYPE            = "banking_account.account_type";
    private const BANKING_ACCOUNT_BALANCE_BALANCE         = "banking_account.balance.balance";
    private const BANKING_ACCOUNT_BALANCE_CURRENCY        = "banking_account.balance.currency";
    private const BANKING_ACCOUNT_BALANCE_ID              = "banking_account.balance.id";
    private const BANKING_ACCOUNT_BALANCE_LAST_FETCHED_AT = "banking_account.balance.last_fetched_at";
    private const BANKING_ACCOUNT_BALANCE_LOCKED_BALANCE  = "banking_account.balance.locked_balance";
    private const BANKING_ACCOUNT_BANK_INTERNAL_STATUS    = "banking_account.bank_internal_status";
    private const BANKING_ACCOUNT_BANK_REFERENCE_NUMBER   = "banking_account.bank_reference_number";
    private const BANKING_ACCOUNT_BENEFICIARY_EMAIL       = "banking_account.beneficiary_email";
    private const BANKING_ACCOUNT_BENEFICIARY_MOBILE      = "banking_account.beneficiary_mobile";
    private const BANKING_ACCOUNT_BENEFICIARY_NAME        = "banking_account.beneficiary_name";
    private const BANKING_ACCOUNT_CHANNEL                 = "banking_account.channel";
    private const BANKING_ACCOUNT_ID                      = "banking_account.id";
    private const BANKING_ACCOUNT_MERCHANT_ID             = "banking_account.merchant_id";
    private const BANKING_ACCOUNT_PINCODE                 = "banking_account.pincode";
    private const BANKING_ACCOUNT_REFERENCE1              = "banking_account.reference1";
    private const BANKING_ACCOUNT_STATUS                  = "banking_account.status";
    private const BANKING_ACCOUNT_STATUS_LAST_UPDATED_AT  = "banking_account.status_last_updated_at";
    private const BANKING_ACCOUNT_SUB_STATUS              = "banking_account.sub_status";

    //merchant object
    private const     MERCHANT_ID                          = 'merchant.id';
    private const     MERCHANT_ENTITY                      = 'merchant.entity';
    private const     MERCHANT_NAME                        = 'merchant.name';
    private const     MERCHANT_EMAIL                       = 'merchant.email';
    private const     MERCHANT_ACTIVATED                   = 'merchant.activated';
    private const     MERCHANT_ACTIVATED_AT                = 'merchant.activated_at';
    private const     MERCHANT_LIVE                        = 'merchant.live';
    private const     MERCHANT_HOLD_FUNDS                  = 'merchant.hold_funds';
    private const     MERCHANT_PRICING_PLAN_ID             = 'merchant.pricing_plan_id';
    private const     MERCHANT_PARENT_ID                   = 'merchant.parent_id';
    private const     MERCHANT_WEBSITE                     = 'merchant.website';
    private const     MERCHANT_CATEGORY                    = 'merchant.category';
    private const     MERCHANT_CATEGORY2                   = 'merchant.category2';
    private const     MERCHANT_INTERNATIONAL               = 'merchant.international';
    private const     MERCHANT_LINKED_ACCOUNT_KYC          = 'merchant.linked_account_kyc';
    private const     MERCHANT_HAS_KEY_ACCESS              = 'merchant.has_key_access';
    private const     MERCHANT_FEE_BEARER                  = 'merchant.fee_bearer';
    private const     MERCHANT_FEE_MODEL                   = 'merchant.fee_model';
    private const     MERCHANT_REFUND_SOURCE               = 'merchant.refund_source';
    private const     MERCHANT_BILLING_LABEL               = 'merchant.billing_label';
    private const     MERCHANT_RECEIPT_EMAIL_ENABLED       = 'merchant.receipt_email_enabled';
    private const     MERCHANT_RECEIPT_EMAIL_TRIGGER_EVENT = 'merchant.receipt_email_trigger_event';
    private const     MERCHANT_TRANSACTION_REPORT_EMAIL    = 'merchant.transaction_report_email';
    private const     MERCHANT_INVOICE_LABEL_FIELD         = 'merchant.invoice_label_field';
    private const     MERCHANT_CHANNEL                     = 'merchant.channel';
    private const     MERCHANT_CONVERT_CURRENCY            = 'merchant.convert_currency';
    private const     MERCHANT_MAX_PAYMENT_AMOUNT          = 'merchant.max_payment_amount';
    private const     MERCHANT_AUTO_REFUND_DELAY           = 'merchant.auto_refund_delay';
    private const     MERCHANT_AUTO_CAPTURE_LATE_AUTH      = 'merchant.auto_capture_late_auth';
    private const     MERCHANT_BRAND_COLOR                 = 'merchant.brand_color';
    private const     MERCHANT_HANDLE                      = 'merchant.handle';
    private const     MERCHANT_RISK_RATING                 = 'merchant.risk_rating';
    private const     MERCHANT_RISK_THRESHOLD              = 'merchant.risk_threshold';
    private const     MERCHANT_PARTNER_TYPE                = 'merchant.partner_type';
    private const     MERCHANT_CREATED_AT                  = 'merchant.created_at';
    private const     MERCHANT_UPDATED_AT                  = 'merchant.updated_at';
    private const     MERCHANT_SUSPENDED_AT                = 'merchant.suspended_at';
    private const     MERCHANT_ARCHIVED_AT                 = 'merchant.archived_at';
    private const     MERCHANT_ICON_URL                    = 'merchant.icon_url';
    private const     MERCHANT_LOGO_URL                    = 'merchant.logo_url';
    private const     MERCHANT_ORG_ID                      = 'merchant.org_id';
    private const     MERCHANT_FEE_CREDITS_THRESHOLD       = 'merchant.fee_credits_threshold';
    private const     MERCHANT_AMOUNT_CREDITS_THRESHOLD    = 'merchant.amount_credits_threshold';
    private const     MERCHANT_REFUND_CREDITS_THRESHOLD    = 'merchant.refund_credits_threshold';
    private const     MERCHANT_DISPLAY_NAME                = 'merchant.display_name';
    private const     MERCHANT_ACTIVATION_SOURCE           = 'merchant.activation_source';
    private const     MERCHANT_BUSINESS_BANKING            = 'merchant.business_banking';
    private const     MERCHANT_SECOND_FACTOR_AUTH          = 'merchant.second_factor_auth';
    private const     MERCHANT_RESTRICTED                  = 'merchant.restricted';
    private const     MERCHANT_DEFAULT_REFUND_SPEED        = 'merchant.default_refund_speed';
    private const     MERCHANT_PARTNERSHIP_URL             = 'merchant.partnership_url';
    private const     MERCHANT_EXTERNAL_ID                 = 'merchant.external_id';
    private const     MERCHANT_PRODUCT_INTERNATIONAL       = 'merchant.product_international';
    private const     MERCHANT_SIGNUP_SOURCE               = 'merchant.signup_source';
    private const     MERCHANT_PURPOSE_CODE                = 'merchant.purpose_code';
    private const     MERCHANT_IEC_CODE                    = 'merchant.iec_code';
    private const     MERCHANT_NOTES                       = 'merchant.notes';
    private const     MERCHANT_WHITELISTED_DOMAINS         = 'merchant.whitelisted_domains';
    private const     MERCHANT_WHITELISTED_IPS_LIVE        = 'merchant.whitelisted_ips_live';
    private const     MERCHANT_WHITELISTED_IPS_TEST        = 'merchant.whitelisted_ips_test';
    private const     RESPONSE_FIELDS_ROLE_MAPPING         = [

        self::CONTACT_NAME                             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::CONTACT_EMAIL                            => [
            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::CONTACT_MOBILE                           => [
            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::CONTACT_LANDLINE                         => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_TYPE                            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_NAME                            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_DESCRIPTION                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_DBA                             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_WEBSITE                         => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_INTERNATIONAL                   => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_PAYMENTDETAILS                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BUSINESS_REGISTERED_ADDRESS              => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_REGISTERED_ADDRESS_L2           => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_REGISTERED_COUNTRY              => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_REGISTERED_STATE                => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_REGISTERED_CITY                 => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_REGISTERED_DISTRICT             => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_REGISTERED_PIN                  => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_OPERATION_ADDRESS               => [

            Role::OPERATIONS,

        ],
        self::BUSINESS_OPERATION_ADDRESS_L2            => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_OPERATION_COUNTRY               => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_OPERATION_STATE                 => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_OPERATION_CITY                  => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_OPERATION_DISTRICT              => [

            Role::OPERATIONS,
        ],
        self::BUSINESS_OPERATION_PIN                   => [

            Role::OPERATIONS,
        ],
        self::PROMOTER_PAN                             => [

            Role::FINANCE,

        ],
        self::PROMOTER_PAN_NAME                        => [

            Role::FINANCE,

        ],
        self::BUSINESS_DOE                             => [

            Role::FINANCE,
        ],
        self::GSTIN                                    => [

            Role::FINANCE,
        ],
        self::P_GSTIN                                  => [

            Role::FINANCE,
        ],
        self::COMPANY_CIN                              => [

            Role::FINANCE,
        ],
        self::COMPANY_PAN                              => [

            Role::FINANCE,
        ],
        self::COMPANY_PAN_NAME                         => [

            Role::FINANCE,
        ],
        self::BUSINESS_CATEGORY                        => [

            Role::FINANCE,
        ],
        self::BUSINESS_SUBCATEGORY                     => [

            Role::FINANCE,
        ],
        self::BUSINESS_MODEL                           => [

            Role::FINANCE,
        ],
        self::TRANSACTION_VOLUME                       => [

            Role::FINANCE,
        ],
        self::TRANSACTION_VALUE                        => [

            Role::FINANCE,
        ],
        self::WEBSITE_ABOUT                            => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::WEBSITE_CONTACT                          => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::WEBSITE_PRIVACY                          => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::WEBSITE_TERMS                            => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::WEBSITE_REFUND                           => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::WEBSITE_PRICING                          => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::WEBSITE_LOGIN                            => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::STEPS_FINISHED                           => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::ACTIVATION_PROGRESS                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::LOCKED                                   => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::ACTIVATION_STATUS                        => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANK_DETAILS_VERIFICATION_STATUS         => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::POA_VERIFICATION_STATUS                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::POI_VERIFICATION_STATUS                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::CLARIFICATION_MODE                       => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ARCHIVED                                 => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MARKETPLACE_ACTIVATION_STATUS            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS       => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::SUBSCRIPTIONS_ACTIVATION_STATUS          => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::SUBMITTED                                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::SUBMITTED_AT                             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::TRANSACTION_REPORT_EMAIL                 => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANK_ACCOUNT_NUMBER                      => [

            Role::FINANCE,
        ],
        self::BANK_ACCOUNT_NAME                        => [

            Role::FINANCE,
        ],
        self::BANK_ACCOUNT_TYPE                        => [

            Role::FINANCE,
        ],
        self::BANK_BRANCH                              => [

            Role::FINANCE,
        ],
        self::BANK_BRANCH_IFSC                         => [

            Role::FINANCE,
        ],
        self::BANK_BENEFICIARY_ADDRESS1                => [

            Role::FINANCE,
        ],
        self::BANK_BENEFICIARY_ADDRESS2                => [

            Role::FINANCE,
        ],
        self::BANK_BENEFICIARY_ADDRESS3                => [

            Role::FINANCE,
        ],
        self::BANK_BENEFICIARY_CITY                    => [

            Role::FINANCE,
        ],
        self::BANK_BENEFICIARY_STATE                   => [

            Role::FINANCE,
        ],
        self::BANK_BENEFICIARY_PIN                     => [

            Role::FINANCE,
        ],
        self::ROLE                                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::DEPARTMENT                               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::CREATED_AT                               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::UPDATED_AT                               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::ACTIVATION_FLOW                          => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::INTERNATIONAL_ACTIVATION_FLOW            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::LIVE_TRANSACTION_DONE                    => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::KYC_CLARIFICATION_REASONS                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::KYC_ADDITIONAL_DETAILS                   => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::ESTD_YEAR                                => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::AUTHORIZED_SIGNATORY_RESIDENTIAL_ADDRESS => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::AUTHORIZED_SIGNATORY_DOB                 => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::PLATFORM                                 => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::FUND_ACCOUNT_VALIDATION_ID               => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::GSTIN_VERIFICATION_STATUS                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::DATE_OF_ESTABLISHMENT                    => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::ACTIVATION_FORM_MILESTONE                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::COMPANY_PAN_VERIFICATION_STATUS          => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::CIN_VERIFICATION_STATUS                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::COMPANY_PAN_DOC_VERIFICATION_STATUS      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::PERSONAL_PAN_DOC_VERIFICATION_STATUS     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::BANK_DETAILS_DOC_VERIFICATION_STATUS     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::MSME_DOC_VERIFICATION_STATUS             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::SHOP_ESTABLISHMENT_NUMBER                => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::SHOP_ESTABLISHMENT_VERIFICATION_STATUS   => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::BUSINESS_SUGGESTED_PIN                   => [

            Role::OPERATIONS,

        ],
        self::BUSINESS_SUGGESTED_ADDRESS               => [

            Role::OPERATIONS,

        ],
        self::FRAUD_TYPE                               => [

            Role::OPERATIONS,
        ],
        self::BAS_BUSINESS_ID                          => [

            Role::OPERATIONS,
        ],
        self::SHOP_ESTABLISHMENT_VERIFIABLE_ZONE       => [

            Role::OPERATIONS,
        ],
        self::VERIFICATION_STATUS                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::VERIFICATION_DISABLED_REASON             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP

        ],
        self::VERIFICATION_REQUIRED_FIELDS             => [

            Role::OPERATIONS,
        ],
        self::VERIFICATION_ACTIVATION_PROGRESS         => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::CAN_SUBMIT                               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ACTIVATED                                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::LIVE                                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::INTERNATIONAL                            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_ID                              => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_ENTITY                          => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_NAME                            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_EMAIL                           => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_ACTIVATED                       => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_ACTIVATED_AT                    => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_LIVE                            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_HOLD_FUNDS                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_PRICING_PLAN_ID                 => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_PARENT_ID                       => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_WEBSITE                         => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_CATEGORY                        => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_CATEGORY2                       => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_INTERNATIONAL                   => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_LINKED_ACCOUNT_KYC              => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_HAS_KEY_ACCESS                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_FEE_BEARER                      => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_FEE_MODEL                       => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_REFUND_SOURCE                   => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_BILLING_LABEL                   => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_RECEIPT_EMAIL_ENABLED           => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_RECEIPT_EMAIL_TRIGGER_EVENT     => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_TRANSACTION_REPORT_EMAIL        => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_INVOICE_LABEL_FIELD             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_CHANNEL                         => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_CONVERT_CURRENCY                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_MAX_PAYMENT_AMOUNT              => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_AUTO_REFUND_DELAY               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_AUTO_CAPTURE_LATE_AUTH          => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_BRAND_COLOR                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_HANDLE                          => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_RISK_RATING                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_RISK_THRESHOLD                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_PARTNER_TYPE                    => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_CREATED_AT                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_UPDATED_AT                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_SUSPENDED_AT                    => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_ARCHIVED_AT                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_ICON_URL                        => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_LOGO_URL                        => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_ORG_ID                          => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_FEE_CREDITS_THRESHOLD           => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_AMOUNT_CREDITS_THRESHOLD        => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_REFUND_CREDITS_THRESHOLD        => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_DISPLAY_NAME                    => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_ACTIVATION_SOURCE               => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_BUSINESS_BANKING                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_SECOND_FACTOR_AUTH              => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_RESTRICTED                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_DEFAULT_REFUND_SPEED            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_PARTNERSHIP_URL                 => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_EXTERNAL_ID                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_PRODUCT_INTERNATIONAL           => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_SIGNUP_SOURCE                   => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_PURPOSE_CODE                    => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_IEC_CODE                        => [

            Role::OPERATIONS,
        ],
        self::STAKEHOLDER                              => [

            Role::OPERATIONS,
        ],
        self::MERCHANT_AVG_ORDER_VALUE                 => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::DEDUPE_ISMATCH                           => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::DEDUPE_ISUNDERREVIEW                     => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ISDEDUPE                                 => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ISAUTOKYCDONE                            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ISHARDLIMITREACHED                       => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_BUSINESS_DETAIL                 => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::PLAYSTORE_URL                            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::APPSTORE_URL                             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_VERIFICATION_DETAIL             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ACTIVATION_STATUS_CHANGE_LOGS            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ADDITIONAL_WEBSITES                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::CLIENT_APPLICATIONS                      => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::CREDIT_BALANCE                           => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::DOCUMENTS                                => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
        ],
        self::IS_SUB_MERCHANT                          => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::VERIFICATION_OPTIONAL_FIELDS             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_NOTES                           => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_WHITELISTED_DOMAINS             => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_WHITELISTED_IPS_LIVE            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::MERCHANT_WHITELISTED_IPS_TEST            => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_ACCOUNT_CURRENCY         => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_ACCOUNT_IFSC             => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_ACCOUNT_NUMBER           => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_ACCOUNT_TYPE             => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_BALANCE_BALANCE          => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_BALANCE_CURRENCY         => [

            Role::OPERATIONS,
            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_BALANCE_ID               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_BALANCE_LAST_FETCHED_AT  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_BALANCE_LOCKED_BALANCE   => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_BANK_INTERNAL_STATUS     => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_BANK_REFERENCE_NUMBER    => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_BENEFICIARY_EMAIL        => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_BENEFICIARY_MOBILE       => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_BENEFICIARY_NAME         => [

            Role::FINANCE,
        ],
        self::BANKING_ACCOUNT_CHANNEL                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_ID                       => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_MERCHANT_ID              => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_PINCODE                  => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_REFERENCE1               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_STATUS                   => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_STATUS_LAST_UPDATED_AT   => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::BANKING_ACCOUNT_SUB_STATUS               => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
        self::ALLOWED_NEXT_ACTIVATION_STATUSES         => [

            Role::OPERATIONS,
            Role::FINANCE,
            Role::SUPPORT,
            Role::SELLERAPP
        ],
    ];

    public function getFieldRoleMapping()
    {
        return self::RESPONSE_FIELDS_ROLE_MAPPING;
    }
}
