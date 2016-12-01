<?php

namespace RZP\Gateway\Ebs;

use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::EBS;

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string',
        Entity::RECEIVED            => 'sometimes|boolean',
        Entity::REFUND_ID           => 'sometimes|string',
        Entity::GATEWAY_PAYMENT_ID  => 'sometimes|string',
    );


    public function findByEbsPaymentIdAndActionOrFail($ebsPaymentId, $action)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_PAYMENT_ID, '=', $ebsPaymentId)
                    ->where(Entity::ACTION, '=', $action)
                    ->firstOrFail();
    }
}
