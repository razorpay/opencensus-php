<?php

namespace RZP\Models\Payment;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Transaction;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Trace\Trace;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function retrieveByIdAndMerchantId($id, $merchantId)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findByIdAndMerchantId($id, $merchantId);

        return $payment;
    }

    public function retrieveRefund($refundId, $merchantId, $paymentId = null)
    {
        if ($paymentId !== null)
        {
            Payment\Entity::verifyIdAndStripSign($paymentId);
        }

        Refund\Entity::verifyIdAndStripSign($refundId);

        return (new Refund\Repository)->findOrFailPublicByParams($refundId, $merchantId, $paymentId);
    }

    public function retrieveById($id)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->repo->payment->findOrFail($id);

        return $payment;
    }

    public function retrievePaymentById($id)
    {
        return $this->repo->payment->findOrFail($id);
    }

    public function retrieveRefundById($refundId)
    {
        return (new Refund\Repository)->findOrFail($refundId);
    }
}
