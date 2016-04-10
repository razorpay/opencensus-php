<?php

namespace Gateway\AxisMigs;

use EE\Exception;
use Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'AxisMigs';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'vpc_ReceiptNo'                 => 'sometimes|string|max:25',
        'received'                      => 'sometimes|in:0,1',
        'vpc_TransactionNo'             => 'sometimes|string|max:10',
        'vpc_ShopTransactionNo'         => 'sometimes|string|max:10',
        'vpc_TxnResponseCode'           => 'sometimes|string|max:10');

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

    public function findByPaymentId($paymentId)
    {
        return $this->findByPaymentIdAndCommand($paymentId, 'pay');
    }

    public function countPaymentsNearTransactionNo($txnNo, $terminalId)
    {
        $txnNo = (int) $txnNo;

        return $this->newQuery()
                    ->where('vpc_TransactionNo', '=', $txnNo - 1)
                    ->where('vpc_TransactionNo', '=', $txnNo + 1)
                    ->where('terminal_id', '=', $terminalId)
                    ->count();
    }
}
