<?php

namespace RZP\Models\Partner;

use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Models\Merchant\Webhook\Event;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Tax\Gst\GstTaxIdMap;
use RZP\Models\Merchant\Invoice\TaxName;

class Constants
{
    const WEEKLY_ACTIVATION_SUMMARY_MERCHANT_COUNT_CAP = 10;
    const WEEKLY_ACTIVATION_SUMMARY_JOB_PAGE_SIZE = 300;
    const WEEKLY_ACTIVATION_SUMMARY_PARTNER_LIMIT = 1000000;
    const WEEKLY_ACTIVATION_SUMMARY_JOB_BATCH_SIZE = 10;

    const REFERRAL_WITH_CONSENT = 'REFERRAL_WITH_CONSENT';

    const ADDRESS ='address';
    const COUNTRY ='country';

    const ENTITY_TYPE_PARTNER = "partner";
    const ENTITY_ID           = "entity_id";
    const ENTITY_TYPE         = "entity_type";
    const MERCHANT_ID         = "merchant_id";
    const DEFAULT_MERCHANT_ID = "10000razorpay";

    const PARTNER_SELF_SERVE = "Partner Self Serve";

    public static $subMActivationStatusLabels = [
        'activated' => 'Activated',
        'activated_mcc_pending' => 'Activated Mcc Pending',
        'activated_kyc_pending' => 'Activated Kyc Pending',
        'rejected' => 'Rejected',
        'under_review' => 'Under Review',
        'instantly_activated' => 'Instantly Activated',
        'needs_clarification' => 'Needs Clarification'
    ];

    const RATE_LIMIT_SUBMERCHANT_INVITE_BATCH_PREFIX  = 'rate_limit_submerchant_invite_batch_prefix:';

    // sub-merchant signup sources
    const BULK_LINKING_ADMIN            = 'bulk_linking_admin';
    const LINKING_ADMIN                 = 'linking_admin';
    const BULK_ONBOARDING_ADMIN         = 'bulk_onboarding_admin';
    const REFERRAL                      = 'referral';
    const COUPON                        = 'coupon';
    const ADD_ACCOUNT                   = 'add_account';
    const ADD_ACCOUNT_V1_ACCOUNTS_API   = 'add_account_v1_accounts_api';
    const ADD_ACCOUNT_V2_ONBOARDING_API = 'add_account_v2_onboarding_api';
    const ADD_MULTIPLE_ACCOUNT          = 'add_multiple_accounts';
    const PHANTOM                       = 'phantom';

    const RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_EMAIL_TEMPLATE   = 'emails.mjml.merchant.partner.notify.reseller_to_pure_platform_switch';
    const RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_EMAIL_SUBJECT    = 'Partner account type updated to Platform Partner type';
    const RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_SMS_TEMPLATE     = 'Sms.Partnerships.Partner_type_reseller_to_pure_platform_v3';
    const PURE_PLATFORM_TO_RESELLER_PARTNER_SWITCH_EMAIL_TEMPLATE   = 'emails.mjml.merchant.partner.notify.pure_platform_to_reseller_switch';
    const PURE_PLATFORM_TO_RESELLER_PARTNER_SWITCH_EMAIL_SUBJECT    = 'Partner account type updated to Reseller Partner type';
    const PURE_PLATFORM_TO_RESELLER_PARTNER_SWITCH_SMS_TEMPLATE     = 'Sms.Partnerships.Partner_type_pure_platform_to_reseller_v3';
    const PURE_PLATFORM_DOCS_LINK                                   = 'https://razorpay.com/docs/partners/platform/';
    const RESELLER_DOCS_LINK                                        = 'https://razorpay.com/docs/partners/resellers/';
    const PARTNER_SUPPORT_EMAIL                                     = 'partners@razorpay.com';
    const PARTNER_TYPE_SWITCH_TEMPLATES                             = [
        'reseller_to_pure_platform' => [
            'sms'       => self::RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_SMS_TEMPLATE,
            'email'     => self::RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_EMAIL_TEMPLATE,
            'subject'   => self::RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_EMAIL_SUBJECT,
            'docs_link' => self::PURE_PLATFORM_DOCS_LINK
        ],
        'pure_platform_to_reseller' => [
            'sms'       => self::PURE_PLATFORM_TO_RESELLER_PARTNER_SWITCH_SMS_TEMPLATE,
            'email'     => self::PURE_PLATFORM_TO_RESELLER_PARTNER_SWITCH_EMAIL_TEMPLATE,
            'subject'   => self::PURE_PLATFORM_TO_RESELLER_PARTNER_SWITCH_EMAIL_SUBJECT,
            'docs_link' => self::RESELLER_DOCS_LINK
        ]
    ];

    const NEEDS_CLARIFICATION_OPT_OUT_FEATURE = [
        'email'         => FeatureConstants::NC_EMAIL_OPT_OUT,
        'whatsapp'      => FeatureConstants::NC_WHATSAPP_OPT_OUT,
    ];

    const RESELLER_TO_PURE_PLATFORM_MIGRATE               = "reseller_to_pure_platform_migrate";
    const RESELLER_TO_PURE_PLATFORM_MIGRATE_LOCK_TIME_OUT = 30; //seconds
    const PURE_PLATFORM_TO_RESELLER_MIGRATE               = "pure_platform_to_reseller_migrate";
    const PURE_PLATFORM_TO_RESELLER_MIGRATE_LOCK_TIME_OUT = 30; //seconds

    /**
     * List of partner types that can get a settlement on behalf of a submerchant
     *
     * @var array
     */
    public static $settlementPartnerTypes = [
        Merchant\Constants::AGGREGATOR,
        Merchant\Constants::FULLY_MANAGED,
    ];

    /**
     * List of partner types that are allowed to set Default Payment Methods in Partner Config
     *
     * @var array
     */
    public static $defaultPaymentMethodsPartnerTypes = [
        Merchant\Constants::AGGREGATOR,
        Merchant\Constants::FULLY_MANAGED,
    ];


    public static array $taxComponentNameMap = [
        TaxName::CGST  => [
            'tax_id'   => GstTaxIdMap::CGST_90000,
            'name'     => 'CGST 9%',
            'rate'     => 90000,
        ],
        TaxName::SGST  => [
            'tax_id'   => GstTaxIdMap::SGST_90000,
            'name'     => 'SGST 9%',
            'rate'     => 90000,
        ],
        TaxName::IGST  => [
            'tax_id'   => GstTaxIdMap::IGST_180000,
            'name'     => 'IGST 18%',
            'rate'     => 180000,
        ]
    ];

    const transactionIsolationEntityTypeKey = "entity_type";
    const transactionIsolationEntityIdKey   = "id";
    const transactionIsolationEventTypeKey  = "event_type";
    const transactionIsolationPartnerEvent  = "partnership";

    const TRANSACTION_ISOLATION_ORDER_EXPERIMENT = "app.transaction_isolation_for_order_experiment_id";
    const TRANSACTION_ISOLATION_REFUND_EXPERIMENT = "app.transaction_isolation_for_refund_experiment_id";
    const TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID = "app.transaction_isolation_for_invoice_experiment_id";
    const TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID = "app.transaction_isolation_for_subscription_experiment_id";
    const TRANSACTION_ISOLATION_VIRTUAL_ACCOUNT_EXPERIMENT_ID = "app.transaction_isolation_for_virtual_account_experiment_id";
    const TRANSACTION_ISOLATION_QR_CODE_EXPERIMENT_ID = "app.transaction_isolation_for_qr_code_experiment_id";
    const TRANSACTION_ISOLATION_PAYMENT_EXPERIMENT = "app.transaction_isolation_for_payment_experiment_id";
    const TRANSACTION_ISOLATION_TRANSFER_EXPERIMENT_ID = "app.transaction_isolation_for_transfer_experiment_id";
    const TRANSACTION_ISOLATION_DISPUTE_EXPERIMENT = "app.transaction_isolation_for_dispute_experiment_id";

    public static array $transactionIsolationEventToExperimentMap = [
        Event::ORDER_PAID            => self::TRANSACTION_ISOLATION_ORDER_EXPERIMENT,
        Event::REFUND_PROCESSED      => self::TRANSACTION_ISOLATION_REFUND_EXPERIMENT,
        Event::REFUND_CREATED        => self::TRANSACTION_ISOLATION_REFUND_EXPERIMENT,
        Event::REFUND_FAILED         => self::TRANSACTION_ISOLATION_REFUND_EXPERIMENT,
        Event::REFUND_SPEED_CHANGED  => self::TRANSACTION_ISOLATION_REFUND_EXPERIMENT,
        Event::INVOICE_PAID           => self::TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID,
        Event::INVOICE_PARTIALLY_PAID => self::TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID,
        Event::INVOICE_EXPIRED        => self::TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID,
        Event::PAYMENT_LINK_PAID      => self::TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID,
        Event::PAYMENT_LINK_PARTIALLY_PAID => self::TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID,
        Event::PAYMENT_LINK_CANCELLED => self::TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID,
        Event::PAYMENT_LINK_EXPIRED   => self::TRANSACTION_ISOLATION_INVOICE_EXPERIMENT_ID,
        Event::SUBSCRIPTION_AUTHENTICATED => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_ACTIVATED => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_COMPLETED => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_UPDATED   => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_PENDING   => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_HALTED    => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_CHARGED   => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_CANCELLED => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_PAUSED    => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::SUBSCRIPTION_RESUMED   => self::TRANSACTION_ISOLATION_SUBSCRIPTION_EXPERIMENT_ID,
        Event::PAYMENT_AUTHORIZED    => self::TRANSACTION_ISOLATION_PAYMENT_EXPERIMENT,
        Event::PAYMENT_CAPTURED      => self::TRANSACTION_ISOLATION_PAYMENT_EXPERIMENT,
        Event::PAYMENT_FAILED        => self::TRANSACTION_ISOLATION_PAYMENT_EXPERIMENT,
        Event::VIRTUAL_ACCOUNT_CREATED  => self::TRANSACTION_ISOLATION_VIRTUAL_ACCOUNT_EXPERIMENT_ID,
        Event::VIRTUAL_ACCOUNT_CREDITED => self::TRANSACTION_ISOLATION_VIRTUAL_ACCOUNT_EXPERIMENT_ID,
        Event::VIRTUAL_ACCOUNT_CLOSED   => self::TRANSACTION_ISOLATION_VIRTUAL_ACCOUNT_EXPERIMENT_ID,
        Event::QR_CODE_CREATED        => self::TRANSACTION_ISOLATION_QR_CODE_EXPERIMENT_ID,
        Event::QR_CODE_CREDITED       => self::TRANSACTION_ISOLATION_QR_CODE_EXPERIMENT_ID,
        Event::QR_CODE_CLOSED         => self::TRANSACTION_ISOLATION_QR_CODE_EXPERIMENT_ID,
        Event::TRANSFER_PROCESSED     => self::TRANSACTION_ISOLATION_TRANSFER_EXPERIMENT_ID,
        Event::TRANSFER_FAILED        => self::TRANSACTION_ISOLATION_TRANSFER_EXPERIMENT_ID,
        Event::PAYMENT_DISPUTE_CREATED         => self::TRANSACTION_ISOLATION_DISPUTE_EXPERIMENT,
        Event::PAYMENT_DISPUTE_LOST            => self::TRANSACTION_ISOLATION_DISPUTE_EXPERIMENT,
        Event::PAYMENT_DISPUTE_WON             => self::TRANSACTION_ISOLATION_DISPUTE_EXPERIMENT,
        Event::PAYMENT_DISPUTE_CLOSED          => self::TRANSACTION_ISOLATION_DISPUTE_EXPERIMENT,
        Event::PAYMENT_DISPUTE_UNDER_REVIEW    => self::TRANSACTION_ISOLATION_DISPUTE_EXPERIMENT,
        Event::PAYMENT_DISPUTE_ACTION_REQUIRED => self::TRANSACTION_ISOLATION_DISPUTE_EXPERIMENT,
    ];

    const ONBOARDING_SIGNATURE                    = 'onboarding_signature';
    const CLIENT_ID                               = 'client_id';
    const TIMESTAMP                               = 'timestamp';
    const ONBOARDING_SIGNATURE_EXPIRY_IN_SECONDS  = 86400;
    const SUBMERCHANT_PREFILL_LOGIN               = 'submerchant_prefill_login';
}
