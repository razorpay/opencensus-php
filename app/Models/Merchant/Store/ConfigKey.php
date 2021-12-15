<?php


namespace RZP\Models\Merchant\Store;


class ConfigKey
{
    /*
     * Namespaces defined here (part of prefix for redis-store
     */
    const ONBOARDING_NAMESPACE = 'onboarding';

    /*
     * Keys are defined here
     */

    const MTU_COUPON_POPUP_COUNT             = 'mtu_coupon_popup_count';
    const ENABLE_MTU_CONGRATULATORY_POPUP    = 'enable_mtu_congratulatory_popup';

    //m2m referral
    const REFERRED_COUNT               = 'referred_count';
    const REFERRAL_LINK                = 'referral_link';
    const REFERRAL_CODE                = 'referral_code';
    const REFERRAL_SUCCESS_POPUP_COUNT = 'referral_success_popup_count';
    const REFEREE_SUCCESS_POPUP_COUNT = 'referee_success_popup_count';
    const REFEREE_NAME                 = 'referee_name';
    const REFEREE_ID                   = 'referee_id';
    const REFERRAL_AMOUNT              = 'referral_amount';
    const REFERRAL_AMOUNT_CURRENCY     = 'referral_amount_currency';
    const IS_SIGNED_UP_REFEREE         = 'is_signed_up_referee';

    const GST_DETAILS_FROM_PAN                    = 'gst_details_from_pan';
    const GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT  = 'get_gst_details_from_bvs_attempt_count';
    const BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT = 'bank_account_verification_attempt_count';
    /*
     * config that defines which key belongs to which namespace
     * Read : tells who can view the data. if it is empty it is public
     * Write : tells who can write the data. if it is empty it is public i.e. every one can write the data
     * TTL : tells the data time to live. if is empty it is stored forever
     */
    const NAMESPACE_KEY_CONFIG = [
        self::ONBOARDING_NAMESPACE => [
            self::MTU_COUPON_POPUP_COUNT       => [
                Constants::STORE => Constants::REDIS
            ],
            self::ENABLE_MTU_CONGRATULATORY_POPUP       => [
                Constants::STORE => Constants::REDIS
            ],
            self::REFERRAL_CODE                => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL]
            ],
            self::REFERRAL_LINK                => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL]
            ],
            self::REFERRED_COUNT               => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL]
            ],
            self::REFERRAL_SUCCESS_POPUP_COUNT => [
                Constants::STORE => Constants::REDIS
            ],
            self::REFEREE_SUCCESS_POPUP_COUNT => [
                Constants::STORE => Constants::REDIS
            ],
            self::REFEREE_NAME                 => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
            ],
            self::REFEREE_ID                   => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
            ],
            self::REFERRAL_AMOUNT              => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL]
            ],
            self::REFERRAL_AMOUNT_CURRENCY     => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL]
            ],
            self::IS_SIGNED_UP_REFEREE         => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL]
            ],
            self::GST_DETAILS_FROM_PAN         => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::GST_DETAILS_FROM_PAN_TTL_IN_SECONDS
            ],

            self::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT_TTL_IN_SECONDS
            ],

            self::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT_TTL_IN_SECONDS
            ]
        ]
    ];
}
