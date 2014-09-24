<?php

namespace Models\Payment;

use EE\Exception\BaseException;
use EE\Exception\BadRequestException;

use Models\Gateway;

use Models\Card;
use Models\Ledger;
use Models\Payment;

use Trace\Trace;
use Trace\TraceCode;

class Core
{
    protected $trace;

    protected $txnRepo;

    public function __construct()
    {
        $this->trace = Trace::getInstance();

        $this->txnRepo = (new Payment\Repository);
    }

    public function retrieveByIdAndMerchantId($id, $merchantId)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $txn = $this->txnRepo->findByIdAndMerchantId($id, $merchantId);

        return $txn;
    }

    public function retrieveRefund($refundId, $merchantId, $txnId = null)
    {
        if ($txnId !== null)
        {
            Payment\Entity::verifyIdAndStripSign($txnId);
        }

        Refund\Entity::verifyIdAndStripSign($refundId);

        return (new Refund\Repository)->findOrFailPublicByParams($refundId, $merchantId, $txnId);
    }

    public function retrieveById($id)
    {
        Payment\Entity::verifyIdAndStripSign($id);

        $txn = $this->txnRepo->findOrFail($id);

        return $txn;
    }
}
