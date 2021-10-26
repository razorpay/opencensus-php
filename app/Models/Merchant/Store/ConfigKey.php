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
    const MTU_COUPON_POPUP_COUNT                  = 'mtu_coupon_popup_count';
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

            self::MTU_COUPON_POPUP_COUNT => [
                Constants::STORE => Constants::REDIS,
            ],

            self::GST_DETAILS_FROM_PAN => [
                Constants::STORE  => Constants::REDIS,
                Constants::READ   => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL    => Constants::GST_DETAILS_FROM_PAN_TTL_IN_SECONDS
            ],

            self::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT => [
                Constants::STORE  => Constants::REDIS,
                Constants::READ   => [Constants::INTERNAL],
                Constants::WRITE => [Constants::INTERNAL],
                Constants::TTL    => Constants::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT_TTL_IN_SECONDS
            ],

            self::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT => [
                Constants::STORE => Constants::REDIS,
                Constants::WRITE  => [Constants::INTERNAL],
                Constants::TTL   => Constants::BANK_ACCOUNT_VERIFICATION_ATTEMPT_COUNT_TTL_IN_SECONDS
            ]
        ]
    ];
}
