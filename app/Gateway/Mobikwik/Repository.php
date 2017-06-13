<?php

namespace RZP\Gateway\Mobikwik;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'mobikwik';

    public function findRefundByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('action', '=', Base\Action::REFUND)
                    ->firstOrFail();
    }

    public function findSuccessfulRefundByRefundId($refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where('refund_id', '=', $refundId)
                                ->where('action', '=', Base\Action::REFUND)
                                ->where('statuscode', '=' , '0')
                                ->get();
        //
        // There should never be more than one successful gateway refund entity
        // for a given refund_id
        //
        if ($refundEntities->count() > 1)
        {
            throw new Exception\LogicException(
                'Multiple refund entities found for a refund ID',
                Error\ErrorCode::SERVER_ERROR_MULTIPLE_REFUNDS_FOUND,
                [
                    'refund_id' => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }
}
