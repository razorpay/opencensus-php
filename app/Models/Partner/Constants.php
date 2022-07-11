<?php

namespace RZP\Models\Partner;

use RZP\Models\Merchant;

class Constants
{
    const WEEKLY_ACTIVATION_SUMMARY_MERCHANT_COUNT_CAP = 10;
    const WEEKLY_ACTIVATION_SUMMARY_JOB_PAGE_SIZE = 300;
    const WEEKLY_ACTIVATION_SUMMARY_PARTNER_LIMIT = 1000000;
    const WEEKLY_ACTIVATION_SUMMARY_JOB_BATCH_SIZE = 10;
    
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
    const BULK_LINKING_ADMIN    = 'bulk_linking_admin';
    const LINKING_ADMIN         = 'linking_admin';
    const BULK_ONBOARDING_ADMIN = 'bulk_onboarding_admin';
    const REFERRAL              = 'referral';
    const COUPON                = 'coupon';
    const ADD_ACCOUNT           = 'add_account';
    const ADD_MULTIPLE_ACCOUNT  = 'add_multiple_accounts';

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
}
