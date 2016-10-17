<?php

namespace RZP\Models\Settlement;

use RZP\Models\Base;
use RZP\Models\Settlement;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Settlement';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
        Entity::STATUS          => 'sometimes|in:created,processed,failed',
        Entity::UTR             => 'sometimes|alpha_num',
    );

    public function getSettlementWithFeesAsNullOrZero()
    {
        return $this->newQuery()
                    ->where(Entity::FEES, '=', '0')
                    ->orWhereNull(Entity::FEES)
                    ->get();
    }


    public function getSettlementWithServiceTaxNullOrZero()
    {
        return $this->newQuery()
                    ->where(Entity::SERVICE_TAX, '=', '0')
                    ->orWhereNull(Entity::SERVICE_TAX)
                    ->get();
    }

    public function fetchSettlementsBetweenTimestamp($from, $to)
    {
        return $this->newQuery()
                    ->whereBetween(Entity::CREATED_AT, [$from, $to])
                    ->select(Entity::MERCHANT_ID, Entity::AMOUNT)
                    ->get();
    }

    public function getFewSettlementsWithNoCorrespondingSettlementDetails()
    {
        $setlIds = $this->db->select(
            'SELECT DISTINCT id FROM settlements
                WHERE settlements.id NOT IN
                    (SELECT DISTINCT settlements.id from settlements
                        JOIN settlement_details on settlements.id = settlement_details.settlement_id)
                LIMIT 20');

        $setlIds = json_decode(json_encode($setlIds), true);

        $setlIds2  = [];
        foreach ($setlIds as $setlId)
        {
            $setlIds2[] = $setlId['id'];
        }

        return $this->newQuery()
                    ->whereIn(Entity::ID, $setlIds2)
                    ->get();
    }
}
