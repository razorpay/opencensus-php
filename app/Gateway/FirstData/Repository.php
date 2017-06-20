<?php

namespace RZP\Gateway\FirstData;

use RZP\Error;
use RZP\Exception;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'first_data';

    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::CAPTURE)
                    ->firstOrFail();
    }

    /**
     * Used in Payment & Refund Reconciliate for fetching
     * payment by given gateway transaction id & its respective action
     *
     * @param string $gatewayTxnId
     * @return FirstData\Entity
     */
    public function findByGatewayTransactionIdAndAction(
        string $gatewayTxnId, string $action)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_TRANSACTION_ID, '=', $gatewayTxnId)
                    ->where(Entity::ACTION, '=', $action)
                    ->firstOrFail();
    }

    public function findSuccessfulRefundByRefundId(string $refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where('refund_id', '=', $refundId)
                                ->where('action', '=', Base\Action::REFUND)
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
                    'refund_id'       => $refundId,
                    'refund_entities' => $refundEntities->toArray()
                ]);
        }

        return $refundEntities;
    }
}
