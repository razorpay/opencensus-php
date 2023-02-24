<?php

namespace RZP\Jobs;

class MerchantBasedBalanceUpdateV3 extends MerchantBalanceUpdate
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_based_balance_update_v3';
}
