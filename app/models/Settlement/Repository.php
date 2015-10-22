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
    );

    public function fetchBetweenTimestamp($from, $to, $merchantId)
    {
        $repo = $this->repo;
        return $repo::whereBetween(Entity::CREATED_AT, [$from, $to])
            ->where(Base\Common::MERCHANT_ID, '=', $merchantId)
            ->get();
    }

    public function getSettlementWithFeesAsNullOrZero()
    {
        $repo = $this->repo;

        return $repo::where(Entity::FEE, '=', '0')
                    ->orWhereNull(Entity::FEE)
                    ->get();
    }
}
