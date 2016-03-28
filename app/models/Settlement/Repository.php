<?php

namespace Models\Settlement;

use Models\Base;
use Models\Settlement;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Settlement';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
        Entity::STATUS          => 'sometimes|in:created,processed,failed',
    );

    public function getSettlementWithFeesAsNullOrZero()
    {
        $repo = $this->repo;

        return $repo::where(Entity::FEES, '=', '0')
                    ->orWhereNull(Entity::FEES)
                    ->get();
    }


    public function getSettlementWithServiceTaxNullOrZero()
    {
        $repo = $this->repo;

        return $repo::where(Entity::SERVICE_TAX, '=', '0')
                    ->orWhereNull(Entity::SERVICE_TAX)
                    ->get();
    }
}
