<?php

namespace RZP\Models\MerchantRiskAlert;

class Constants
{
    const RISK_CLARIFICATION                    = 'Risk Clarification';
    const MERCHANT_FOH_KEY                      = 'merchant_foh';
    const MERCHANT_INTERNATIONAL_KEY            = 'merchant_international';
    const MERCHANT_LIVE_KEY                     = 'merchant_live';
    const MERCHANT_SUSPENDED_KEY                = 'merchant_suspended';
    const MERCHANT_FOH_WORKFLOW_KEY             = 'merchant_foh_workflow_open';
    const MERCHANT_INTERNATIONAL_WORKFLOW_KEY   = 'merchant_disable_international_workflow_open';
    const MERCHANT_CREATED_AT                   = 'merchant_created_at';
    const MERCHANT_MIN_AOV                      = 'merchant_min_aov';
    const MERCHANT_MAX_AOV                      = 'merchant_max_aov';
    const MERCHANT_HAS_AOV                      = 'merchant_has_aov';

    const MERCHANT_LAST_UPDATED_WORKFLOW = 'merchant_last_updated_workflow_days';


    const MERCHANT_PAYMENTS_DISPUTED_GMV          = 'merchant_payments_disputed_gmv';
    const MERCHANT_PAYMENTS_DISPUTED_COUNT        = 'merchant_payments_disputed_count';
    const MERCHANT_PAYMENTS_HIGHER_DISPUTED_COUNT = 'merchant_payments_higher_disputed_count';

    const MERCHANT_ODS                       = 'merchant_ods';
    const MERCHANT_AUTHORIZED_LIFETIME_GMV            = 'merchant_authorized_lifetime_gmv';
    const MERCHANT_AUTHORIZED_LIFETIME_PAYMENTS_COUNT = 'merchant_authorized_lifetime_payment_count';

    const MERCHANT_RISK_SCORE_DRUID_KEY_MAPPING = [
        self::MERCHANT_AUTHORIZED_LIFETIME_GMV            => 'merchant_fact_authorized_gmv_ltd',
        self::MERCHANT_AUTHORIZED_LIFETIME_PAYMENTS_COUNT => 'merchant_fact_authorized_payment_count_ltd',
    ];

    const ACTION_MANUAL_FOH      = 'manual';
    const ACTION_AUTO_FOH        = 'auto';
    const ACTION_AUTO_REVIEW_FOH = 'auto_review';

    const MANUAL_FOH_TAG = 'manual_foh';
    const AUTO_FOH_TAG   = 'auto_foh';
    const MANAGED_MERCHANT_TAG = 'ras_managed_merchant';

    const MERCHANT_DETAIL_KEY = 'merchant_detail';

    const MERCHANT_KEY = 'merchant';

    const MUTEX_PREFIX = 'merchant_risk_alert_foh:';

    const FOH_WORKFLOW_EXECUTE_CONTROLLER = 'RZP\Http\Controllers\MerchantRiskAlertController@executeFOHWorkflow';

    // SMS template identifiers
    const FOH_SMS_GENERIC_CONFIRMATION_TEMPLATE                = 'sms.merchant_risk.generic.funds_on_hold.confirmation';
    const FOH_SMS_WEBSITE_CHECKER_CONFIRMATION_TEMPLATE        = 'sms.merchant_risk.website_checker.funds_on_hold.confirmation';
    const FOH_SMS_APP_CHECKER_CONFIRMATION_TEMPLATE            = 'sms.merchant_risk.app_checker.funds_on_hold.confirmation';
    const FOH_SMS_WEBSITE_CHECKER_NEEDS_CLARIFICATION_TEMPLATE = 'sms.merchant_risk.website_checker.funds_on_hold.needs_clarification';
    const FOH_SMS_APP_CHECKER_NEEDS_CLARIFICATION_TEMPLATE     = 'sms.merchant_risk.app_checker.funds_on_hold.needs_clarification';


    // Whatsapp template names
    const FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE_NAME                = 'merchant_risk.generic.funds_on_hold.confirmation';
    const FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME = 'merchant_risk.website_checker.funds_on_hold.needs_clarification';
    const FOH_APP_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME     = 'merchant_risk.app_checker.funds_on_hold.needs_clarification';
    const FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME        = 'merchant_risk.website_checker.funds_on_hold.confirmation';
    const FOH_APP_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME            = 'merchant_risk.app_checker.funds_on_hold.confirmation';

    // WhatsApp templates
    const FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE                = 'Hi {merchantName}, we regret to inform you that your settlements are under review due to risk alert for non-compliance with regulatory guidelines as set by our partner banks. Please check your email ID registered with Razorpay and help us with clarification to re-enable settlements';
    const FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE = 'We have observed that your registered website(s) is(are) no longer operating at the moment. Please check your registered email ID with subject: Razorpay Account Website Clarification: {merchantName} | {merchantId} for more details.';
    const FOH_APP_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE     = 'We have observed that your registered mobile application(s) is(are) no longer operating at the moment. Please check your registered email ID with subject: Razorpay Account Mobile Application Clarification: {merchantName} | {merchantId} for more details.';
    const FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE        = 'We have put your settlement under review as we observed your registered website(s) is(are) no longer live. Please check your registered email ID for an email with subject: Razorpay Account Review: {merchantName} | {merchantId} and help us with clarification to re-enable settlements.';
    const FOH_APP_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE            = 'We have put your settlement under review as we observed your mobile application(s) is(are) no longer live. Please check your registered email ID for an email with subject: Razorpay Account Review: {merchantName} | {merchantId} and help us with clarification to re-enable settlements.';

    // Email template identifiers
    const FOH_GENERIC_CONFIRMATION_MAIL_TPL                = 'emails.merchant.risk.generic.funds_on_hold.confirmation';
    const FOH_ADMIN_TRIGGER_NEEDS_CLARIFICATION_TPL        = 'emails.merchant.risk.generic.admin_trigger_needs_clarification';
    const FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL = 'emails.merchant.risk.website_checker.funds_on_hold.needs_clarification';
    const FOH_APP_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL     = 'emails.merchant.risk.app_checker.funds_on_hold.needs_clarification';
    const FOH_WEBSITE_CHECKER_CONFIRMATION_MAIL_TPL        = 'emails.merchant.risk.website_checker.funds_on_hold.confirmation';
    const FOH_APP_CHECKER_CONFIRMATION_MAIL_TPL            = 'emails.merchant.risk.app_checker.funds_on_hold.confirmation';

    // Email subjects
    const FOH_GENERIC_CONFIRMATION_MAIL_SUBJECT                = 'Razorpay Account Review: {merchant_name} | {merchant_id} | Funds under Review';
    const FOH_ADMIN_TRIGGER_NEEDS_CLARIFICATION_SUBJECT        = 'Razorpay Account Review: {merchant_name} | {merchant_id} | Risk Clarification';
    const FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_MAIL_SUBJECT = 'Razorpay Account Website Clarification: {merchant_name} | {merchant_id}';
    const FOH_APP_CHECKER_NEEDS_CLARIFICATION_MAIL_SUBJECT = 'Razorpay Account Website Clarification: {merchant_name} | {merchant_id}';

    // Email maps
    const FOH_HEALTH_CHECKER_CONFIRMATION_MAIL_TPL = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_WEBSITE_CHECKER_CONFIRMATION_MAIL_TPL,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_APP_CHECKER_CONFIRMATION_MAIL_TPL,
    ];
    const FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_APP_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL,
    ];
    const FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_MAIL_SUBJECT = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_MAIL_SUBJECT,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_APP_CHECKER_NEEDS_CLARIFICATION_MAIL_SUBJECT,
    ];

    // Sms Maps
    const FOH_SMS_HEALTH_CHECKER_CONFIRMATION_TEMPLATE = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_SMS_WEBSITE_CHECKER_CONFIRMATION_TEMPLATE,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_SMS_APP_CHECKER_CONFIRMATION_TEMPLATE,
    ];
    const FOH_SMS_HEALTH_CHECKER_NEEDS_CLARIFICATION_TEMPLATE = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_SMS_WEBSITE_CHECKER_NEEDS_CLARIFICATION_TEMPLATE,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_SMS_APP_CHECKER_NEEDS_CLARIFICATION_TEMPLATE,
    ];

    // Whatsapp Maps
    const FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_APP_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE,
    ];
    const FOH_HEALTH_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_APP_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE,
    ];
    const FOH_HEALTH_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_APP_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME,
    ];
    const FOH_HEALTH_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME = [
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => self::FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME,
        self::RAS_TRIGGER_REASON_APP_CHECKER        => self::FOH_APP_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME,
    ];

    // notification type
    const FOH_NC_NOTIFICATION           = 'needs_clarification';
    const FOH_CONFIRMATION_NOTIFICATION = 'confirmation';

    // ras trigger reason
    const RAS_TRIGGER_REASON_KEY = 'ras_trigger_reason';

    const RAS_TRIGGER_REASON_WEBSITE_CHECKER = 'website_checker';
    const RAS_TRIGGER_REASON_APP_CHECKER = 'app_checker';
    const RAS_TRIGGER_REASON_GENERIC         = 'generic';
    const RAS_TRIGGER_REASON_NC_FLOW         = 'nc_flow';

    const  RAS_TRIGGER_REASONS_HEALTH_CHECKER = [
        self::RAS_TRIGGER_REASON_APP_CHECKER,
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER,
    ];

    // Mobile signup templates
    // Generic
    const FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE_MOBILE_SIGNUP          = 'Hi {merchantName}, we regret to inform you that payment settlements to your Razorpay account are under review due to a risk alert raised by our banking partners. Please check link {supportTicketLink} and help us with the required clarification to re-enable settlements.';
    const FOH_GENERIC_CONFIRMATION_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP     = 'whatsapp_risk_foh_confirmation_mobile_signup';
    const FOH_GENERIC_CONFIRMATION_SMS_TEMPLATE_MOBILE_SIGNUP               = 'sms.risk.foh_confirmation_mobile_signup';
    const RAS_NC_WHATSAPP_TEMPLATE                                          = 'Hi {merchant name}, we have received a risk alert against your Razorpay account from our banking partners. To resolve this issue on priority, please check link {supportTicketLink} for more details and help us with the required clarifications.';
    const RAS_NC_WHATSAPP_TEMPLATE_NAME                                     = 'whatsapp_risk_RAS_NC_mobile_signup';
    const RAS_NC_SMS_TEMPLATE                                               = 'sms.risk.RAS_NC_mobile_signup';
    // Website Checker
    const FOH_SMS_WEBSITE_CHECKER_CONFIRMATION_TEMPLATE_MOBILE_SIGNUP                   = 'sms.merchant_risk.website_checker.funds_on_hold.confirmation';
    const FOH_SMS_WEBSITE_CHECKER_NEEDS_CLARIFICATION_TEMPLATE_MOBILE_SIGNUP            = 'sms.merchant_risk.website_checker.funds_on_hold.needs_clarification';
    const FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP  = 'merchant_risk.website_checker.funds_on_hold.needs_clarification';
    const FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_NAME_MOBILE_SIGNUP         = 'merchant_risk.website_checker.funds_on_hold.confirmation';
    const FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_WHATSAPP_TEMPLATE_MOBILE_SIGNUP       = 'We have observed that your registered website(s) is(are) no longer operating at the moment. Please check your registered email ID with subject: Razorpay Account Website Clarification: {merchantName} | {merchantId} for more details.';
    const FOH_WEBSITE_CHECKER_CONFIRMATION_WHATSAPP_TEMPLATE_MOBILE_SIGNUP              = 'We have put your settlement under review as we observed your registered website(s) is(are) no longer live. Please check your registered email ID for an email with subject: Razorpay Account Review: {merchantName} | {merchantId} and help us with clarification to re-enable settlements.';

    // Fd Sub Category
    const FD_SUB_CATEGORY_NEED_CLARIFICATION = 'Need Clarification';
    const FD_SUB_CATEGORY_FUNDS_ON_HOLD      = 'Funds on hold';
    const FD_SUB_CATEGORY_FRAUD_ALERTS       = 'Fraud alerts';

    // FD RAS Tags
    const FD_TAG_RAS_FOH        = 'RAS_FOH';
    const FD_TAG_RAS_REASON_FOH = 'RAS_%s_FOH';

    // Workflow fd tags
    const RAS_FD_TICKET_TAG_PREFIX = '%s_fd_ticket_id_';
    const RAS_FD_TICKET_ID_TAG_FMT = self::RAS_FD_TICKET_TAG_PREFIX . '%s';

    const REDIS_REMINDER_MAP_NAME = [
        self::RAS_TRIGGER_REASON_APP_CHECKER     => 'risk:app_checker:reminder_map',
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER => 'risk:web_checker:reminder_map',
    ];

    const REDIS_DEDUPE_SIGNUP_CHECKER_MAP = 'ras:signup_checker_fraud';

    const HEALTH_CHECKER_NC_DAYS_TO_FOH          = 7;
    const HEALTH_CHECKER_NC_REMINDER_DAYS_TO_FOH = 5;

    const FD_TICKET_ID_KEY          = 'fd_ticket_id';
    const WORKFLOW_ACTION_INPUT_KEY = 'workflow_action_input';

    const RAS_NC_OUTBOUND_EMAIL_FRESHDESK_TICKET_URL_FORMAT = 'RAS NC Outbound email freshdesk ticket url: https://razorpay-ind.freshdesk.com/a/tickets/%s'; //hardcoding url as its the only instance being used
    const RAS_NC_WORKFLOW_CACHE_KEY                         = 'ras_nc_workflow_key_%s';
    const RAS_NC_WORKFLOW_CACHE_TTL                         = 120 * (60 * 60 * 24); // 120days

    const CREATE_RULE_URL = '/twirp/rzp.merchant_risk_alerts.rule.v1.RuleService/Create';
    const UPDATE_RULE_URL = '/twirp/rzp.merchant_risk_alerts.rule.v1.RuleService/Update';
    const DELETE_RULE_URL = '/twirp/rzp.merchant_risk_alerts.rule.v1.RuleService/Delete';

    const FETCH_MAPPING_URL = '/twirp/rzp.merchant_risk_alerts.needs_clarification.v1.NeedsClarificationService/FetchTeamMapping';

    const RAS_SIGN_UP_CHECKER_POST_ACTION_FEATURE_FLAG = 'merchants_risk_trigger_sign_up_checker_post_actions';

    const QUERY_EXECUTION_TIME                         = 'query_execution_time';

    const RAS_RULE_ENTITY                              = 'ras_rule_entity';

    const RAS_RULES_CREATE_PAYLOAD                     = 'ras_rules_create_payload';

    const RAS_RULES_UPDATE_PAYLOAD                     = 'ras_rules_update_payload';

    const RAS_RULES_DELETE_PAYLOAD                     = 'ras_rules_delete_payload';

    const RAS_RULES_ID                                 = 'ras_rules_id';
}

