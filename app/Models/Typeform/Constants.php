<?php

namespace RZP\Models\Typeform;

use RZP\Models\Admin\Permission;

class Constants
{
    const REJECTION_TAG_PREFIX                       = 'ie_rejection_tag_';
    const REJECTION_REASON_PREFIX                    = 'ie_rejection_reason_';
    const INTERNATIONAL_ENABLEMENT_REQUEST_ID_PREFIX = 'ie_request_id_';

    const CSV_SEPERATOR = ',';

    const REJECTION_REASON_KEY = 'rejection_reason';
    const REJECTION_TAGS_KEY   = 'rejection_tags';

    const REJECT_REASON_MERCHANT_LOOKS_RISKY    = 'merchant_looks_risky';
    const REJECT_REASON_MERCHANT_LOOKS_SAFE     = 'merchant_looks_safe';
    const REJECT_REASON_MERCHANT_NOT_REGISTERED = 'merchant_not_registered';

    const REJECTION_TAG_CHARGEBACK_PRESENT                = 'merchant_high_chargebacks_present';
    const REJECTION_TAG_WEBSITE_INCOMPLETE                = 'website_incomplete';
    const REJECTION_TAG_INADEQUATE_DOCUMENTS              = 'inadequate_documents';
    const REJECTION_TAG_BUSINESS_USE_CASE_UNDEFINED       = 'business_use_case_undefined';
    const REJECTION_TAG_GOODS_LOGISTICS_PARTNER_MISSING   = 'goods_logistics_partner_missing';
    const REJECTION_TAG_WEBSITE_QUICKLINKS_UNSATISFACTORY = 'website_quicklinks_unsatisfactory';
    const REJECTION_TAG_CATEGORY_INTERNATIONAL_INELIGIBLE = 'merchant_category_international_ineligible';

    const REJECTION_REASONS = [
        self::REJECT_REASON_MERCHANT_LOOKS_RISKY,
        self::REJECT_REASON_MERCHANT_LOOKS_SAFE,
        self::REJECT_REASON_MERCHANT_NOT_REGISTERED,
    ];

    const REJECTION_TAGS = [
        self::REJECTION_TAG_CHARGEBACK_PRESENT,
        self::REJECTION_TAG_WEBSITE_INCOMPLETE,
        self::REJECTION_TAG_INADEQUATE_DOCUMENTS,
        self::REJECTION_TAG_BUSINESS_USE_CASE_UNDEFINED,
        self::REJECTION_TAG_GOODS_LOGISTICS_PARTNER_MISSING,
        self::REJECTION_TAG_WEBSITE_QUICKLINKS_UNSATISFACTORY,
        self::REJECTION_TAG_CATEGORY_INTERNATIONAL_INELIGIBLE,
    ];

    const INTERNATIONAL_ENABLEMENT_NOTIFICATION_FEATURE_FLAG                = 'international_enablement_notification';
    const INTERNATIONAL_ENABLEMENT_NOTIFICATION_FEATURE_FLAG_NOTIFY_VARIANT = 'notify';

    const AUTO_MERCHANT_NOTIFICATION_ENABLED  = 'auto_merchant_notification_enabled';
    const AUTO_MERCHANT_NOTIFICATION_DISABLED = 'auto_merchant_notification_disabled';

    const MERCHANT_KEY = 'merchant';

    const SMS_INTERNATIONAL_ENABLEMENT_APPROVED_TPL = 'sms.internation_enablement.approved';
    const SMS_INTERNATIONAL_ENABLEMENT_REJECTED_TPL = 'sms.internation_enablement.rejected';

    const WHATSAPP_INTERNATIONAL_ENABLEMENT_APPROVED_TPL = "Request to enable 'International Payments' for MID - {merchant_id} in the name of M/s. {business_name} held with Razorpay has been evaluated and approved. Please check your registered email for more details";
    const WHATSAPP_INTERNATIONAL_ENABLEMENT_REJECTED_TPL = "We regret to inform you that the request to enable 'International Payments' for MID - {merchant_id} in the name of M/s. {business_name} held with Razorpay has not been approved by our banking partners. Please check your registered email for more details";

    const WHATSAPP_INTERNATIONAL_ENABLEMENT_APPROVED_TPL_NAME = 'International_payments.approved';
    const WHATSAPP_INTERNATIONAL_ENABLEMENT_REJECTED_TPL_NAME = 'International_payments.rejected';

    const REJECTION_REASON_PRIORITY = [
        self::REJECT_REASON_MERCHANT_LOOKS_SAFE     => 3,
        self::REJECT_REASON_MERCHANT_LOOKS_RISKY    => 2,
        self::REJECT_REASON_MERCHANT_NOT_REGISTERED => 1,
    ];

    const REJECTED_MAILABLE_CLASS = [
        self::REJECT_REASON_MERCHANT_LOOKS_RISKY    => \RZP\Mail\Merchant\InternationalEnablement\RejectedRiskyMerchant::class,
        self::REJECT_REASON_MERCHANT_LOOKS_SAFE     => \RZP\Mail\Merchant\InternationalEnablement\RejectedSafeMerchant::class,
        self::REJECT_REASON_MERCHANT_NOT_REGISTERED => \RZP\Mail\Merchant\InternationalEnablement\RejectedUnregisteredMerchant::class,
    ];

    const ACCEPTED_MAILABLE_CLASS = \RZP\Mail\Merchant\InternationalEnablement\Accepted::class;

    // NOTE: We are picking a static mapping, as from the merchants perspective these are teh only possible combinations
    // In case Pages, Links and Invoices can be vouched for seperately, then this needs to change
    const PERMISSION_PRODUCT_MAPPING = [
        Permission\Name::EDIT_MERCHANT_PG_INTERNATIONAL      => 'Payment Gateway',
        Permission\Name::EDIT_MERCHANT_PROD_V2_INTERNATIONAL => 'Payment Pages, Links and Invoices',
    ];

    public static function isValidRejectionReason(string $rejectionReason)
    {
        return in_array($rejectionReason, self::REJECTION_REASONS) === true;
    }

    public static function isValidRejectionTag(string $rejectionTag)
    {
        return in_array($rejectionTag, self::REJECTION_TAGS) === true;
    }
}
