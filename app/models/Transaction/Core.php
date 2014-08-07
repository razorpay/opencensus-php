<?php

namespace Models\Transaction;

use EE\Exception\BaseException;
use EE\Exception\BadRequestException;

use Models\Gateway;

use Models\Card;
use Models\Ledger;
use Models\Transaction;

use Trace\Trace;
use Trace\TraceCode;

class Core
{
    protected $txn;

    protected $trace;

    protected $txnRepo;

    public function __construct()
    {
        $this->trace = Trace::getInstance();

        $this->txnRepo = (new Transaction\Repository);
    }

    public function retrieveByIdAndMerchantId($id, $merchantId)
    {
        Transaction\Entity::verifyIdAndStripSign($id);

        $txn = $this->txnRepo->findByIdAndMerchantId($id, $merchantId);

        return $txn;
    }

    public function retrieveById($id)
    {
        Transaction\Entity::verifyIdAndStripSign($id);

        $txn = $this->txnRepo->findOrFail($id);

        return $txn;
    }
}
