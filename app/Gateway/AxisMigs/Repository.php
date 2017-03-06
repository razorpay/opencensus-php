<?php

namespace RZP\Gateway\AxisMigs;

use RZP\Exception;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'axis_migs';

    protected $appFetchParamRules = array(
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        'vpc_ReceiptNo'                 => 'sometimes|string|max:25',
        'received'                      => 'sometimes|in:0,1',
        'vpc_TransactionNo'             => 'sometimes|string|max:10',
        'vpc_ShopTransactionNo'         => 'sometimes|string|max:10',
        'vpc_TxnResponseCode'           => 'sometimes|string|max:10',
        'vpc_3DSstatus'                 => 'sometimes|string|max:2');

    public function findByMerchantTxnRef($merchantTxnRef)
    {
        return $this->newQuery()
                    ->where('vpc_MerchTxnRef', '=', $merchantTxnRef)
                    ->firstOrFail();
    }

    public function findByRrn($rrn)
    {
        return $this->newQuery()
                    ->where('vpc_ReceiptNo', '=', $rrn)
                    ->firstOrFail();
    }

    public function findByMerchantTxnRefAndCommand($merchantTxnRef, $command)
    {
        return $this->newQuery()
                    ->where('vpc_MerchTxnRef', '=', $merchantTxnRef)
                    ->where('vpc_Command', '=', $command)
                    ->firstOrFail();
    }

    public function findByPaymentIdAndCommandOrFail($paymentId, $command)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('vpc_Command', '=', $command)
                    ->firstOrFail();
    }

    public function getSuccessfullyRefundedEntities($paymentId, $refundAmount)
    {
        return $this->newQuery()
                    ->where('payment_id', '=', $paymentId)
                    ->where('vpc_Command', '=', Command::REFUND)
                    ->where('vpc_TxnResponseCode', '=', '0')
                    ->where('vpc_Amount', '=', $refundAmount)
                    ->get();
    }

    // TODO: Rename the function to a proper one
    // and fix the get auth code function for emi
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->firstOrFail();
    }

    public function countPaymentsNearTransactionNo($txnNo, $terminalId)
    {
        $txnNo = (int) $txnNo;

        return $this->newQuery()
                    ->where(function($query) use ($txnNo)
                    {
                        $query->where('vpc_TransactionNo', '=', $txnNo - 1)
                              ->orWhere('vpc_TransactionNo', '=', $txnNo + 1);
                    })
                    ->where('terminal_id', '=', $terminalId)
                    ->count();
    }
}
