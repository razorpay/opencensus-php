<?php

namespace RZP\Constants;

class MailTags
{
    const HEADER                           = 'X-Mailgun-Tag';
    const SES_HEADER                       = 'X-SES-MESSAGE-TAGS';
    const SES_CONFIGURATION_HEADER         = 'X-SES-CONFIGURATION-SET';

    /**
     * Defines tags associated with emails, stored in the X-Mailgun-Tag header
     */

    const KOTAK_BENEFICIARY_MAIL           = 'kotak_beneficiary_mail';
    const KOTAK_SETTLEMENT_FILES           = 'kotak_settlement_files';
    const KOTAK_PAYOUT_SUMMARY             = 'kotak_payout_summary';

    const ICICI_SETTLEMENT_FILES           = 'icici_settlement_files';
    const ICICI_BENEFICIARY_MAIL           = 'icici_beneficiary_mail';

    const AXIS_SETTLEMENT_FILES            = 'axis_settlement_files';
    const AXIS_BENEFICIARY_MAIL            = 'axis_beneficiary_mail';

    const AXIS_PAYSECURE_MAIL              = 'axis_paysecure_mail';

    const HDFC_SETTLEMENT_FILES            = 'hdfc_settlement_files';
    const HDFC_BENEFICIARY_MAIL            = 'hdfc_beneficiary_mail';

    const YESBANK_BENEFICIARY_MAIL         = 'yesbank_beneficiary_mail';
    const YESBANK_SETTLEMENT               = 'yesbank_settlement';

    const RBL_SETTLEMENT                   = 'rbl_settlement';

    const SETTLEMENT_FAILURE_EMAIL         = 'settlement_failure_email';

    const FTA_RECON_REPORT                 = 'fta_recon_report';
    const FTA_CRITICAL_ERROR               = 'fta_critical_error';

    const CORPORATION_NETBANKING_REFUNDS_MAIL   = 'corporation_netbanking_refunds_mail';
    const ALLAHABAD_NETBANKING_REFUNDS_MAIL     = 'allahabad_netbanking_refunds_mail';
    const HDFC_NETBANKING_REFUNDS_MAIL          = 'hdfc_netbanking_refunds_mail';
    const BOB_NETBANKING_REFUNDS_MAIL           = 'bob_netbanking_refunds_mail';
    const AXIS_NETBANKING_REFUNDS_MAIL          = 'axis_netbanking_refunds_mail';
    const CSB_NETBANKING_REFUNDS_MAIL           = 'csb_netbanking_refunds_mail';
    const AIRTEL_MONEY_REFUNDS_MAIL             = 'airtel_money_refunds_mail';
    const AIRTEL_MONEY_FAILED_REFUNDS_MAIL      = 'airtel_money_failed_refunds_mail';
    const ICICI_NETBANKING_REFUNDS_MAIL         = 'icici_netbanking_refunds_mail';
    const ICICI_PAYLATER_REFUNDS_MAIL           = 'icici_paylater_refunds_mail';
    const INDIAN_BANK_NETBANKING_REFUNDS_MAIL   = 'indianbank_netbanking_refunds_mail';
    const CBI_NETBANKING_REFUNDS_MAIL           = 'cbi_netbanking_refunds_mail';
    const CANARA_NETBANKING_REFUNDS_MAIL        = 'canara_netbanking_refunds_mail';
    const FEDERAL_NETBANKING_REFUNDS_MAIL       = 'axis_netbanking_refunds_mail';
    const IDFC_NETBANKING_REFUNDS_MAIL          = 'idfc_netbanking_refunds_mail';
    const KOTAK_NETBANKING_REFUNDS_MAIL         = 'kotak_netbanking_refunds_mail';
    const RBL_NETBANKING_REFUNDS_MAIL           = 'rbl_netbanking_refunds_mail';
    const INDUSIND_NETBANKING_REFUNDS_MAIL      = 'indusind_netbanking_refunds_mail';
    const ISG_REFUNDS_MAIL                      = 'isg_refunds_mail';
    const AXIS_MIGS_FAILED_REFUNDS_MAIL         = 'axis_migs_failed_refunds_mail';
    const ICICI_FIRST_DATA_FAILED_REFUNDS_MAIL  = 'icici_firstdata_failed_refunds_mail';
    const HDFC_CYBERSOURCE_FAILED_REFUNDS_MAIL  = 'hdfc_cybersource_failed_refunds_mail';
    const HDFC_FSS_FAILED_REFUNDS_MAIL          = 'fss_failed_refunds_mail';
    const AXIS_CYBERSOURCE_FAILED_REFUNDS_MAIL  = 'axis_cybersource_failed_refunds_mail';
    const FAILED_REFUNDS_MAIL                   = 'failed_refunds_mail';
    const UPI_SBI_REFUNDS_MAIL                  = 'upi_sbi_refunds_mail';
    const UPI_AIRTEL_REFUNDS_MAIL               = 'upi_airtel_refunds_mail';
    const UPI_YESBANK_REFUNDS_MAIL              = 'upi_yesbank_refunds_mail';

    const PAYU_MONEY_REFUNDS_MAIL          = 'payu_money_refunds_mail';
    const ICICI_UPI_REFUNDS_MAIL           = 'icici_upi_refunds_mail';
    const ICICI_UPI_FAILED_REFUNDS_MAIL    = 'icici_upi_failed_refunds_mail';
    const MINDGATE_UPI_FAILED_REFUNDS_MAIL = 'mindgate_upi_failed_refunds_mail';
    const BATCH_IRCTC_REFUNDS_FILE         = 'batch_irctc_refunds_file';
    const BATCH_IRCTC_DELTA_REFUNDS_FILE   = 'batch_irctc_delta_refunds_file';
    const BATCH_IRCTC_SETTLEMENT_FILE      = 'batch_irctc_settlement_file';
    const BATCH_REFUNDS_FILE               = 'batch_refunds_file';
    const BATCH_PAYMENT_LINK_FILE          = 'batch_payment_link_file';
    const BATCH_AUTH_LINK_FILE             = 'batch_auth_link_file';
    const BATCH_CONTACT_FILE               = 'batch_contact_file';
    const BATCH_FUND_ACCOUNT_FILE          = 'batch_fund_account_file';
    const BATCH_PAYOUT_FILE                = 'batch_payout_file';
    const BATCH_TALLY_PAYOUT_FILE          = 'batch_tally_payout_file';
    const BATCH_MERCHANT_ONBOARDING_FILE   = 'batch_merchant_onboarding_file';

    const BATCH_BANKING_ACCOUNT_ACTIVATION_COMMENTS_FILE         = 'batch_banking_account_activation_comments_file';
    const BATCH_ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS_FILE      = 'batch_icici_lead_account_activation_comments_file';
    const BATCH_ICICI_BULK_UPLOAD_COMMENTS_FILE                  = 'batch_icici_bulk_upload_comments_file';

    const BATCH_ICICI_VIDEO_KYC_BULK_UPLOAD_FILE                 = 'batch_icici_video_kyc_bulk_upload_file';
    const BATCH_RBL_BULK_UPLOAD_COMMENTS_FILE                    = 'batch_rbl_bulk_upload_comments_file';

    const BATCH_PARTNER_SUBMERCHANT_INVITE_FILE = 'batch_partner_submerchant_invite_file';
    const BATCH_PARTNER_SUBMERCHANT_REFERRAL_INVITE_FILE = 'batch_partner_submerchant_referral_invite_file';

    const PAYOUT_APPROVAL                  = 'payout_approval';

    const PAYMENT_SUCCESSFUL               = 'payment_successful';
    const REFUND_SUCCESSFUL                = 'refund_successful';
    const PAYMENT_FAILED                   = 'payment_failed';
    const FAILED_TO_AUTHORIZED             = 'failed_to_authorized';
    const CARD_SAVING                      = 'card_saving';
    const REFUND_RRN_UPDATE                = 'refund_rrn_update';

    const PAYOUT_SUCCESSFUL                = 'payout_successful';
    const PAYOUT_SUCCESSFUL_CONTACT_MAIL   = 'payout_successful_contact_mail';

    const INVOICE                          = 'invoice';
    const ECOD                             = 'ecod';
    const LINK                             = 'link';

    const PAYMENT_LINK_PAYMENT_REQUEST     = 'payment_link_payment_request';

    const SUBSCRIPTION_AUTHENTICATED       = 'subscription_authenticated';
    const SUBSCRIPTION_CHARGED             = 'subscription_charged';
    const SUBSCRIPTION_PENDING             = 'subscription_pending';
    const SUBSCRIPTION_HALTED              = 'subscription_halted';
    const SUBSCRIPTION_CANCELLED           = 'subscription_cancelled';
    const SUBSCRIPTION_CARD_CHANGED        = 'subscription_card_changed';
    const SUBSCRIPTION_COMPLETED           = 'subscription_completed';
    const SUBSCRIPTION_INVOICE_CHARGED     = 'subscription_invoice_charged';

    const HDFC_EMANDATE_REGISTER_MAIL      = 'hdfc_emandate_register_mail';
    const HDFC_EMANDATE_DEBIT_MAIL         = 'hdfc_emandate_debit_mail';

    const SBI_EMANDATE_DEBIT_MAIL          = 'sbi_emandate_debit_mail';

    const AXIS_EMANDATE_DEBIT_MAIL         = 'axis_emandate_debit_mail';

    const ICICI_ENACH_DEBIT_MAIL           = 'icici_enach_debit_mail';
    const ICICI_NACH_REGISTER_MAIL         = 'icici_nach_register_mail';
    const ICICI_NACH_CANCEL_MAIL           = 'icici_nach_cancel_mail';

    const RBL_ENACH_DEBIT_MAIL             = 'rbl_enach_debit_mail';
    const RBL_ENACH_REGISTER_MAIL          = 'rbl_enach_register_mail';

    const CITI_NACH_REGISTER_MAIL          = 'citi_nach_register_mail';
    const CITI_NACH_DEBIT_MAIL             = 'citi_nach_debit_mail';

    const DAILY_FILE                       = 'daily_file';
    const DAILY_REPORT                     = 'daily_report';
    const FEE_CREDITS_ALERT                = 'fee_credits_alert';
    const AMOUNT_CREDITS_ALERT             = 'amount_credits_alert';
    const REFUND_CREDITS_ALERT             = 'refund_credits_alert';
    const CREDITS_ADDITION_MAIL            = 'credits_addition_mail';
    const RESERVE_BALANCE_ADDITION_MAIL    = 'reserve_balance_addition_mail';
    const BALANCE_THRESHOLD_ALERT          = 'balance_threshold_alert';
    const RX_LOW_BALANCE_ALERT             = 'rx_low_balance_alert';
    const IRCTC_REFUND_REPORT              = 'irctc_refund_report';
    const AUTH_REMINDER                    = 'auth_reminder';
    const HOLIDAY_NOTIFICATION             = 'holiday_notification';
    const WEBHOOK                          = 'webhook';

    const EMI_FILE                         = 'emi_file';
    const ICICI_EMI_REFUNDS_MAIL           = 'icici_emi_refunds_mail';

    const SCORECARD                        = 'scorecard';
    const BANKING_SCORECARD                = 'banking_scorecard';
    const CRITICAL_ERROR                   = 'critical_error';

    const ACCOUNT_CHANGED                  = 'account_changed';
    const ACCOUNT_CHANGE_REQUEST           = 'account_change_request';
    const FORGOT_PASSWORD                  = 'forgot_password';
    const ADMIN_CREATE                     = 'admin_create';
    const WELCOME                          = 'welcome';
    const ACCOUNT_ACTIVATED                = 'account_activated';
    const INSTANT_ACTIVATION               = 'instant_activation';
    const NEEDS_CLARIFICATION              = 'needs_clarification';
    const SUBMERCHANT_NC_STATUS_CHANGED    = 'submerchant_nc_status_changed';
    const PARTNER_WEEKLY_ACTIVATION_SUMMARY= 'partner_weekly_activation_summary';
    const SUB_MERCHANT_ADDED               = 'sub_merchant_added';
    const AFFILIATE_ADDED                  = 'affiliate_added';
    const ACCOUNT_REJECTED                 = 'account_rejected';

    const ICICI_FILES                      = 'icici_files';
    const CBI_FILES                        = 'cbi_files';

    const IBK_FILES                        = 'ibk_files';

    // Heimdall Email Tags
    const ADMIN_INVITE_MERCHANT            = 'admin_invite_merchant';
    const ADMIN_INVITE_PARTNER            = 'admin_invite_partner';

    const MERCHANT_INVITATION_MAIL         = 'merchant_invitation_mail';

    // Merchant Activation Email Tags
    const NOTIFY_ACTIVATION_SUBMISSION     = 'notify_activation_submission';
    const CONFIRM_ACTIVATION_SUBMISSION    = 'confirm_activation_submission';

    // Merchant website details update email tags
    const NOTIFY_WEBSITE_DETAIL_SUBMISSION = 'notify_website_detail_submission';

    // OAuth email tags
    const OAUTH_APP_AUTHORIZED             = 'oauth_app_authorized';

    // Merchant feature tags
    const FEATURE_ENABLED                  = 'feature_enabled';

    const UPDATE_CONTACT_NUMBER            = 'update_contact_number';

    // Merchant Request tags
    const MERCHANT_REQUEST_REJECTED            = 'merchant_request_rejected';
    const MERCHANT_REQUEST_NEEDS_CLARIFICATION = 'merchant_request_needs_clarification';

    // Dispute tags
    const DISPUTE_CREATED                       = 'dispute_created';
    const DISPUTES_CREATED_IN_BULK              = 'disputes_created_in_bulk';
    const DISPUTE_ACCEPTED_ADMIN                = 'dispute_accepted_admin';
    const DISPUTE_PRESENTMENT_RISK_OPS_REVIEW   = 'dispute_presentment_risk_ops_review';
    const DISPUTE_SUBMITTED_ADMIN               = 'files_submitted_admin';

    // Fraud Notification tags
    const FRAUD_NOTIFICATION_DOMAIN_MISMATCH = 'fraud_notification_domain_mismatch';
    const PAYMENT_FRAUD_BATCH_FILE_CREATED   = 'payment_fraud_batch_file_created';

    // ED Merchant Search
    const ED_MERCHANT_SEARCH_RESULT          =  'ed_merchant_search_result';

    // Daily Recon summary tags
    const DAILY_RECON_SUMMARY              = 'daily_recon_summary';

    // Transactions
    const TRANSACTION_CREATED              = 'transaction_created';

    const BANKING_ACCOUNT_STATUS_UPDATED   = 'banking_account_status_updated';

    const BANKING_ACCOUNT_STATUS_UPDATED_TO_SPOC   = 'banking_account_status_updated_to_spoc';

    const BANKING_ACCOUNT_REPORT   = 'banking_account_report';

    const EXPERIAN_REPORT                  = 'D2C_experian_csv_report';

    const BANKING_ACCOUNT_X_PRO_ACTIVATION = 'banking_account_x_pro_activation';
    const ICICI_CURRENT_ACCOUNT            = 'icici_current_account';

    const PARTNER_ON_BOARDED                            = 'partner_on_boarded';
    const NEGATIVE_BALANCE_THRESHOLD_ALERT              = 'negative_balance_threshold_alert';
    const BALANCE_NEGATIVE_ALERT                        = 'balance_negative_alert';
    const BALANCE_POSITIVE_ALERT                        = 'balance_positve_alert';
    const RESERVE_BALANCE_ACTIVATED                     = 'reserve_balance_activated';
    const NEGATIVE_BALANCE_BREACH_REMINDER              = 'negative_balance_breach_reminder';
    const COMMISSION_INVOICE                            = 'commission_invoice';

    // User Eamils
    const USER_ACCOUNT_LOCKED                           = 'user_account_locked';
    const USER_CONTACT_MOBILE_UPDATED                   = 'user_contact_mobile_updated';

    // Admin Emails
    const ADMIN_ACCOUNT_LOCKED                          = 'admin_account_locked';

    //Downtime Emails
    const DOWNTIME_NOTIFICATION                         = 'downtime_notification';

    //Merchant Invoice tags
    const MERCHANT_INVOICE_EXECUTION_SUMMARY            = 'merchant_invoice_execution_summary';

    //payout downtime notification
    const PAYOUT_DOWNTIME_NOTIFICATION                  = 'payout_downtime_notification';

    //international_enablement
    const INTERNATIONAL_ENABLEMENT                      = 'international_enablement';

    // Merchant Risk Alert tags
    const MERCHANT_RISK_ALERT_FUNDS_ON_HOLD             = 'merchant_risk_alert_funds_on_hold';

    const MERCHANT_BUSINESS_WEBSITE_ADD                 = 'merchant_business_website_add';

    const MERCHANT_BUSINESS_WEBSITE_UPDATE              = 'merchant_business_website_update';

    const WEBSITE_SELF_SERVE_REJECTION_REASON           = 'website_self_serve_rejection_reason';

    const INCREASE_TRANSACTION_LIMIT_REQUEST_APPROVE    = 'increase_transaction_limit_request_approve';

    const MERCHANT_ADD_ADDITIONAL_WEBSITE_SUCCESS       = 'merchant_add_additional_website_success';

    const UPDATE_REJECTION_REASON                       = 'update_rejection_reason';

    const DISABLE_NON_3DS_SUCCESS                       = 'disable_non_3ds_success';

    const ENABLE_NON_3DS_REQUEST_SUCCESS                = 'enable_non_3ds_request_success';

    const BANK_ACCOUNT_UPDATE_SUCCESS                   = 'merchant_bank_account_update_success';
    const BANK_ACCOUNT_UPDATE_UNDER_REVIEW              = 'merchant_bank_account_update_under_review';
    const BANK_ACCOUNT_UPDATE_SOH_UNDER_REVIEW          = 'merchant_bank_account_update_soh_under_review';
    const BANK_ACCOUNT_UPDATE_REJECTED                  = 'merchant_bank_account_update_rejected';
    const BANK_ACCOUNT_UPDATE_SOH_REJECTED              = 'merchant_bank_account_update_soh_rejected';
    const BANK_ACCOUNT_UPDATE_NEEDS_CLARIFICATION       = 'merchant_bank_account_update_needs_clarification';
    const BANK_ACCOUNT_UPDATE_SOH_NEEDS_CLARIFICATION   = 'merchant_bank_account_update_soh_needs_clarification';

    const IE_SUCCESSFUL                                 = 'ie_successful_all_products';
    const IE_UNDER_REVIEW                               = 'ie_under_review';
    const IE_SUCCESSFUL_PPLI                            = 'ie_successful_pp_pl_invoice';
    const IE_SUCCESSFUL_PG                              = 'ie_payment_gateway';
    const IE_REJECTED_CLARIFICATION_NOT_PROVIDED        = 'ie_rejected_clarification_not_provided';
    const IE_REJECTED_WEBSITE_DETAILS_INCOMPLETE        = 'ie_rejected_website_details_incomplete';
    const IE_REJECTED_BUSINESS_MODEL_MISMATCH           = 'ie_rejected_business_model_mismatch';
    const IE_REJECTED_INVALID_DOCUMENTS                 = 'ie_rejected_invalid_documents';
    const IE_REJECTED_RISK_REJECTION                    = 'ie_rejected_risk_rejection';
    const IE_REJECTED_MERCHANT_HIGH_CHARGEBACKS_FRAUD   = 'ie_rejected_merchant_high_chargebacks_fraud';
    const IE_REJECTED_DORMANT_MERCHANT                  = 'ie_rejected_dormant_merchant';
    const IE_REJECTED_RESTRICTED_BUSINESS               = 'ie_rejected_restricted_business';
    const IE_NEEDS_CLARIFICATION                        = 'ie_needs_clarification';

    // Mail tags for needs clarification on workflow
    const MERCHANT_CLARIFICATION_ON_BANK_ACCOUNT_WORKFLOW        =  'merchant_clarification_on_bank_account_workflow';
    const MERCHANT_CLARIFICATION_ON_TRANSACTION_LIMIT_WORKFLOW   =  'merchant_clarification_on_transaction_limit_workflow';
    const MERCHANT_CLARIFICATION_ON_WEBSITE_UPDATE_WORKFLOW      =  'merchant_clarification_on_website_update_workflow';
    const MERCHANT_CLARIFICATION_ON_WEBSITE_ADD_WORKFLOW         =  'merchant_clarification_on_website_add_workflow';
    const MERCHANT_CLARIFICATION_ON_GSTIN_UPDATE_WORKFLOW        =  'merchant_clarification_on_gstin_update_workflow';
    const MERCHANT_CLARIFICATION_ON_GSTIN_ADD_WORKFLOW           =  'merchant_clarification_on_gstin_add_workflow';
    const MERCHANT_CLARIFICATION_ON_ADDITIONAL_WEBSITE_WORKFLOW  =  'merchant_clarification_on_additional_website_workflow';

    const GSTIN_UPDATED_VALIDATION_SUCCESS              = 'gstin_updated_validation_success';
    const GSTIN_UPDATED_WORKFLOW_APPROVE                = 'gstin_updated_workflow_approve';
    const GSTIN_ADDED_VALIDATION_SUCCESS                = 'gstin_added_validation_success';
    const GSTIN_ADDED_WORKFLOW_APPROVE                  = 'gstin_added_workflow_approve';

    const BULK_API_KEYS_REGENERATE                      = 'bulk_api_keys_regenerate';

    const MERCHANT_BUSINESS_WEBSITE_UPDATE_REJECTION_REASON      = 'merchant_business_website_update_rejection_reason';

    const MERCHANT_BUSINESS_WEBSITE_ADD_REJECTION_REASON         = 'merchant_business_website_add_rejection_reason';

    const MERCHANT_INCREASE_TRANSACTION_LIMIT_REJECTION_REASON   = 'merchant_increase_transaction_limit_rejection_reason';

    const MERCHANT_GSTIN_UPDATE_REJECTION_REASON                 = 'merchant_gstin_update_rejection_reason';

    const MERCHANT_GSTIN_ADD_REJECTION_REASON                    = 'merchant_gstin_add_rejection_reason';

    const MERCHANT_BANK_ACCOUNT_UPDATE_REJECTION_REASON          = 'merchant_bank_account_update_rejection_reason';

    const MERCHANT_ADD_ADDITIONAL_WEBSITE_REJECTION_REASON       = 'merchant_add_additional_website_rejection_reason';

    const B2B_UPLOAD_INVOICE = 'b2b_upload_invoice';

    /**
     * Razorpay Trusted Business/Badge Constants
     */
    public const RTB_OPTIN_REQUEST = 'rtb_optin_request';
    public const RTB_OPTOUT_NOTIFY = 'rtb_optout_notify';
    public const RTB_WELCOME       = 'rtb_welcome';

    /**
     * Email tags that should respond to the mailgun failure webhook
     * @var array Email tags
     * @static
     */
    public static $setlNotifyTags = [
        self::KOTAK_BENEFICIARY_MAIL,
        self::HDFC_NETBANKING_REFUNDS_MAIL,
        self::AXIS_NETBANKING_REFUNDS_MAIL,
        self::ICICI_NETBANKING_REFUNDS_MAIL,
        self::INDIAN_BANK_NETBANKING_REFUNDS_MAIL,
        self::CBI_NETBANKING_REFUNDS_MAIL,
        self::RBL_NETBANKING_REFUNDS_MAIL,
        self::INDUSIND_NETBANKING_REFUNDS_MAIL,
    ];
}
