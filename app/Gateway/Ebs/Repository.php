<?php

namespace RZP\Gateway\Ebs;

use RZP\Exception;
use RZP\Constants;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\ENTITY::EBS;

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|string',
        Entity::RECEIVED        => 'sometimes|in:0,1',
        Entity::REFUND_ID       => 'sometimes|string',
    );

    public function findByGatewayRefundId($gatewayRefundId)
    {
        $repo = $this->repo;

        return $repo::where(Entity::REFUND_ID, '=', $gatewayRefundId)
                    ->firstOrFail();
    }

    public function findByEbsPaymentIdAndActionOrFail($ebsPaymentId, $action)
    {
        $repo = $this->repo;

        return $repo::where(Entity::REFERENCE_ID, '=', $ebsPaymentId)
                    ->where(Entity::ACTION, '=', $action)
                    ->firstOrFail();
    }
}
