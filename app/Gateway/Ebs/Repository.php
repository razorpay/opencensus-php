<?php

namespace RZP\Gateway\Ebs;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'ebs';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID      => 'sometimes|string|min:14|max:18',
        Entity::RECEIVED        => 'sometimes|in:0,1',
        Entity::AUTH_STATUS     => 'sometimes|max:5',
        Entity::REF_STATUS      => 'sometimes|max:5',
        Entity::REFUND_ID       => 'sometimes|string',
    );

    public function findByGatewayRefundId($gatewayRefundId)
    {
        $repo = $this->repo;

        return $repo::where(Entity::REFUND_ID, '=', $gatewayRefundId)
                    ->firstOrFail();
    }

    public function findByEbsPaymentIdAndActionOrFail($paymentId, $action)
    {
        return $this->newQuery()
            ->where(Entity::EBS_PAYMENT_ID, '=', $paymentId)
            ->where(Entity::ACTION, '=', $action)
            ->firstOrFail();
    }
}
