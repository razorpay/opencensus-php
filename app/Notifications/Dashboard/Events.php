<?php


namespace RZP\Notifications\Dashboard;

use RZP\Constants\MailTags;
use RZP\Notifications\Channel;
use RZP\Models\User\Role as UserRole;

class Events
{
    const EVENT = 'event';

    const MERCHANT_BUSINESS_WEBSITE_ADD                                 = 'MERCHANT_BUSINESS_WEBSITE_ADD';

    const MERCHANT_BUSINESS_WEBSITE_UPDATE                              = 'MERCHANT_BUSINESS_WEBSITE_UPDATE';

    const BANK_ACCOUNT_CHANGE_SUCCESSFUL                                = 'BANK_ACCOUNT_CHANGE_SUCCESSFUL';

    const BANK_ACCOUNT_CHANGE_REQUEST                                   = 'BANK_ACCOUNT_CHANGE_REQUEST';

    const BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                     = 'BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE';

    const BANK_ACCOUNT_CHANGE_REJECTION_REASON                          = 'BANK_ACCOUNT_CHANGE_REJECTION_REASON';

    const INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                    = 'INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE';

    const BUSINESS_WEBSITE_ADD_REJECTION_REASON                         = 'BUSINESS_WEBSITE_ADD_REJECTION_REASON';

    const BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                      = 'BUSINESS_WEBSITE_UPDATE_REJECTION_REASON';

    const INCREASE_TRANSACTION_LIMIT_REJECTION_REASON                   = 'INCREASE_TRANSACTION_LIMIT_REJECTION_REASON';

    const NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW                = 'NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW';

    const NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW                   = 'NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW';

    const NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW           = 'NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW';

    const NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW      = 'NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW';

    const GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                       = 'GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS';

    const GSTIN_UPDATED_ON_WORKFLOW_APPROVE                             = 'GSTIN_UPDATED_ON_WORKFLOW_APPROVE';

    const BULK_REGENERATE_API_KEYS                                      = 'BULK_REGENERATE_API_KEYS';

    const GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                         = 'GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS';

    const GSTIN_ADDED_ON_WORKFLOW_APPROVE                               = 'GSTIN_ADDED_ON_WORKFLOW_APPROVE';

    const GSTIN_UPDATE_REJECTION_REASON                                 = 'GSTIN_UPDATE_REJECTION_REASON';

    const GSTIN_ADD_REJECTION_REASON                                    = 'GSTIN_ADD_REJECTION_REASON';

    const UPDATE_MERCHANT_CONTACT_FROM_ADMIN                            = 'UPDATE_MERCHANT_CONTACT_FROM_ADMIN';

    const NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                     = 'NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW';

    const NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW                  = 'NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW';

    const FEATURE_UPDATE_NOTIFICATION                                   = 'FEATURE_UPDATE_NOTIFICATION';

    const ADD_ADDITIONAL_WEBSITE_SUCCESS                                = 'ADD_ADDITIONAL_WEBSITE_SUCCESS';

    const ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                       = 'ADD_ADDITIONAL_WEBSITE_REJECTION_REASON';

    const NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW        = 'NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW';

    const COUPON_EXPIRY_ALERT                                           = 'COUPON_EXPIRY_ALERT';

    const DISABLE_NON_3DS_ALERT                                         = 'DISABLE_NON_3DS_ALERT';

    const ENABLE_NON_3DS_ALERT                                          = 'ENABLE_NON_3DS_ALERT';

    const BANK_ACCOUNT_UPDATE_SUCCESS                                   = 'BANK_ACCOUNT_UPDATE_SUCCESS';

    const BANK_ACCOUNT_UPDATE_UNDER_REVIEW                              = 'BANK_ACCOUNT_UPDATE_UNDER_REVIEW';

    const BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                          = 'BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW';

    const BANK_ACCOUNT_UPDATE_REJECTED                                  = 'BANK_ACCOUNT_UPDATE_REJECTED';

    const BANK_ACCOUNT_UPDATE_SOH_REJECTED                              = 'BANK_ACCOUNT_UPDATE_SOH_REJECTED';

    const BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                       = 'BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION';

    const BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION                   = 'BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION';

    const IE_SUCCESSFUL                                                 = 'IE_SUCCESSFUL';

    const IE_UNDER_REVIEW                                               = 'IE_UNDER_REVIEW';

    const IE_SUCCESSFUL_PPLI                                            = 'IE_SUCCESSFUL_PPLI';

    const IE_SUCCESSFUL_PG                                              = 'IE_SUCCESSFUL_PG';

    const IE_REJECTED_CLARIFICATION_NOT_PROVIDED                        = 'IE_REJECTED_CLARIFICATION_NOT_PROVIDED';

    const IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                        = 'IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE';

    const IE_REJECTED_BUSINESS_MODEL_MISMATCH                           = 'IE_REJECTED_BUSINESS_MODEL_MISMATCH';

    const IE_REJECTED_INVALID_DOCUMENTS                                 = 'IE_REJECTED_INVALID_DOCUMENTS';

    const IE_REJECTED_RISK_REJECTION                                    = 'IE_REJECTED_RISK_REJECTION';

    const IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD                   = 'IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD';

    const IE_REJECTED_DORMANT_MERCHANT                                  = 'IE_REJECTED_DORMANT_MERCHANT';

    const IE_REJECTED_RESTRICTED_BUSINESS                               = 'IE_REJECTED_RESTRICTED_BUSINESS';

    const IE_NEEDS_CLARIFICATION                                        = 'IE_NEEDS_CLARIFICATION';

    // Event vs sms templates mapping
    const SMS_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => 'sms.dashboard.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => 'sms.dashboard.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => 'sms.dashboard.bank_account_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => 'sms.dashboard.bank_account_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => 'sms.dashboard.bank_account_change_successful',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => 'sms.dashboard.increase_transaction_limit_request_approve',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => 'sms.dashboard.merchant_business_website_add_rejection',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => 'sms.dashboard.merchant_business_website_update_rejection',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => 'sms.dashboard.increase_transaction_limit_rejection',
        self::GSTIN_UPDATE_REJECTION_REASON                             => 'sms.dashboard.merchant_gstin_rejection',
        self::FEATURE_UPDATE_NOTIFICATION                               => 'sms.dashboard.feature_enabled',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => 'sms.dashboard.increase_transaction_limit_needs_clarification',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => 'sms.dashboard.merchant_bank_account_needs_clarification',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => 'sms.dashboard.merchant_gstin_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => 'sms.dashboard.merchant_website_update_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => 'sms.dashboard.merchant_website_add_needs_clarification',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => 'sms.dashboard.merchant_gstin_auto_updated',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => 'sms.dashboard.merchant_gstin_workflow_approve',
        self::BULK_REGENERATE_API_KEYS                                  => 'sms.dashboard.bulk_regenerate_api_key',
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => 'sms.dashboard.merchant_add_gstin_auto_update_V1',
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => 'sms.dashboard.merchant_add_gstin_workflow_approve',
        self::GSTIN_ADD_REJECTION_REASON                                => 'sms.dashboard.merchant_add_gstin_rejection',
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => 'sms.dashboard.merchant_add_gstin_needs_clarification_V1',
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => 'sms.dashboard.bank_account_rejection',
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => 'sms.dashboard.merchant_additional_website_successful',
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => 'sms.dashboard.merchant_additional_website_rejection',
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => 'sms.dashboard.merchant_additional_website_clarification',
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => 'sms.dashboard.bank_account_update_success',
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => 'sms.dashboard.bank_account_update_under_review',
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => 'sms.dashboard.bank_account_update_under_review',
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => 'sms.dashboard.bank_account_update_rejected',
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => 'sms.dashboard.bank_account_update_rejected',
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => 'sms.dashboard.bank_account_update_needs_clarification',
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => 'sms.dashboard.bank_account_update_needs_clarification',
        self::IE_SUCCESSFUL                                             => 'sms.dashboard.ie_successful',
        self::IE_UNDER_REVIEW                                           => 'sms.dashboard.ie_under_review',
        self::IE_SUCCESSFUL_PPLI                                        => 'sms.dashboard.ie_successful',
        self::IE_SUCCESSFUL_PG                                          => 'sms.dashboard.ie_successful',
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => 'sms.dashboard.ie_rejected_clarification_not_provided',
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => 'sms.dashboard.ie_rejected_website_details_incomplete',
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => 'sms.dashboard.ie_rejected_business_model_mismatch',
        self::IE_REJECTED_INVALID_DOCUMENTS                             => 'sms.dashboard.ie_rejected_invalid_documents',
        self::IE_REJECTED_RISK_REJECTION                                => 'sms.dashboard.ie_rejected_risk_rejection_1',
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => 'sms.dashboard.ie_rejected_merchant_high_chargebacks_fraud',
        self::IE_REJECTED_DORMANT_MERCHANT                              => 'sms.dashboard.ie_rejected_dormant_merchant',
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => 'sms.dashboard.ie_rejected_restricted_business',
        self::IE_NEEDS_CLARIFICATION                                    => 'sms.dashboard.ie_needs_clarification',
    ];

    /**
     * Event vs sms template keys mapping : only whitelisted keys will be sent to raven for template rendering
     * this will prevent to send extra key-value raven in payload
     */
    const SMS_TEMPLATE_KEYS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => [Constants::UPDATED_BUSINESS_WEBSITE],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => [Constants::PREVIOUS_BUSINESS_WEBSITE, Constants::UPDATED_BUSINESS_WEBSITE],
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => [Constants::NAME, Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => [],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => [Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => [Constants::UPDATED_TRANSACTION_LIMIT],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => [Constants::MERCHANT_NAME],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => [Constants::MERCHANT_NAME],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => [Constants::MERCHANT_NAME],
        self::GSTIN_UPDATE_REJECTION_REASON                             => [Constants::MERCHANT_NAME],
        self::FEATURE_UPDATE_NOTIFICATION                               => [Constants::FEATURE],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => [Constants::MERCHANT_NAME, Constants::MAX_PAYMENT_AMOUNT],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => [Constants::MERCHANT_NAME],
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => [Constants::GSTIN, Constants::BUSINESS_REGISTERED_ADDRESS, Constants::BUSINESS_REGISTERED_PIN, Constants::BUSINESS_REGISTERED_CITY, Constants::BUSINESS_REGISTERED_STATE],
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => [Constants::GSTIN],
        self::BULK_REGENERATE_API_KEYS                                  => [],
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => [Constants::GSTIN, Constants::BUSINESS_REGISTERED_ADDRESS, Constants::BUSINESS_REGISTERED_PIN, Constants::BUSINESS_REGISTERED_CITY, Constants::BUSINESS_REGISTERED_STATE],
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => [Constants::GSTIN],
        self::GSTIN_ADD_REJECTION_REASON                                => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => [Constants::MERCHANT_NAME],
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => [Constants::MERCHANT_NAME],
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => [Constants::MERCHANT_NAME, Constants::ADDITIONAL_WEBSITE],
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => [Constants::MERCHANT_NAME],
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => [],
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => [Constants::UPDATE_DATE],
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => [Constants::UPDATE_DATE],
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => [],
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => [],
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => [],
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => [],
        self::IE_SUCCESSFUL                                             => [],
        self::IE_UNDER_REVIEW                                           => [Constants::UPDATE_DATE],
        self::IE_SUCCESSFUL_PPLI                                        => [],
        self::IE_SUCCESSFUL_PG                                          => [],
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => [],
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => [],
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => [],
        self::IE_REJECTED_INVALID_DOCUMENTS                             => [],
        self::IE_REJECTED_RISK_REJECTION                                => [Constants::UPDATE_DATE],
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => [Constants::UPDATE_DATE],
        self::IE_REJECTED_DORMANT_MERCHANT                              => [Constants::UPDATE_DATE],
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => [Constants::UPDATE_DATE],
        self::IE_NEEDS_CLARIFICATION                                    => [],
    ];

    // Event vs whatsapp templates mapping
    const WHATSAPP_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => 'whatsapp.merchant.dashboard.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => 'whatsapp.merchant.dashboard.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => 'whatsapp.merchant.dashboard.bank_account_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => 'whatsapp.merchant.dashboard.bank_account_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => 'whatsapp.merchant.dashboard.bank_account_change_successful',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => 'whatsapp.merchant.dashboard.increase_transaction_limit_request_approve',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => 'whatsapp.merchant.dashboard.merchant_business_website_add_rejection',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => 'whatsapp.merchant.dashboard.merchant_business_website_update_rejection',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => 'whatsapp.merchant.dashboard.increase_transaction_limit_rejection',
        self::GSTIN_UPDATE_REJECTION_REASON                             => 'whatsapp.merchant.dashboard.merchant_gstin_rejection',
        self::FEATURE_UPDATE_NOTIFICATION                               => 'whatsapp.merchant.dashboard.feature_enabled',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => 'whatsapp.merchant.dashboard.increase_transaction_limit_needs_clarification',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => 'whatsapp.merchant.dashboard.merchant_bank_account_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => 'whatsapp.merchant.dashboard.merchant_business_website_update_needs_clarification',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => 'whatsapp.merchant.dashboard.merchant_business_website_add_needs_clarification',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => 'whatsapp.merchant.dashboard.merchant_gstin_needs_clarification',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => 'whatsapp.merchant.dashboard.merchant_gstin_auto_updated',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => 'whatsapp.merchant.dashboard.merchant_gstin_workflow_approve',
        self::BULK_REGENERATE_API_KEYS                                  => 'whatsapp.merchant.dashboard.bulk_regenerate_api_keys',
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => 'whatsapp.merchant.dashboard.merchant_add_gstin_workflow_approve',
        self::GSTIN_ADD_REJECTION_REASON                                => 'whatsapp.merchant.dashboard.merchant_add_gstin_rejection',
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => 'whatsapp.merchant.dashboard.merchant_add_gstin_needs_clarification',
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => 'whatsapp.merchant.dashboard.bank_account_rejection',
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => 'whatsapp.merchant.dashboard.merchant_add_gstin_auto_update4',
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => 'whatsapp.merchant.dashboard.merchant_additional_website_successful',
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => 'whatsapp.merchant.dashboard.merchant_additional_website_rejection',
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => 'whatsapp.merchant.dashboard.merchant_additional_website_clarification',
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => 'whatsapp.merchant.dashboard.bank_account_update_success',
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => 'whatsapp.merchant.dashboard.bank_account_update_under_review',
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => 'whatsapp.merchant.dashboard.bank_account_update_soh_under_review5',
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => 'whatsapp.merchant.dashboard.bank_account_update_rejected',
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => 'whatsapp.merchant.dashboard.bank_account_update_soh_rejected',
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => 'whatsapp.merchant.dashboard.bank_account_update_needs_clarification',
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => 'whatsapp.merchant.dashboard.bank_account_update_soh_needs_clarification',
        self::IE_SUCCESSFUL                                             => 'whatsapp.merchant.dashboard.ie_successful',
        self::IE_UNDER_REVIEW                                           => 'whatsapp.merchant.dashboard.ie_under_review',
        self::IE_SUCCESSFUL_PPLI                                        => 'whatsapp.merchant.dashboard.ie_successful_ppli',
        self::IE_SUCCESSFUL_PG                                          => 'whatsapp.merchant.dashboard.ie_successful_pg',
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => 'whatsapp.merchant.dashboard.ie_rejected_clarification_not_provided',
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => 'whatsapp.merchant.dashboard.ie_rejected_website_details_incomplete',
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => 'whatsapp.merchant.dashboard.ie_rejected_business_model_mismatch',
        self::IE_REJECTED_INVALID_DOCUMENTS                             => 'whatsapp.merchant.dashboard.ie_rejected_invalid_documents',
        self::IE_REJECTED_RISK_REJECTION                                => 'whatsapp.merchant.dashboard.ie_rejected_risk_rejection',
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => 'whatsapp.merchant.dashboard.ie_rejected_merchant_high_chargebacks_fraud',
        self::IE_REJECTED_DORMANT_MERCHANT                              => 'whatsapp.merchant.dashboard.ie_rejected_dormant_merchant',
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => 'whatsapp.merchant.dashboard.ie_rejected_restricted_business',
        self::IE_NEEDS_CLARIFICATION                                    => 'whatsapp.merchant.dashboard.ie_needs_clarification',
    ];

    /**
     * Event vs whatsapp template keys mapping : only whitelisted keys will be sent to stork as parameters for template rendering
     */
    const WHATSAPP_TEMPLATE_KEYS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => [Constants::UPDATED_BUSINESS_WEBSITE],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => [Constants::PREVIOUS_BUSINESS_WEBSITE, Constants::UPDATED_BUSINESS_WEBSITE],
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => [Constants::NAME, Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => [],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => [Constants::BENEFICIARY_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => [Constants::UPDATED_TRANSACTION_LIMIT],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => [Constants::MERCHANT_NAME],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => [Constants::MERCHANT_NAME],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => [Constants::MERCHANT_NAME],
        self::GSTIN_UPDATE_REJECTION_REASON                             => [Constants::MERCHANT_NAME],
        self::FEATURE_UPDATE_NOTIFICATION                               => [Constants::FEATURE],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => [Constants::MERCHANT_NAME, Constants::MAX_PAYMENT_AMOUNT],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => [Constants::MERCHANT_NAME],
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => [Constants::GSTIN, Constants::BUSINESS_REGISTERED_ADDRESS, Constants::BUSINESS_REGISTERED_PIN, Constants::BUSINESS_REGISTERED_CITY, Constants::BUSINESS_REGISTERED_STATE],
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => [Constants::GSTIN],
        self::BULK_REGENERATE_API_KEYS                                  => [],
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => [Constants::GSTIN],
        self::GSTIN_ADD_REJECTION_REASON                                => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => [Constants::MERCHANT_NAME],
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => [Constants::MERCHANT_NAME],
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => [Constants::GSTIN, Constants::BUSINESS_REGISTERED_ADDRESS, Constants::BUSINESS_REGISTERED_PIN, Constants::BUSINESS_REGISTERED_CITY, Constants::BUSINESS_REGISTERED_STATE],
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => [Constants::MERCHANT_NAME, Constants::ADDITIONAL_WEBSITE],
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => [Constants::MERCHANT_NAME],
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => [Constants::MERCHANT_NAME],
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => [Constants::MERCHANT_NAME, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE],
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => [Constants::MERCHANT_NAME, Constants::UPDATE_DATE, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE, Constants::LAST_3],
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => [Constants::MERCHANT_NAME, Constants::UPDATE_DATE, Constants::ACCOUNT_NUMBER, Constants::IFSC_CODE, Constants::LAST_3],
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => [Constants::MERCHANT_NAME, Constants::LAST_3],
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => [Constants::MERCHANT_NAME, Constants::LAST_3],
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => [Constants::MERCHANT_NAME, Constants::LAST_3],
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => [Constants::MERCHANT_NAME, Constants::LAST_3],
        self::IE_SUCCESSFUL                                             => [Constants::MERCHANT_NAME],
        self::IE_UNDER_REVIEW                                           => [Constants::MERCHANT_NAME, Constants::UPDATE_DATE],
        self::IE_SUCCESSFUL_PPLI                                        => [Constants::MERCHANT_NAME],
        self::IE_SUCCESSFUL_PG                                          => [Constants::MERCHANT_NAME],
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => [Constants::MERCHANT_NAME],
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => [Constants::MERCHANT_NAME],
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => [Constants::MERCHANT_NAME],
        self::IE_REJECTED_INVALID_DOCUMENTS                             => [Constants::MERCHANT_NAME],
        self::IE_REJECTED_RISK_REJECTION                                => [Constants::MERCHANT_NAME, Constants::UPDATE_DATE],
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => [Constants::MERCHANT_NAME, Constants::UPDATE_DATE],
        self::IE_REJECTED_DORMANT_MERCHANT                              => [Constants::MERCHANT_NAME, Constants::UPDATE_DATE],
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => [Constants::MERCHANT_NAME, Constants::UPDATE_DATE],
        self::IE_NEEDS_CLARIFICATION                                    => [Constants::MERCHANT_NAME],
    ];

    // Event vs email templates mapping
    const EMAIL_TEMPLATES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => 'emails.merchant.merchant_business_website_add',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => 'emails.merchant.merchant_business_website_update',
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => 'emails.merchant.bankaccount_change_request',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => 'emails.merchant.bankaccount_change_penny_testing_failure',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => 'emails.merchant.bankaccount_change',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => 'emails.merchant.increase_transaction_limit_request_approve',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => 'emails.merchant.gstin_updated_self_serve',
        self::FEATURE_UPDATE_NOTIFICATION                               => 'emails.merchant.feature_enabled',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => 'emails.merchant.gstin_updated_on_workflow_approve',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => 'emails.merchant.rejection_reason_notification',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => 'emails.merchant.rejection_reason_notification',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => 'emails.merchant.rejection_reason_notification',
        self::UPDATE_MERCHANT_CONTACT_FROM_ADMIN                        => 'emails.merchant.update_merchant_contact_from_admin',
        self::GSTIN_UPDATE_REJECTION_REASON                             => 'emails.merchant.rejection_reason_notification',
        self::GSTIN_ADD_REJECTION_REASON                                => 'emails.merchant.rejection_reason_notification',
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => 'emails.merchant.gstin_updated_self_serve',
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => 'emails.merchant.gstin_updated_on_workflow_approve',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => 'emails.merchant.needs_clarification_on_workflow',
        self::BULK_REGENERATE_API_KEYS                                  => 'emails.merchant.bulk_regenerate_api_keys',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => 'emails.merchant.needs_clarification_on_workflow',
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => 'emails.merchant.needs_clarification_on_workflow',
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => 'emails.merchant.rejection_reason_notification',
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => 'emails.merchant.add_additional_website_success',
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => 'emails.merchant.rejection_reason_notification',
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => 'emails.merchant.needs_clarification_on_workflow',
        self::COUPON_EXPIRY_ALERT                                       => 'emails.coupon.coupon_expiry_alert',
        self::DISABLE_NON_3DS_ALERT                                     => 'emails.merchant.disable_non_3ds_alert',
        self::ENABLE_NON_3DS_ALERT                                      => 'emails.merchant.enable_non_3ds_alert',
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => 'emails.merchant.bank_account_update_success',
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => 'emails.merchant.bank_account_update_under_review',
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => 'emails.merchant.bank_account_update_soh_under_review',
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => 'emails.merchant.bank_account_update_rejected',
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => 'emails.merchant.bank_account_update_soh_rejected',
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => 'emails.merchant.bank_account_update_needs_clarification',
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => 'emails.merchant.bank_account_update_soh_needs_clarification',
        self::IE_SUCCESSFUL                                             => 'emails.merchant.ie_successful',
        self::IE_UNDER_REVIEW                                           => 'emails.merchant.ie_under_review',
        self::IE_SUCCESSFUL_PPLI                                        => 'emails.merchant.ie_successful_ppli',
        self::IE_SUCCESSFUL_PG                                          => 'emails.merchant.ie_successful_pg',
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => 'emails.merchant.ie_rejected_clarification_not_provided',
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => 'emails.merchant.ie_rejected_website_details_incomplete',
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => 'emails.merchant.ie_rejected_business_model_mismatch',
        self::IE_REJECTED_INVALID_DOCUMENTS                             => 'emails.merchant.ie_rejected_invalid_documents',
        self::IE_REJECTED_RISK_REJECTION                                => 'emails.merchant.ie_rejected_risk_rejection',
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => 'emails.merchant.ie_rejected_merchant_high_chargebacks_fraud',
        self::IE_REJECTED_DORMANT_MERCHANT                              => 'emails.merchant.ie_rejected_dormant_merchant',
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => 'emails.merchant.ie_rejected_restricted_business',
        self::IE_NEEDS_CLARIFICATION                                    => 'emails.merchant.ie_needs_clarification',
    ];

    // Event vs email Tags mapping
    const EMAIL_TAGS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => MailTags::MERCHANT_BUSINESS_WEBSITE_ADD,
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => MailTags::MERCHANT_BUSINESS_WEBSITE_UPDATE,
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => MailTags::ACCOUNT_CHANGE_REQUEST,
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => MailTags::ACCOUNT_CHANGED,
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => MailTags::ACCOUNT_CHANGED,
        self::FEATURE_UPDATE_NOTIFICATION                               => MailTags::FEATURE_ENABLED,
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => MailTags::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE,
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => MailTags::GSTIN_UPDATED_VALIDATION_SUCCESS,
        self::UPDATE_MERCHANT_CONTACT_FROM_ADMIN                        => MailTags::UPDATE_CONTACT_NUMBER,
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => MailTags::GSTIN_UPDATED_WORKFLOW_APPROVE,
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => MailTags::MERCHANT_BUSINESS_WEBSITE_ADD_REJECTION_REASON,
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => MailTags::MERCHANT_BUSINESS_WEBSITE_UPDATE_REJECTION_REASON,
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => MailTags::MERCHANT_INCREASE_TRANSACTION_LIMIT_REJECTION_REASON,
        self::GSTIN_UPDATE_REJECTION_REASON                             => MailTags::MERCHANT_GSTIN_UPDATE_REJECTION_REASON,
        self::GSTIN_ADD_REJECTION_REASON                                => MailTags::MERCHANT_GSTIN_ADD_REJECTION_REASON,
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => MailTags::GSTIN_ADDED_VALIDATION_SUCCESS,
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => MailTags::GSTIN_ADDED_WORKFLOW_APPROVE,
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => MailTags::MERCHANT_CLARIFICATION_ON_BANK_ACCOUNT_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => MailTags::MERCHANT_CLARIFICATION_ON_TRANSACTION_LIMIT_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => MailTags::MERCHANT_CLARIFICATION_ON_WEBSITE_UPDATE_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => MailTags::MERCHANT_CLARIFICATION_ON_WEBSITE_ADD_WORKFLOW,
        self::BULK_REGENERATE_API_KEYS                                  => MailTags::BULK_API_KEYS_REGENERATE,
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => MailTags::MERCHANT_CLARIFICATION_ON_GSTIN_UPDATE_WORKFLOW,
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => MailTags::MERCHANT_CLARIFICATION_ON_GSTIN_ADD_WORKFLOW,
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => MailTags::MERCHANT_BANK_ACCOUNT_UPDATE_REJECTION_REASON,
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => MailTags::MERCHANT_ADD_ADDITIONAL_WEBSITE_SUCCESS,
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => MailTags::MERCHANT_ADD_ADDITIONAL_WEBSITE_REJECTION_REASON,
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => MailTags::MERCHANT_CLARIFICATION_ON_ADDITIONAL_WEBSITE_WORKFLOW,
        self::DISABLE_NON_3DS_ALERT                                     => MailTags::DISABLE_NON_3DS_SUCCESS,
        self::ENABLE_NON_3DS_ALERT                                      => MailTags::ENABLE_NON_3DS_REQUEST_SUCCESS,
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => MailTags::BANK_ACCOUNT_UPDATE_SUCCESS,
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => MailTags::BANK_ACCOUNT_UPDATE_UNDER_REVIEW,
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => MailTags::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW,
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => MailTags::BANK_ACCOUNT_UPDATE_REJECTED,
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => MailTags::BANK_ACCOUNT_UPDATE_SOH_REJECTED,
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => MailTags::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION,
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => MailTags::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION,
        self::IE_SUCCESSFUL                                             => MailTags::IE_SUCCESSFUL,
        self::IE_UNDER_REVIEW                                           => MailTags::IE_UNDER_REVIEW,
        self::IE_SUCCESSFUL_PPLI                                        => MailTags::IE_SUCCESSFUL_PPLI,
        self::IE_SUCCESSFUL_PG                                          => MailTags::IE_SUCCESSFUL_PG,
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => MailTags::IE_REJECTED_CLARIFICATION_NOT_PROVIDED,
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => MailTags::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE,
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => MailTags::IE_REJECTED_BUSINESS_MODEL_MISMATCH,
        self::IE_REJECTED_INVALID_DOCUMENTS                             => MailTags::IE_REJECTED_INVALID_DOCUMENTS,
        self::IE_REJECTED_RISK_REJECTION                                => MailTags::IE_REJECTED_RISK_REJECTION,
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => MailTags::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD,
        self::IE_REJECTED_DORMANT_MERCHANT                              => MailTags::IE_REJECTED_DORMANT_MERCHANT,
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => MailTags::IE_REJECTED_RESTRICTED_BUSINESS,
        self::IE_NEEDS_CLARIFICATION                                    => MailTags::IE_NEEDS_CLARIFICATION,
    ];

    // Event vs email subject mapping
    const EMAIL_SUBJECTS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => 'Razorpay | Update on API key access for %s(MID: %s)',
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => 'Razorpay | Website updated successfully for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => 'Razorpay | Bank account change request for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => 'Razorpay | Update on bank account change request for %s(MID: %s)',
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => 'Razorpay | Bank account change successful for %s(MID: %s)',
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => 'Razorpay | Transaction Limit updated successfully for %s(MID: %s)',
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => 'Razorpay | Gstin updated for %s(MID: %s)',
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => 'Razorpay | Gstin updated for %s(MID: %s)',
        self::UPDATE_MERCHANT_CONTACT_FROM_ADMIN                        => 'Mobile Number for your Razorpay account is updated',
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => '%s',
        self::FEATURE_UPDATE_NOTIFICATION                               => '%s',
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => '%s',
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => '%s',
        self::GSTIN_UPDATE_REJECTION_REASON                             => '%s',
        self::GSTIN_ADD_REJECTION_REASON                                => '%s',
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => 'Razorpay | Gstin added for %s(MID: %s)',
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => 'Razorpay | Gstin added for %s(MID: %s)',
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => '%s',
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => '%s',
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => '%s',
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => '%s',
        self::BULK_REGENERATE_API_KEYS                                  => 'API key De-activated',
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => '%s',
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => '%s',
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => '%s',
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => 'Razorpay | Additional Website added successfully for %s(MID: %s)',
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => '%s',
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => '%s',
        self::DISABLE_NON_3DS_ALERT                                     => 'Non-3D Secure Card Transactions Disabled for your Razorpay Account',
        self::ENABLE_NON_3DS_ALERT                                      => 'Non-3D Secure Card Transactions Enabled for your Razorpay Account',
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => 'Bank account change request successful',
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => 'Bank account change request under review',
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => 'Bank account change request under review',
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => 'Bank account change request rejected',
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => 'Bank account change request rejected',
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => 'Action required: Bank account change request',
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => 'Action required: Bank account change request',
        self::IE_SUCCESSFUL                                             => 'International card payments request successful',
        self::IE_UNDER_REVIEW                                           => 'International card payments request under review',
        self::IE_SUCCESSFUL_PPLI                                        => 'International card payments request successful',
        self::IE_SUCCESSFUL_PG                                          => 'International card payments request successful',
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => 'International card payments request rejected',
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => 'International card payments request rejected',
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => 'International card payments request rejected',
        self::IE_REJECTED_INVALID_DOCUMENTS                             => 'International card payments request rejected',
        self::IE_REJECTED_RISK_REJECTION                                => 'International card payments request rejected',
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => 'International card payments request rejected',
        self::IE_REJECTED_DORMANT_MERCHANT                              => 'International card payments request rejected',
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => 'International card payments request rejected',
        self::IE_NEEDS_CLARIFICATION                                    => 'Action required: International card payments request',
    ];

    // Event vs recipients role mapping
    const RECIPIENT_ROLES = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => [UserRole::OWNER],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => [UserRole::OWNER],
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => [UserRole::OWNER, UserRole::ADMIN],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => [UserRole::OWNER, UserRole::ADMIN],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => [UserRole::OWNER, UserRole::ADMIN],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => [UserRole::OWNER],
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => [UserRole::OWNER],
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => [UserRole::OWNER],
        self::UPDATE_MERCHANT_CONTACT_FROM_ADMIN                        => [UserRole::OWNER],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => [UserRole::OWNER],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => [UserRole::OWNER],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => [UserRole::OWNER],
        self::GSTIN_UPDATE_REJECTION_REASON                             => [UserRole::OWNER],
        self::GSTIN_ADD_REJECTION_REASON                                => [UserRole::OWNER],
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => [UserRole::OWNER],
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => [UserRole::OWNER],
        self::FEATURE_UPDATE_NOTIFICATION                               => [UserRole::OWNER],
        self::BULK_REGENERATE_API_KEYS                                  => [UserRole::OWNER, UserRole::ADMIN],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => [UserRole::OWNER],
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => [UserRole::OWNER],
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => [UserRole::OWNER],
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => [UserRole::OWNER],
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => [UserRole::OWNER],
        self::DISABLE_NON_3DS_ALERT                                     => [UserRole::OWNER],
        self::ENABLE_NON_3DS_ALERT                                      => [UserRole::OWNER],
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => [UserRole::OWNER],
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => [UserRole::OWNER],
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => [UserRole::OWNER],
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => [UserRole::OWNER],
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => [UserRole::OWNER],
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => [UserRole::OWNER],
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => [UserRole::OWNER],
        self::IE_SUCCESSFUL                                             => [UserRole::OWNER],
        self::IE_UNDER_REVIEW                                           => [UserRole::OWNER],
        self::IE_SUCCESSFUL_PPLI                                        => [UserRole::OWNER],
        self::IE_SUCCESSFUL_PG                                          => [UserRole::OWNER],
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => [UserRole::OWNER],
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => [UserRole::OWNER],
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => [UserRole::OWNER],
        self::IE_REJECTED_INVALID_DOCUMENTS                             => [UserRole::OWNER],
        self::IE_REJECTED_RISK_REJECTION                                => [UserRole::OWNER],
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => [UserRole::OWNER],
        self::IE_REJECTED_DORMANT_MERCHANT                              => [UserRole::OWNER],
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => [UserRole::OWNER],
        self::IE_NEEDS_CLARIFICATION                                    => [UserRole::OWNER],
    ];

    // Event vs supported channel mapping
    const SUPPORTED_CHANNELS_FOR_EVENTS = [
        self::MERCHANT_BUSINESS_WEBSITE_ADD                             => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::MERCHANT_BUSINESS_WEBSITE_UPDATE                          => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_CHANGE_REQUEST                               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_CHANGE_PENNY_TESTING_FAILURE                 => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_CHANGE_SUCCESSFUL                            => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE                => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_UPDATED_ON_BVS_VALIDATION_SUCCESS                   => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_UPDATED_ON_WORKFLOW_APPROVE                         => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BUSINESS_WEBSITE_ADD_REJECTION_REASON                     => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BUSINESS_WEBSITE_UPDATE_REJECTION_REASON                  => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::INCREASE_TRANSACTION_LIMIT_REJECTION_REASON               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_UPDATE_REJECTION_REASON                             => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_ADD_REJECTION_REASON                                => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_ADDED_ON_BVS_VALIDATION_SUCCESS                     => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::GSTIN_ADDED_ON_WORKFLOW_APPROVE                           => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_BANK_ACCOUNT_UPDATE_WORKFLOW       => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_TRANSACTION_LIMIT_UPDATE_WORKFLOW  => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_WEBSITE_UPDATE_WORKFLOW            => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_WEBSITE_ADD_WORKFLOW               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_GSTIN_UPDATE_WORKFLOW              => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::FEATURE_UPDATE_NOTIFICATION                               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::UPDATE_MERCHANT_CONTACT_FROM_ADMIN                        => [Channel::EMAIL],
        self::NEED_CLARIFICATION_FOR_GSTIN_ADD_WORKFLOW                 => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BULK_REGENERATE_API_KEYS                                  => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_CHANGE_REJECTION_REASON                      => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::ADD_ADDITIONAL_WEBSITE_SUCCESS                            => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::ADD_ADDITIONAL_WEBSITE_REJECTION_REASON                   => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::NEED_CLARIFICATION_FOR_ADD_ADDITIONAL_WEBSITE_WORKFLOW    => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::DISABLE_NON_3DS_ALERT                                     => [Channel::EMAIL],
        self::ENABLE_NON_3DS_ALERT                                      => [Channel::EMAIL],
        self::BANK_ACCOUNT_UPDATE_SUCCESS                               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_UPDATE_UNDER_REVIEW                          => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW                      => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_UPDATE_REJECTED                              => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_UPDATE_SOH_REJECTED                          => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION                   => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_SUCCESSFUL                                             => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_UNDER_REVIEW                                           => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_SUCCESSFUL_PPLI                                        => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_SUCCESSFUL_PG                                          => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_CLARIFICATION_NOT_PROVIDED                    => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE                    => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_BUSINESS_MODEL_MISMATCH                       => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_INVALID_DOCUMENTS                             => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_RISK_REJECTION                                => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD               => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_DORMANT_MERCHANT                              => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_REJECTED_RESTRICTED_BUSINESS                           => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
        self::IE_NEEDS_CLARIFICATION                                    => [Channel::EMAIL, Channel::SMS, Channel::WHATSAPP],
    ];
}
