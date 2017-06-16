<?php

namespace RZP\Gateway\FirstData;

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
     * @param $gatewayTxnId string
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
}
