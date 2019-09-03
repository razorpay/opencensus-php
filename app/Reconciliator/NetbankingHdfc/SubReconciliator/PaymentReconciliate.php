<?php

namespace RZP\Reconciliator\NetbankingHdfc\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\NetbankingHdfc\Constants;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    protected function getPaymentId(array $row)
    {
        $reconStatus = $this->getReconPaymentStatus($row);

        if ($reconStatus === Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            return null;
        }

        try
        {
            $this->gatewayPayment = $this->repo->netbanking->findByGatewayPaymentIdAndAction(
                                                                            $row[Constants::BANK_PAYMENT_ID],
                                                                            Action::AUTHORIZE);
        }
        catch (DBQueryException $ex)
        {
            // Just trace the exception and Do nothing.
            // This try-catch is needed, just to suppress the exception,
            // Else recon process gets terminated here and rows after this
            // current row do not get processed.
            //
            $this->trace->traceException($ex);
        }

        if ($this->gatewayPayment === null)
        {
            return $row[Constants::COLUMN_PAYMENT_ID] ?? null;
        }

        return $this->gatewayPayment->getPaymentId() ?? null;
    }

     protected function getReferenceNumber($row)
     {
         return $row[Constants::BANK_PAYMENT_ID] ?? null;
     }

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo->netbanking->findByPaymentIdAndAction($paymentId,
                                                               Action::AUTHORIZE);
    }

    /**
     * MIS contains failed payments also. If error code is non zero
     * and bank reference number is 0, status of payment is considered failed
     * otherwise success.
     *
     * @param array $row
     * @return null|string
     */
    protected function getReconPaymentStatus(array $row)
    {
        $errorCode = $row[Constants::ERROR_CODE] ?? 0;

        $bankPaymentId = $this->getReferenceNumber($row);

       if ((empty($errorCode) === false) and (empty($bankPaymentId) === true))
       {
            return Status::FAILED;
       }
    }

    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[Constants::COLUMN_PAYMENT_AMOUNT]);
    }
}
