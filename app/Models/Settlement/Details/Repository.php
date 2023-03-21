<?php

namespace RZP\Models\Settlement\Details;

use RZP\Models\Base;
use RZP\Models\Settlement;

class Repository extends Base\Repository
{
    protected $entity = 'settlement_details';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::SETTLEMENT_ID   => 'sometimes|string|min:14|max:19',
        Entity::COMPONENT       => 'sometimes|string',
    );

    public function getSettlementDetails($id, $merchant)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::SETTLEMENT_ID, '=', $id)
                    ->get();
    }

    protected function addQueryParamSettlementId($query, $params)
    {
        $entityId = $params[Entity::SETTLEMENT_ID];

        Settlement\Entity::stripSignWithoutValidation($entityId);

        $query->where(Entity::SETTLEMENT_ID, '=', $entityId);
    }
}
