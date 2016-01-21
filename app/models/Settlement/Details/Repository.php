<?php

namespace Models\Settlement\Details;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'settlement_details';

    public function getSettlementDetails($id, $merchant)
    {
        $repo = $this->repo;

        return $repo::where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::SETTLEMENT_ID, '=', $id)
                    ->get();
    }
}