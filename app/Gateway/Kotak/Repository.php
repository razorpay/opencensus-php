<?php

namespace RZP\Gateway\Kotak;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Kotak';

    public function findByTxnRefAndType($txnRef, $type)
    {
        $repo = $this->repo;

        return $repo::where('TxnRefNo', '=', $txnRef)
                    ->where('TxnType', '=', $type)
                    ->firstOrFail();
    }

    public function findByPaymentIdAndType($paymentId, $type)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $paymentId)
                    ->where('TxnType', '=', $command)
                    ->firstOrFail();
    }
}