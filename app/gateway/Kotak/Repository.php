<?php

namespace Gateway\Kotak;

use EE\Exception;
use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

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