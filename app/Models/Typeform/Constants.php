<?php

namespace RZP\Models\Typeform;

use RZP\Models\Admin\Permission;
use RZP\Notifications\Dashboard\Events as DashboardEvents;

class Constants
{
    const NEW         = 'new';
    const DETAIL_URL  = 'detail_url';
    const APPROVED    = 'approved';
    const PRODUCTS    = 'products';
    
    const REJECTION_TAG_PREFIX                          = 'ie_rejection_tag_';
    const REJECTION_REASON_PREFIX                       = 'ie_rejection_reason_';
    const INTERNATIONAL_ENABLEMENT_REQUEST_ID_PREFIX    = 'ie_request_id_';
    const INTERNATIONAL_ENABLEMENT_REQUEST_HAS_SIBLINGS = 'ie_request_has_siblings';

    const CSV_SEPERATOR = ',';

    const REJECTION_REASON_KEY = 'rejection_reason';
    const REJECTION_TAGS_KEY   = 'rejection_tags';

    const REJECT_REASON_MERCHANT_LOOKS_RISKY    = 'merchant_looks_risky';
    const REJECT_REASON_MERCHANT_LOOKS_SAFE     = 'merchant_looks_safe';
    const REJECT_REASON_MERCHANT_NOT_REGISTERED = 'merchant_not_registered';

    const REJECT_REASON_MERCHANT_CLARIFICATION_NOT_PROVIDED     = 'clarification_not_provided';
    const REJECT_REASON_MERCHANT_WEBSITE_DETAIL_INCOMPLETE      = 'website_detail_incomplete';
    const REJECT_REASON_MERCHANT_RISK_REJECTION                 = 'risk_rejection';
    const REJECT_REASON_MERCHANT_HIGH_CHARGEBACK_FRAUD_PRESENT  = 'merchant_high_chargebacks_fraud_present';
    const REJECT_REASON_MERCHANT_BUSINESS_MODEL_MISMATCH        = 'business_model_mismatch';
    const REJECT_REASON_MERCHANT_INVALID_DOCUMENTS              = 'invalid_documents';
    const REJECT_REASON_MERCHANT_DORMANT_MERCHANT               = 'dormant_merchant';
    const REJECT_REASON_MERCHANT_RESTRICTED_BUSINESS            = 'restricted_business';
    
    const REJECTION_TAG_CHARGEBACK_FRAUD_PRESENT          = 'merchant_high_chargebacks_fraud_present';
    const REJECTION_TAG_WEBSITE_INCOMPLETE                = 'website_incomplete';
    const REJECTION_TAG_INADEQUATE_DOCUMENTS              = 'inadequate_documents';
    const REJECTION_TAG_BUSINESS_USE_CASE_UNDEFINED       = 'business_use_case_undefined';
    const REJECTION_TAG_GOODS_LOGISTICS_PARTNER_MISSING   = 'goods_logistics_partner_missing';
    const REJECTION_TAG_WEBSITE_QUICKLINKS_UNSATISFACTORY = 'website_quicklinks_unsatisfactory';
    const REJECTION_TAG_CATEGORY_INTERNATIONAL_INELIGIBLE = 'merchant_category_international_ineligible';
    const REJECTION_TAG_BUSINESS_MODEL_MISMATCH           = 'business_model_mismatch';

    const REJECTION_REASONS = [
        self::REJECT_REASON_MERCHANT_LOOKS_RISKY,
        self::REJECT_REASON_MERCHANT_LOOKS_SAFE,
        self::REJECT_REASON_MERCHANT_NOT_REGISTERED,
    ];

    const REJECTION_REASONS_V2 = [
        self::REJECT_REASON_MERCHANT_CLARIFICATION_NOT_PROVIDED,
        self::REJECT_REASON_MERCHANT_WEBSITE_DETAIL_INCOMPLETE,
        self::REJECT_REASON_MERCHANT_RISK_REJECTION,
        self::REJECT_REASON_MERCHANT_HIGH_CHARGEBACK_FRAUD_PRESENT,
        self::REJECT_REASON_MERCHANT_BUSINESS_MODEL_MISMATCH,
        self::REJECT_REASON_MERCHANT_INVALID_DOCUMENTS,
        self::REJECT_REASON_MERCHANT_DORMANT_MERCHANT,
        self::REJECT_REASON_MERCHANT_RESTRICTED_BUSINESS,
    ];
    
    const REJECTION_TAGS = [
        self::REJECTION_TAG_CHARGEBACK_FRAUD_PRESENT,
        self::REJECTION_TAG_WEBSITE_INCOMPLETE,
        self::REJECTION_TAG_INADEQUATE_DOCUMENTS,
        self::REJECTION_TAG_BUSINESS_USE_CASE_UNDEFINED,
        self::REJECTION_TAG_GOODS_LOGISTICS_PARTNER_MISSING,
        self::REJECTION_TAG_WEBSITE_QUICKLINKS_UNSATISFACTORY,
        self::REJECTION_TAG_CATEGORY_INTERNATIONAL_INELIGIBLE,
        self::REJECTION_TAG_BUSINESS_MODEL_MISMATCH,
    ];

    const APPROVED_PRODUCT_COUNT_VS_IE_SUCCESS_EVENT = [
        1 => DashboardEvents::IE_SUCCESSFUL_PG,
        3 => DashboardEvents::IE_SUCCESSFUL_PPLI,
        4 => DashboardEvents::IE_SUCCESSFUL,
    ];

    const AUTO_MERCHANT_NOTIFICATION_ENABLED  = 'auto_merchant_notification_enabled';
    const AUTO_MERCHANT_NOTIFICATION_DISABLED = 'auto_merchant_notification_disabled';

    const MERCHANT_KEY = 'merchant';

    const EMAIL_SIGNUP  = 'EMAIL_SIGNUP';
    const MOBILE_SIGNUP = 'MOBILE_SIGNUP';

    const SMS_INTERNATIONAL_ENABLEMENT_APPROVED_TPL = [
        self::MOBILE_SIGNUP => self::SMS_INTL_ENABLEMENT_APPROVED_MOBILE_SIGNUP,
        self::EMAIL_SIGNUP  => self::SMS_INTL_ENABLEMENT_APPROVED_EMAIL_SIGNUP,
    ];

    const SMS_INTERNATIONAL_ENABLEMENT_REJECTED_TPL = [
        self::MOBILE_SIGNUP => self::SMS_INTL_ENABLEMENT_REJECT_MOBILE_SIGNUP,
        self::EMAIL_SIGNUP  => self::SMS_INTL_ENABLEMENT_REJECT_EMAIL_SIGNUP,
    ];

    const SMS_INTL_ENABLEMENT_APPROVED_EMAIL_SIGNUP = 'sms.internation_enablement.approved';
    const SMS_INTL_ENABLEMENT_REJECT_EMAIL_SIGNUP   = 'sms.internation_enablement.rejected';

    const SMS_INTL_ENABLEMENT_APPROVED_MOBILE_SIGNUP = 'sms.risk.international_acceptance_mobile_signup';
    const SMS_INTL_ENABLEMENT_REJECT_MOBILE_SIGNUP   = 'sms.risk.international_rejection_mobile_signup';

    const WHATSAPP_INTL_ENABLEMENT_APPROVED_EMAIL_SIGNUP_TPL         = "Request to enable 'International Payments' for MID - {merchant_id} in the name of M/s. {business_name} held with Razorpay has been evaluated and approved. Please check your registered email for more details";
    const WHATSAPP_INTL_ENABLEMENT_REJECT_EMAIL_SIGNUP_TPL           = "We regret to inform you that the request to enable 'International Payments' for MID - {merchant_id} in the name of M/s. {business_name} held with Razorpay has not been approved by our banking partners. Please check your registered email for more details";
    const WHATSAPP_INTL_ENABLEMENT_APPROVED_EMAIL_SIGNUP_TPL_NAME    = 'International_payments.approved';
    const WHATSAPP_INTL_ENABLEMENT_REJECT_EMAIL_SIGNUP_TPL_NAME      = 'International_payments.rejected';

    const WHATSAPP_INTL_ENABLEMENT_APPROVED_MOBILE_SIGNUP_TPL         = "Hi {merchantName}, your request to enable international payments for your Razorpay account for MID - {merchant_id} in the name of M/s. {business_name} has been evaluated and approved. Please check link {supportTicketLink} for more details";
    const WHATSAPP_INTL_ENABLEMENT_REJECT_MOBILE_SIGNUP_TPL           = "Hi {merchantName}, we regret to inform you that your request to enable international payments for {merchant_id} in the name of M/s. {business_name} held with Razorpay has not been approved by our banking partners. Please check link {supportTicketLink} for more details";
    const WHATSAPP_INTL_ENABLEMENT_APPROVED_MOBILE_SIGNUP_TPL_NAME    = 'whatsapp_risk_international_acceptance_mobile_signup';
    const WHATSAPP_INTL_ENABLEMENT_REJECT_MOBILE_SIGNUP_TPL_NAME      = 'whatsapp_risk_international_rejection_mobile_signup';

    const WHATSAPP_INTL_ENABLEMENT_REMINDER      =  " Hi {merchant_name} ! You're 1 step away from unlocking 30% more sales for {business_name} by activating international payments - finish it now!\nRegards,\nTeam Razorpay";
    const WHATSAPP_INTL_ENABLEMENT_REMINDER_NAME = 'whatsapp_international_enablement_reminder2';
    const SMS_INTL_ENABLEMENT_REMINDER  =   'sms.dashboard.international_enablement_reminder';

    const WHATSAPP_INTERNATIONAL_ENABLEMENT_APPROVED_TPL = [
      self::EMAIL_SIGNUP        => self::WHATSAPP_INTL_ENABLEMENT_APPROVED_EMAIL_SIGNUP_TPL,
      self::MOBILE_SIGNUP       => self::WHATSAPP_INTL_ENABLEMENT_APPROVED_MOBILE_SIGNUP_TPL,
    ];

    const WHATSAPP_INTERNATIONAL_ENABLEMENT_REJECTED_TPL = [
        self::EMAIL_SIGNUP        => self::WHATSAPP_INTL_ENABLEMENT_REJECT_EMAIL_SIGNUP_TPL,
        self::MOBILE_SIGNUP       => self::WHATSAPP_INTL_ENABLEMENT_REJECT_MOBILE_SIGNUP_TPL,
    ];

    const WHATSAPP_INTERNATIONAL_ENABLEMENT_APPROVED_TPL_NAME = [
        self::EMAIL_SIGNUP        => self::WHATSAPP_INTL_ENABLEMENT_APPROVED_EMAIL_SIGNUP_TPL_NAME,
        self::MOBILE_SIGNUP       => self::WHATSAPP_INTL_ENABLEMENT_APPROVED_MOBILE_SIGNUP_TPL_NAME,
    ];
    const WHATSAPP_INTERNATIONAL_ENABLEMENT_REJECTED_TPL_NAME = [
        self::EMAIL_SIGNUP        => self::WHATSAPP_INTL_ENABLEMENT_REJECT_EMAIL_SIGNUP_TPL_NAME,
        self::MOBILE_SIGNUP       => self::WHATSAPP_INTL_ENABLEMENT_REJECT_MOBILE_SIGNUP_TPL_NAME,
    ];

    const REJECTION_REASON_PRIORITY = [
        self::REJECT_REASON_MERCHANT_LOOKS_SAFE     => 3,
        self::REJECT_REASON_MERCHANT_LOOKS_RISKY    => 2,
        self::REJECT_REASON_MERCHANT_NOT_REGISTERED => 1,
    ];

    const REJECTED_MAIL_VIEW_TPL = [
        self::REJECT_REASON_MERCHANT_LOOKS_RISKY    => 'emails.merchant.international_enablement_request.rejected_merchant_looks_risky',
        self::REJECT_REASON_MERCHANT_LOOKS_SAFE     => 'emails.merchant.international_enablement_request.rejected_merchant_looks_safe',
        self::REJECT_REASON_MERCHANT_NOT_REGISTERED => 'emails.merchant.international_enablement_request.rejected_unregistered_merchant',
    ];

    const ACCEPTED_MAIL_VIEW_TPL = 'emails.merchant.international_enablement_request.accepted';

    const IE_MAIL_SUBJECT = 'Razorpay | International Payment Acceptance Request';

    // FD tags
    const FD_TAG_IE_AUTO_MAILER = 'intl_auto_mailer';
    const FD_TAG_IE_SIBLING     = 'intl_sibling';

    const FD_TAG_IE_APPROVED          = 'intl_approved';
    const FD_TAG_IE_REJECTED          = 'intl_rejected';
    const FD_TAG_IE_APPROVED_REJECTED = 'intl_approved_rejected';

    const FD_TAG_IE_REJECTED_SAFE         = 'intl_rejected_safe';
    const FD_TAG_IE_REJECTED_RISKY        = 'intl_rejected_risky';
    const FD_TAG_IE_REJECTED_UNREGISTERED = 'intl_rejected_unreg';

    const FD_IE_REJECTION_TAGS = [
        self::REJECT_REASON_MERCHANT_LOOKS_RISKY    => self::FD_TAG_IE_REJECTED_RISKY,
        self::REJECT_REASON_MERCHANT_LOOKS_SAFE     => self::FD_TAG_IE_REJECTED_SAFE,
        self::REJECT_REASON_MERCHANT_NOT_REGISTERED => self::FD_TAG_IE_REJECTED_UNREGISTERED,
    ];

    // NOTE: We are picking a static mapping, as from the merchants perspective these are teh only possible combinations
    // In case Pages, Links and Invoices can be vouched for seperately, then this needs to change
    const PERMISSION_PRODUCT_MAPPING = [
        Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL      => 'Payment Gateway',
        Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL => 'Payment Pages, Links and Invoices',
    ];

    public static function isValidRejectionReasonV2(string $rejectionReason)
    {
        return in_array($rejectionReason, self::REJECTION_REASONS_V2) === true;
    }
    
    public static function isValidRejectionReason(string $rejectionReason)
    {
        return in_array($rejectionReason, self::REJECTION_REASONS) === true;
    }

    public static function isValidRejectionTag(string $rejectionTag)
    {
        return in_array($rejectionTag, self::REJECTION_TAGS) === true;
    }
}
