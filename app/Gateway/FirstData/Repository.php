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
     * Used in Payment Reconciliate for fetching
     * payment by given gateway caps_payment_id & its respective action
     * For 'purchase', we consider entities where action is
     * 'purchase' & 'authorize' & pick the first entry.
     *
     * @param string $capsPaymentId
     * @return Entity
     */
    public function findPaymentForGateway(string $capsPaymentId)
    {
        $actions = [Base\Action::PURCHASE, Base\Action::AUTHORIZE];

        return $this->newQuery()
                    ->where(Entity::CAPS_PAYMENT_ID, '=', $capsPaymentId)
                    ->whereIn(Entity::ACTION, $actions)
                    ->firstOrFail();
    }

    /**
     * Used in Refund Reconciliate for fetching
     * payment by given gateway caps_payment_id action as refund
     * & gateway_transaction_id
     *
     * @param string $capsPaymentId
     * @param string $gatewayTxnId [to determine partial refunds]
     * @return Entity
     */
    public function findRefundForGateway(
        string $capsPaymentId, string $gatewayTxnId)
    {
        return $this->newQuery()
                    ->where(Entity::CAPS_PAYMENT_ID, '=', $capsPaymentId)
                    ->where(Entity::GATEWAY_TRANSACTION_ID, '=', $gatewayTxnId)
                    ->where(Entity::ACTION, '=', Base\Action::REFUND)
                    ->firstOrFail();
    }

    public function findSuccessfulRefundByRefundId(string $refundId)
    {
        $refundEntities =  $this->newQuery()
                                ->where(Entity::REFUND_ID, '=', $refundId)
                                ->where(Entity::ACTION, '=', Base\Action::REFUND)
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
