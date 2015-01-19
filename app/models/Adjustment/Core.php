<?php

namespace Models\Adjustment;

use EE\Exception;
use Models\Base;
use Models\Transaction;
use Trace\Trace;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        $this->adjRepo = new Adjustment\Repository;
    }

    public function createAdjustment($amount, $merchant)
    {
        $adj = new Entity;

        $adj->setAmount($amount);
        $adj->setAttribute(Entity::CURRENCY, 'INR');
        $adj->merchant()->associate($merchant);

        $this->adjRepo->saveOrFail($adj);

        return $adj;
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
