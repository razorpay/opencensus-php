<?php

namespace RZP\Gateway\Hitachi;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'hitachi';

    // TODO: Rename the function to a proper one
    // and fix the get auth code function for emi
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->firstOrFail();
    }

    public function findSuccessfulRefundByRefundId($refundId)
    {
        $refundEntities = $this->findByRefundIdAndAction($refundId, Base\Action::REFUND);

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
                    'refund_id' => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }

}
