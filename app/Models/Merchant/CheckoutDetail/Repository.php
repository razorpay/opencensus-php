<?php


namespace RZP\Models\Merchant\CheckoutDetail;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_checkout_detail';

    public function getByMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->first();
    }
}
