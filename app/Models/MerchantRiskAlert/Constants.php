<?php

namespace RZP\Models\MerchantRiskAlert;

class Constants
{
    const MERCHANT_FOH_KEY          = 'merchant_foh';
    const MERCHANT_FOH_WORKFLOW_KEY = 'merchant_foh_workflow_open';
    const MERCHANT_CREATED_AT       = 'merchant_created_at';
    const MERCHANT_MIN_AOV          = 'merchant_min_aov';
    const MERCHANT_MAX_AOV          = 'merchant_max_aov';
    const MERCHANT_HAS_AOV          = 'merchant_has_aov';

    const MERCHANT_PAYMENTS_DISPUTED_GMV          = 'merchant_payments_disputed_gmv';
    const MERCHANT_PAYMENTS_DISPUTED_COUNT        = 'merchant_payments_disputed_count';
    const MERCHANT_PAYMENTS_HIGHER_DISPUTED_COUNT = 'merchant_payments_higher_disputed_count';

    const ACTION_MANUAL_FOH      = 'manual';
    const ACTION_AUTO_FOH        = 'auto';
    const ACTION_AUTO_REVIEW_FOH = 'auto_review';

    const MANUAL_FOH_TAG = 'manual_foh';
    const AUTO_FOH_TAG   = 'auto_foh';

    const MERCHANT_DETAIL_KEY = 'merchant_detail';

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
    const FOH_WEBSITE_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL = 'emails.merchant.risk.website_checker.funds_on_hold.needs_clarification';
    const FOH_APP_CHECKER_NEEDS_CLARIFICATION_MAIL_TPL     = 'emails.merchant.risk.app_checker.funds_on_hold.needs_clarification';
    const FOH_WEBSITE_CHECKER_CONFIRMATION_MAIL_TPL        = 'emails.merchant.risk.website_checker.funds_on_hold.confirmation';
    const FOH_APP_CHECKER_CONFIRMATION_MAIL_TPL            = 'emails.merchant.risk.app_checker.funds_on_hold.confirmation';

    // Email subjects
    const FOH_GENERIC_CONFIRMATION_MAIL_SUBJECT                = 'Razorpay Account Review: {merchant_name} | {merchant_id} | Funds under Review';
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

    const  RAS_TRIGGER_REASONS_HEALTH_CHECKER = [
        self::RAS_TRIGGER_REASON_APP_CHECKER,
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER,
    ];

    // Fd Sub Category
    const FD_SUB_CATEGORY_NEED_CLARIFICATION = 'Need Clarification';
    const FD_SUB_CATEGORY_FUNDS_ON_HOLD      = 'Funds on hold';

    // FD RAS Tags
    const FD_TAG_RAS_FOH        = 'RAS_FOH';
    const FD_TAG_RAS_REASON_FOH = 'RAS_%s_FOH';

    // Workflow fd tags
    const RAS_FD_TICKET_TAG_PREFIX = '%s_fd_ticket_id_';
    const RAS_FD_TICKET_ID_TAG_FMT = self::RAS_FD_TICKET_TAG_PREFIX . '%s';

    const REDIS_REMINDER_MAP_NAME = [
        self::RAS_TRIGGER_REASON_APP_CHECKER        => 'risk:app_checker:reminder_map',
        self::RAS_TRIGGER_REASON_WEBSITE_CHECKER    => 'risk:web_checker:reminder_map',
    ];

    const HEALTH_CHECKER_NC_DAYS_TO_FOH          = 7;
    const HEALTH_CHECKER_NC_REMINDER_DAYS_TO_FOH = 5;

    const FD_TICKET_ID_KEY          = 'fd_ticket_id';
    const WORKFLOW_ACTION_INPUT_KEY = 'workflow_action_input';
}
