<?php

namespace RZP\Models\Partner;

use RZP\Models\Merchant;

class Constants
{
    const RATE_LIMIT_SUBMERCHANT_INVITE_BATCH_PREFIX  = 'rate_limit_submerchant_invite_batch_prefix:';

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
