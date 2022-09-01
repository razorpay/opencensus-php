<?php

namespace RZP\Gateway\Mozart;

use RZP\Base\ConnectionType;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'mozart';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
    );

    public function findByPaymentIdAndMapByAction($paymentId, $actions = [])
    {
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $this->newQueryWithConnection($connectionType)
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->whereIn(Entity::ACTION, $actions)
                    ->get()
                    ->keyBy(Entity::ACTION);
    }
}
