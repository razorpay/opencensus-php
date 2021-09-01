<?php

namespace RZP\Reconciliator\NetbankingHdfc\SubReconciliator;

use App;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Reconciliator\NetbankingHdfc\Constants;

class PaymentReconciliate extends Base\SubReconciliator\NbPlus\NbPlusServiceRecon
{
    const BLACKLISTED_COLUMNS = [
        Constants::COLUMN_CUSTOMER_EMAIL,
    ];

    protected function getPaymentId(array $row)
    {
        $reconStatus = $this->getReconPaymentStatus($row);

        if ($reconStatus === Status::FAILED)
        {
            $this->setFailUnprocessedRow(false);

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::MIS_FILE_PAYMENT_FAILED ,
                    'payment_id' => $row[Constants::COLUMN_PAYMENT_ID] ?? $row[Constants::BANK_PAYMENT_ID] ?? null,
                    'gateway'    => $this->gateway
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_PAYMENT_FAILED);

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
            if ((empty($row[Constants::COLUMN_PAYMENT_ID]) === false) and (strlen($row[Constants::COLUMN_PAYMENT_ID]) === 14))
            {
                return $row[Constants::COLUMN_PAYMENT_ID];
            }

            $this->trace->info(TraceCode::MISC_TRACE_CODE, ['fetching payment id from nbplus for payment id' => $row[Constants::COLUMN_PAYMENT_ID],
                                                                     'bankPaymentId' =>  $row[Constants::BANK_PAYMENT_ID]]);

            /**
             * In case the payment is not found in api DB due to incorrect payment id in file
             * Then fetch from nbplus using bank_transaction_id field
             */
            $request = [
                'fields'                 => ['payment_id'],
                'bank_transaction_ids'   => [$row[Constants::BANK_PAYMENT_ID]]
            ];

            $response = App::getFacadeRoot()['nbplus.payments']->fetchNbplusData($request, 'netbanking');

            if ((isset($response['count']) === true) and ($response['count'] > 0))
            {
                return $response['items'][$row[Constants::BANK_PAYMENT_ID]]['payment_id'];
            }
            else
            {
                // Just trace the exception and Do nothing.
                // This try-catch is needed, just to suppress the exception,
                // Else recon process gets terminated here and rows after this
                // current row do not get processed.
                //
                $this->trace->traceException($ex);
            }
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

    public function getGatewayPayment($paymentId)
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
        $reconRowAmount = $this->getReconPaymentAmount($row);

        if ($this->payment->getBaseAmount() !== $reconRowAmount)
        {
            // HDFC returns amount as Rs 1 in mis file but in our db we have Rs 0 registration
            // hence payment's base amount and recon file amount causes amount mismatch
            if (($this->payment->isRecurringTypeInitial() === true)
                and (($reconRowAmount === 100) and ($this->payment->getBaseAmount() === 0)))
            {
                return true;
            }

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
