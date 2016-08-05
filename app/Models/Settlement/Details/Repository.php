<?php

namespace RZP\Models\Settlement\Details;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'settlement_details';

    public function getSettlementDetails($id, $merchant)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::SETTLEMENT_ID, '=', $id)
                    ->get();
    }
}