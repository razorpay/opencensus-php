<?php

namespace RZP\Gateway\Card\Fss;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'card_fss';

    // TODO: Rename the function to a proper one
    // and fix the get auth code function for emi
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->firstOrFail();
    }

    public function findOrFailRefundByRefundId($refundId)
    {
        $refundEntities = $this->newQuery()
                               ->where(Entity::REFUND_ID, '=', $refundId)
                               ->where(Entity::ACTION, '=', Base\Action::REFUND)
                               ->whereIn(Entity::STATUS, Status::$successStates)
                               ->get();

        //
        // There should never be more than one successful gateway refund entity
        // for a given refund_id
        //
        if ($refundEntities->count() > 1)
        {
            throw new Exception\LogicException(
                'Multiple successful refund entities found for a refund ID',
                Error\ErrorCode::SERVER_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'refund_id'       => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities->first();
    }
}
