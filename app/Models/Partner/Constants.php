<?php

namespace RZP\Models\Partner;

use RZP\Models\Merchant;
use RZP\Models\Tax\Gst\GstTaxIdMap;
use RZP\Models\Merchant\Invoice\TaxName;

class Constants
{
    const WEEKLY_ACTIVATION_SUMMARY_MERCHANT_COUNT_CAP = 10;
    const WEEKLY_ACTIVATION_SUMMARY_JOB_PAGE_SIZE = 300;
    const WEEKLY_ACTIVATION_SUMMARY_PARTNER_LIMIT = 1000000;
    const WEEKLY_ACTIVATION_SUMMARY_JOB_BATCH_SIZE = 10;

    const ADDRESS ='address';
    const COUNTRY ='country';

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

    const RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_EMAIL_TEMPLATE = 'emails.mjml.merchant.partner.notify.reseller_to_pure_platform_switch';
    const RESELLER_TO_PURE_PLATFORM_PARTNER_SWITCH_SMS_TEMPLATE = 'Sms.Partnerships.Partner_type_reseller_to_pure_platform_v3';
    const PURE_PLATFORM_DOCS_LINK = 'https://razorpay.com/docs/partners/platform/';
    const PARTNER_SUPPORT_EMAIL   = 'partners@razorpay.com';

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
            'rate'     => '90000',
        ],
        TaxName::SGST  => [
            'tax_id'   => GstTaxIdMap::SGST_90000,
            'name'     => 'SGST 9%',
            'rate'     => '90000',
        ],
        TaxName::IGST  => [
            'tax_id'   => GstTaxIdMap::IGST_180000,
            'name'     => 'IGST 18%',
            'rate'     => '180000',
        ]
    ];
}
