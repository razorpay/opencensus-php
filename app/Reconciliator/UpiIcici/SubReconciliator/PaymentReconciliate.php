<?php

namespace RZP\Reconciliator\UpiIcici;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Status;
use RZP\Gateway\Upi\Icici\Status as UpiStatus;

class PaymentReconciliate extends Base\PaymentReconciliate
{
    const MERCHANT_TRAN_ID = 'merchanttranid';
    const SERVICE_TAX      = 'service_tax';
    const COMMISSION       = 'commission';
    const STATUS           = 'status';
    const AMOUNT           = 'amount';

    protected function getPaymentId(array $row)
    {
        $paymentId =  $row[self::MERCHANT_TRAN_ID];

        $payment = $this->repo->payment->find($paymentId);

        if ($payment !== null)
        {
            return $paymentId;
        }

        $bharatQr = $this->repo->bharat_qr->findByMerchantReference($paymentId);

        if ($bharatQr === null)
        {
           $this->alertUnexpectedBharatQrPayment($row);

            $this->setFailUnprocessedRow(true);

            return null;
        }

        return $bharatQr->payment->getId();
    }

    protected function alertUnexpectedBharatQrPayment(array $row)
    {
        $this->trace->info(TraceCode::BHARAT_QR_UNEXPECTED, [
            'message'       => 'Unexpected Bharat Qr Payment',
            'id'            => $row[self::MERCHANT_TRAN_ID],
            'row'           => $row,
        ]);
    }

    protected function getGatewayFee($row)
    {
        return $row[self::COMMISSION] ?? null;
    }

    protected function getGatewayServiceTax($row)
    {
        return $row[self::SERVICE_TAX] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        // If status is not set, assuming status to be failed
        $status = $row[self::STATUS];

        if ($status === UpiStatus::SUCCESS)
        {
            return Status::AUTHORIZED;
        }
        else if ($this->isPaymentStatusFailed($status) === true)
        {
            return Status::FAILED;
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_CRITICAL_ALERT,
                'message'    => 'Recon status is neither success, rejected or failed',
                'payment_id' => $this->payment->getId(),
                'gateway'    => get_called_class()
            ]);

        return Status::FAILED;
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

    private function getReconPaymentAmount(array $row)
    {
        return Base\Helper::getIntegerFormattedAmount($row[self::AMOUNT]);
    }

    private final function isPaymentStatusFailed(string $status)
    {
        return (($status === UpiStatus::REJECT) || ($status === UpiStatus::FAILURE));
    }
}
