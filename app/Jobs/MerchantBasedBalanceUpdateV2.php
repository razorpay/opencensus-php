<?php

namespace RZP\Jobs;

class MerchantBasedBalanceUpdateV2 extends MerchantBalanceUpdate
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_based_balance_update_v2';
}
