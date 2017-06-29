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
     * payment by given gateway caps_payment_id & its respective action
     * For 'purchase', we consider entities where action is
     * 'authorize' & 'capture' & pick the first entry.
     *
     * @param string $capsPaymentId
     * @param string $action
     * @param string $gatewayTxnId [to be sent only in case of refunds
     *                              to determine partial refunds]
     * @return Entity
     */
    public function findByCapsPaymentIdAndAction(
        string $capsPaymentId, string $action, string $gatewayTxnId = null)
    {
        switch ($action)
        {
            case Base\Action::PURCHASE :

                $actions = [Base\Action::AUTHORIZE, Base\Action::CAPTURE];

                $result = $this->newQuery()
                               ->where(Entity::CAPS_PAYMENT_ID, '=', $capsPaymentId)
                               ->whereIn(Entity::ACTION, $actions)
                               ->firstOrFail();

                break;

            case Base\Action::REFUND :

                $result = $this->newQuery()
                               ->where(Entity::CAPS_PAYMENT_ID, '=', $capsPaymentId)
                               ->where(Entity::GATEWAY_TRANSACTION_ID, '=', $gatewayTxnId)
                               ->whereIn(Entity::ACTION, '=', Base\Action::REFUND)
                               ->firstOrFail();

                break;
        }

        return $result;
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
