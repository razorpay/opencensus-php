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
        $repo = $this->repo;

        return $repo::where(Entity::PAYMENT_ID, '=', $id)
                  ->where(Entity::STATUS, '=', $status)
                  ->firstOrFail();
    }

    public function findCapturedPaymentById($paymentId)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PAYMENT_ID, '=', $paymentId)
                  ->where(Entity::ACTION, '=', Status::CAPTURED)
                  ->firstOrFail();
    }

    public function retrieveCapturedByPaymentId($id)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PAYMENT_ID, '=', $id)
                  ->where(Entity::STATUS, '=', Status::CAPTURED)
                  ->first();
    }
}
