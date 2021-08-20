<?php

namespace RZP\Gateway\Netbanking\Base;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'netbanking';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID          => 'sometimes|string|size:14',
        Entity::CAPS_PAYMENT_ID     => 'sometimes|string|size:14',
        Entity::BANK_PAYMENT_ID     => 'sometimes|string|max:20',
        Entity::INT_PAYMENT_ID      => 'sometimes',
    );

    public function findByIntPaymentId($intPaymentId)
    {
        return $this->newQuery()
                    ->where(Entity::INT_PAYMENT_ID, '=', $intPaymentId)
                    ->firstOrFail();
    }

    public function findByGatewayPaymentIdAndAction($gatewayPaymentId, $action)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_PAYMENT_ID, '=', $gatewayPaymentId)
                    ->where(Entity::ACTION, '=', $action)
                    ->firstOrFail();
    }

    public function findByVerificationIdAndAction($paymentId, $action)
    {
        return $this->newQuery()
                    ->where(Entity::VERIFICATION_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', $action)
                    ->first();
    }

    public function findByVerificationIdOrPaymentIdAndAction($verificationId, $action, $paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::ACTION, '=', $action)
                    ->where(function ($query) use ($verificationId, $paymentId)
                {
                    $query->where(Entity::VERIFICATION_ID, '=', $verificationId)
                          ->orWhere(Entity::PAYMENT_ID, '=', $paymentId);
                })
                    ->first();
    }

    public function findByRefundIdActionAndReference1($refundId, $action, $reference1)
    {
        return $this->newQuery()
                    ->where(Entity::REFUND_ID, '=', $refundId)
                    ->where(Entity::ACTION, '=', $action)
                    ->where(Entity::REFERENCE1, '=', $reference1)
                    ->firstOrFail();
    }

    public function findByGatewayPaymentId($gatewayPaymentId)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_PAYMENT_ID, '=', $gatewayPaymentId)
                    ->first();
    }
}
