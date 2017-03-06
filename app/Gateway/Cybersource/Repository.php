<?php

namespace RZP\Gateway\Cybersource;

use RZP\Exception;
use RZP\Gateway\Cybersource;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'cybersource';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID   => 'sometimes|string|size:14',
        Entity::REFUND_ID    => 'sometimes|string|size:14',
        Entity::RECEIVED     => 'sometimes|boolean',
        Entity::REF          => 'sometimes|string',
        Entity::CAPTURE_REF  => 'sometimes|string'
    );

    // TODO: Rename the function to a proper one
    // and fix the get auth code function for emi
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->firstOrFail();
    }

    public function findSuccessfulCapturedEntity($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::CAPTURE)
                    ->where(Entity::STATUS, '=', Status::CAPTURED)
                    ->first();
    }
}
