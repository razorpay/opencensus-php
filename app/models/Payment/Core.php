<?php

namespace Models\Payment;

use EE\Exception\BaseException;
use EE\Exception\BadRequestException;
use Models\Card;
use Models\Transaction;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;

class Core
{
    protected $trace;

    protected $paymentRepo;

    public function __construct()
    {
        $this->trace = Trace::getInstance();

        $this->paymentRepo = (new Payment\Repository);
    }

    public function retrieveByIdAndMerchantId($id, $merchantId)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $payment = $this->paymentRepo->findByIdAndMerchantId($id, $merchantId);

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

        $payment = $this->paymentRepo->findOrFail($id);

        return $payment;
    }
}
