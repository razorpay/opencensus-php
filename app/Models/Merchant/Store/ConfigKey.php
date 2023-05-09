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

    const MTU_COUPON_POPUP_COUNT          = 'mtu_coupon_popup_count';
    const ENABLE_MTU_CONGRATULATORY_POPUP = 'enable_mtu_congratulatory_popup';

    //m2m referral
    const REFERRED_COUNT               = 'referred_count';
    const REFERRAL_LINK                = 'referral_link';
    const REFERRAL_CODE                = 'referral_code';
    const REFERRAL_SUCCESS_POPUP_COUNT = 'referral_success_popup_count';
    const REFEREE_SUCCESS_POPUP_COUNT  = 'referee_success_popup_count';
    const REFEREE_NAME                 = 'referee_name';
    const REFEREE_ID                   = 'referee_id';
    const REFERRAL_AMOUNT              = 'referral_amount';
    const REFERRAL_AMOUNT_CURRENCY     = 'referral_amount_currency';
    const IS_SIGNED_UP_REFEREE         = 'is_signed_up_referee';
    const POLICY_DATA                  = 'policy_data';

    const WEBSITE_INCOMPLETE_SOFT_NUDGE_COUNT     = 'website_incomplete_soft_nudge_count';
    const WEBSITE_INCOMPLETE_SOFT_NUDGE_TIMESTAMP = 'website_incomplete_soft_nudge_timestamp';

    //Payment Handle
    const IS_PAYMENT_HANDLE_ONBOARDING_INITIATED = 'is_payment_handle_onboarding_initiated';

    const GST_DETAILS_FROM_PAN                    = 'gst_details_from_pan';
    const GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT  = 'get_gst_details_from_bvs_attempt_count';
    const BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT = 'bank_account_verification_attempt_count';

    const GET_PROMOTER_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT = 'get_promoter_pan_details_from_bvs_attempt_count';
    const GET_COMPANY_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT  = 'get_company_pan_details_from_bvs_attempt_count';

    const BUSINESS_NAME_SUGGESTED     = 'business_name_suggested';
    const PROMOTER_PAN_NAME_SUGGESTED = 'promoter_pan_name_suggested';

    const NO_DOC_ONBOARDING_INFO = 'no_doc_onboarding_info';

    /*
     * config that defines which key belongs to which namespace
     * Read : tells who can view the data. if it is empty it is public
     * Write : tells who can write the data. if it is empty it is public i.e. every one can write the data
     * TTL : tells the data time to live. if is empty it is stored forever
     */
    const NAMESPACE_KEY_CONFIG = [
        self::ONBOARDING_NAMESPACE => [
            self::MTU_COUPON_POPUP_COUNT                  => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::MTU_POPUP_TTL_IN_SECONDS
            ],
            self::ENABLE_MTU_CONGRATULATORY_POPUP         => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::VISIBILITY_TTL_IN_SECONDS
            ],
            self::WEBSITE_INCOMPLETE_SOFT_NUDGE_COUNT     => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::VISIBILITY_TTL_IN_SECONDS
            ],
            self::WEBSITE_INCOMPLETE_SOFT_NUDGE_TIMESTAMP => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::VISIBILITY_TTL_IN_SECONDS
            ],
            self::REFERRAL_CODE                           => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFERRAL_LINK                           => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFERRED_COUNT                          => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFERRAL_SUCCESS_POPUP_COUNT            => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFEREE_SUCCESS_POPUP_COUNT             => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFEREE_NAME                            => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFEREE_ID                              => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFERRAL_AMOUNT                         => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::REFERRAL_AMOUNT_CURRENCY                => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::IS_SIGNED_UP_REFEREE                    => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::REFERRAL_TTL_IN_SECONDS
            ],
            self::GST_DETAILS_FROM_PAN                    => [
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

            self::GET_PROMOTER_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::GET_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT_TTL_IN_SECONDS
            ],

            self::GET_COMPANY_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::GET_PAN_DETAILS_FROM_BVS_ATTEMPT_COUNT_TTL_IN_SECONDS
            ],

            self::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT_TTL_IN_SECONDS
            ],

            self::NO_DOC_ONBOARDING_INFO => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::STORE_MERCHANT_DETAILS_TTL_IN_SECONDS
            ],

            self::BUSINESS_NAME_SUGGESTED => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::BVS_SUGGESTED_NAMES_TTL_IN_SECONDS
            ],

            self::PROMOTER_PAN_NAME_SUGGESTED            => [
                Constants::STORE => Constants::REDIS,
                Constants::READ  => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL   => Constants::BVS_SUGGESTED_NAMES_TTL_IN_SECONDS
            ],
            self::IS_PAYMENT_HANDLE_ONBOARDING_INITIATED => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE => [Constants::INTERNAL],
                Constants::READ  => [Constants::INTERNAL]
            ],
            self::POLICY_DATA                            => [
                Constants::STORE  => Constants::REDIS,
                Constants::READ   => [Constants::INTERNAL],
                Constants::WRITE  => [Constants::INTERNAL],
                Constants::DELETE => [Constants::INTERNAL],
                Constants::TTL    => Constants::STORE_MERCHANT_DETAILS_TTL_IN_SECONDS
            ],
            self::SHOW_FTUX_FINAL_SCREEN                 => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::FTUX_POPUP_TTL_IN_SECONDS
            ],
            self::SHOW_FIRST_PAYMENT_BANNER              => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::FTUX_POPUP_TTL_IN_SECONDS
            ],
            self::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => [
                Constants::STORE => Constants::REDIS,
                Constants::TTL   => Constants::UPI_TERMINAL_BANNER_TTL_IN_SECONDS
            ]
        ]
    ];

    const IS_MERCHANT_NO_DOC_ONBOARDED = 'is_merchant_no_doc_onboarded';

    //ftux
    const SHOW_FTUX_FINAL_SCREEN    = 'show_ftux_final_screen';
    const SHOW_FIRST_PAYMENT_BANNER = 'show_first_payment_banner';

    //Dedicated UPI Terminal Procurement status
    const UPI_TERMINAL_PROCUREMENT_STATUS_BANNER = 'upi_terminal_procurement_status_banner';
}
