<?php

namespace RZP\Jobs;

class MerchantBalanceUpdateReverseShadowQueue extends MerchantBalanceUpdate
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_based_balance_update_reverse_shadow';
}
