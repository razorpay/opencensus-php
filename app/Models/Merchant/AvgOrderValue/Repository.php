<?php


namespace RZP\Models\Merchant\AvgOrderValue;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_avg_order_value';
}
