<?php

namespace RZP\Gateway\Mozart;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'mozart';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
    );

    public function findByGatewayPaymentIdAndAction($gatewayPaymentId, $action)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_PAYMENT_ID, '=', $gatewayPaymentId)
                    ->where(Entity::ACTION, '=', $action)
                    ->firstOrFail();
    }
}
