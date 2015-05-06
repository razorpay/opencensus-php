<?php

namespace Gateway\AxisGenius;

use EE\Exception;
use Gateway\Atom;
use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'AxisGenius';

    public function findByMerchantTxnRef($merchantTxnRef)
    {
        $repo = $this->repo;

        return $repo::where('vpc_MerchTxnRef', '=', $merchantTxnRef)
                    ->firstOrFail();
    }

    public function findByMerchantTxnRefAndCommand($merchantTxnRef, $command)
    {
        $repo = $this->repo;

        return $repo::where('vpc_MerchTxnRef', '=', $merchantTxnRef)
                    ->where('vpc_Command', '=', $command)
                    ->firstOrFail();
    }

    public function findByPaymentIdAndCommand($paymentId, $command)
    {
        $repo = $this->repo;

        return $repo::where('payment_id', '=', $paymentId)
                    ->where('vpc_Command', '=', $command)
                    ->firstOrFail();
    }
}