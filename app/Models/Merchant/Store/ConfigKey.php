<?php


namespace RZP\Models\Merchant\Store;


class ConfigKey
{
    /*
     * Namespaces defined here (part of prefix for redis-store
     */
    const ONBOARDING_NAMESPACE  = 'onboarding';

    /*
     * Keys are defined here
     */
    const MTU_COUPON_POPUP_COUNT    = 'mtu_coupon_popup_count';

    /*
     * config that defines which key belongs to which namespace
     */
    const NAMESPACE_KEY_CONFIG         = [
        self::ONBOARDING_NAMESPACE  => [
            self::MTU_COUPON_POPUP_COUNT    => [
                Constants::STORE    => Constants::REDIS
            ]
        ]
    ];
}
