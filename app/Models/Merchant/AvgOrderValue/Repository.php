<?php


namespace RZP\Models\Merchant\AvgOrderValue;

use RZP\Models\Base;
use RZP\Models\P2p\Base\Traits\SoftDeletes;

class Repository extends Base\Repository
{
    use SoftDeletes;

    protected $entity = 'merchant_avg_order_value';
}
