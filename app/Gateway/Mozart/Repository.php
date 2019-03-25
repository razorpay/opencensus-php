<?php

namespace RZP\Gateway\Mozart;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'mozart';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
    );

    public function findByPaymentIdAndMapByAction($paymentId, $actions = [])
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->whereIn(Entity::ACTION, $actions)
                    ->get()
                    ->keyBy(Entity::ACTION);
    }

    public function findByPaymentIdAndActionsOrFail($paymentId, $actions)
    {
        return $this->newQuery()
            ->where('payment_id', '=', $paymentId)
            ->whereIn('action', $actions)
            ->firstOrFail();
    }
}
