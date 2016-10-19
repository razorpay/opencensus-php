<?php

namespace RZP\Gateway\Cybersource;

use RZP\Exception;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Cybersource';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID   => 'sometimes|string|min:14|max:18',
        Entity::RECEIVED     => 'sometimes|boolean',
        Entity::REF          => 'sometimes|string',
        Entity::CAPTURE_REF  => 'sometimes|string'
    );

    public function retrieveByPaymentIdAndStatus($id, $status)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $id)
                    ->where(Entity::STATUS, '=', $status)
                    ->firstOrFail();
    }

    public function retrieveCapturedByPaymentId($id)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $id)
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->first();
    }
}
