<?php

namespace Gateway\AxisMigs;

use EE\Exception;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'AxisMigs';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'vpc_ReceiptNo'                 => 'sometimes|string|max:25');

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