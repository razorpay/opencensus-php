<?php

namespace RZP\Models\Merchant\OwnerDetail;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_owner_details';

    public function getByMerchantIdAndGateway($merchantId, $gateway)
    {
        return $this->newQueryOnSlave()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::GATEWAY, $gateway)
            ->where(Entity::DELETED_AT, null)
            ->get();
    }
}
