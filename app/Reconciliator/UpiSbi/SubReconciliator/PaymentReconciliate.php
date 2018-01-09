<?php

namespace RZP\Reconciliator\UpiSbi;

use RZP\Gateway\Upi\Sbi\Action;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    /**
     * @see https://drive.google.com/drive/u/0/folders/0B1kf6HOmx7JBTmMzTXgwQVRrNm8
     */

    const ORDER_NUMBER       = 'order_no';
    const TRANS_REF_NUMBER   = 'trans_ref_no';
    const TRANSACTION_STATUS = 'transaction_status';
    const TRANSACTION_AMOUNT = 'transaction_amount';

    protected function getPaymentId(array $row)
    {
        return $row[self::ORDER_NUMBER];
    }

    protected function getReferenceNumber($row)
    {
        return $row[self::TRANS_REF_NUMBER] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = strtolower($row[self::TRANSACTION_STATUS]) ?? null;

        return Status::getPaymentStatus($status);
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'message'         => 'Payment amount mismatch',
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'currency'        => $this->payment->getCurrency(),
                    'row'             => $row,
                    'gateway'         => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        $paymentAmount = floatval($row[self::TRANSACTION_AMOUNT]) * 100;

        // We are converting to int after casting to string as PHP randomly
        // returns wrong int values due to differing floating point precisions
        // So something like intval(31946.0) may give 31945 or 31946.
        // Converting to string using number_format and then converting
        // is a hack to avoid this issue
        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->upi->findByPaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setNpciReferenceId($referenceNumber);
    }
}
