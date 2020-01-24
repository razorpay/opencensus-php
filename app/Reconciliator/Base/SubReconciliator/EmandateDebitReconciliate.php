<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Payment\Processor\Processor;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class EmandateDebitReconciliate extends PaymentReconciliate
{
    /**
     * Override this from PaymentReconciliator because transactions does
     * not exist for the payments yet, since they're not authorized now.
     *
     * @param $row
     * @param $paymentId
     * @throws \Exception
     */
    public function setPaymentAndTransaction($row, $paymentId)
    {
        try
        {
            $this->payment = $this->paymentRepo->findOrFail($paymentId);
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'info_code'  => InfoCode::PAYMENT_ABSENT,
                    'message'    => 'Payment not found in DB. -> ' . $ex->getMessage(),
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway
                ]);
        }
    }

    protected function getRowDetailsStructured($row)
    {
        $rowDetails = parent::getRowDetailsStructured($row);

        $gatewayStatusCode = $this->getGatewayStatusCode($row);

        $gatewayToken = $this->getGatewayToken($row);

        $gatewayErrorCode = $this->getGatewayErrorCode($row);

        $gatewayErrorDescription = $this->getGatewayErrorDescription($row);

        $rowDetails = array_merge(
            $rowDetails,
            [
                BaseReconciliate::GATEWAY_TOKEN       => $gatewayToken,
                BaseReconciliate::GATEWAY_ERROR_CODE  => $gatewayErrorCode,
                BaseReconciliate::GATEWAY_ERROR_DESC  => $gatewayErrorDescription,
                BaseReconciliate::GATEWAY_STATUS_CODE => $gatewayStatusCode,
            ]
        );

        return $rowDetails;
    }

    /**
     * If this is being implemented in the child class.
     * The gateway token would be used to assert with the value
     * currently present in the token entity.
     *
     * @param $row
     * @return null
     */
    protected function getGatewayToken(array $row)
    {
        return null;
    }

    /**
     * Gateway status code would be stored in the gateway payment entity
     * if this is used in child class.
     *
     * @param array $row
     * @return null
     */
    protected function getGatewayStatusCode(array $row)
    {
        return null;
    }

    /**
     * To be overridden in the child classes
     * We can map the failure code in the GatewayErrorException and thus store it in
     * payment entity if it fails
     *
     * @return null
     */
    protected function getGatewayErrorCode(array $row)
    {
        return null;
    }

    /**
     * To be overridden in the child classes
     *
     * @return null
     */
    protected function getGatewayErrorDescription(array $row)
    {
        return null;
    }

    /**
     * Here, we check if the payment status is anything other than created.
     * The reason for this is, during emandate debit reconciliation process, we
     * move the payment to success or failure.
     *
     * @param $payment
     * @return bool
     */
    protected function checkIfAlreadyReconciled($payment)
    {
        if ($payment->getStatus() === Payment\Status::CREATED)
        {
            // If transaction is not present, it would mean that
            // the reconciliation did not happen for this.
            return false;
        }

        return true;
    }

    /**
     * Overriding from PaymentReconciliate because:
     * 1. We do not need to check if the payment is in failed state
     * 2. Authorize Failed does not apply in emandate debit recon
     *
     * @param $row
     * @return bool
     */
    protected function validatePaymentStatus($row)
    {
        return true;
    }

    protected function processReconciliationRow($row, $rowDetails, $paymentId)
    {
        // Setting reconciled attribute before pre Reconciled check to check for duplicate row
        $this->reconciled = $this->checkIfAlreadyReconciled($this->payment);

        // Increment the total count for the summary
        $this->setSummaryCount(self::TOTAL_SUMMARY, $paymentId);

        $this->runPreReconciledAtCheckRecon($rowDetails);

        if ($this->reconciled === true)
        {
            $this->handleAlreadyReconciled($paymentId);

            //
            // Record gateway fee and service tax for reconciled payments
            //
            $this->recordMissingGatewayFeeAndServiceTax($rowDetails);
        }
        else
        {
            $validate = $this->validatePaymentDetails($row);

            if ($validate === true)
            {
                $paymentProcessed = $this->processPayment($row, $rowDetails);

                $this->persistReconciliationData($rowDetails, $row);

                /**
                 * We mark the recon as failed if payment status mapping
                 * is not found from the status in file
                 */
                if ($paymentProcessed === false)
                {
                    // Increment the failure count for the summary.
                    $this->setSummaryCount(self::FAILURES_SUMMARY, $paymentId);
                }
                else
                {
                    $this->setSummaryCount(self::SUCCESSES_SUMMARY, $paymentId);
                }
            }
            else
            {
                // Increment the failure count for the summary.
                $this->setSummaryCount(self::FAILURES_SUMMARY, $paymentId);
            }
        }
    }

    /**
     * Here we mark the payment as authorized or failed depending on the bank's status
     * Returns true if the payment gets processed, false otherwise
     *
     * @param array $row
     * @param array $rowDetails
     * @return bool
     */
    protected function processPayment(array $row, array $rowDetails)
    {
        $reconPaymentStatus = $this->getReconPaymentStatus($row);

        $merchant = $this->payment->merchant;

        $processor = new Processor($merchant);

        $processor->setPayment($this->payment);

        if ($reconPaymentStatus === Payment\Status::FAILED)
        {
            //
            // If the payment status is failed in recon, we need
            // to mark the payment as failed  and update the
            // related entities + push events for the same.
            //
            $this->failPayment($processor, $rowDetails);
        }
        else if ($reconPaymentStatus === Payment\Status::AUTHORIZED)
        {
            //
            // The payment gets authorized once the recon details
            // are confirmed. This will also create a transaction.
            //
            $processor->processAuth($this->payment);

            $this->paymentTransaction = $this->payment->reload()->transaction;

            $this->persistReconciledAt($this->payment);
        }
        else
        {
            return false;
        }

        return true;
    }

    /**
     * Mark the payment as failed with the appropriate error code mapping
     *
     * @param Processor $processor
     * @param array $rowDetails
     */
    protected function failPayment(Processor $processor, array $rowDetails)
    {
        //
        // If payment's status is already failed, we don't
        // need to update the payment status. This can happen
        // if we upload the same file again. `alreadyReconciled`
        // check won't be of use here since transaction won't
        // be created at all, since it's a failed payment.
        //
        if ($this->payment->isFailed() === true)
        {
            return;
        }

        $apiErrorCode = $this->getApiErrorCodeMapped($rowDetails);

        $exception = new Exception\GatewayErrorException(
            $apiErrorCode,
            $rowDetails[BaseReconciliate::GATEWAY_ERROR_CODE],
            $rowDetails[BaseReconciliate::GATEWAY_ERROR_DESC]);

        // Update payment status failed and send the corresponding events
        $processor->updatePaymentAuthFailed($exception);
    }

    /**
     * Overriden to set the gateway payment status as well
     *
     * @param array $rowDetails
     */
    protected function persistGatewayData(array $rowDetails)
    {
        $gatewayPayment = $this->updateAndFetchGatewayPayment();

        if ($gatewayPayment === null)
        {
            return;
        }

        $this->persistReferenceNumber($rowDetails, $gatewayPayment);

        $this->persistAccountDetails($rowDetails, $gatewayPayment);

        $this->persistGatewayTransactionId($rowDetails, $gatewayPayment);

        $this->persistGatewayPaymentDate($rowDetails, $gatewayPayment);

        $this->persistCustomerDetails($rowDetails, $gatewayPayment);

        $this->persistGatewayStatusCode($rowDetails, $gatewayPayment);

        $this->repo->saveOrFail($gatewayPayment);
    }

    /**
     * Child implementation classes can override this to set gateway specific
     * gateway payment data
     *
     * @param $rowDetails
     * @param $gatewayPayment
     */
    protected function persistGatewayStatusCode($rowDetails, $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::GATEWAY_STATUS_CODE]) === true)
        {
            return;
        }

        $gatewayStatusCode = $rowDetails[BaseReconciliate::GATEWAY_STATUS_CODE];

        $gatewayPayment->setStatus($gatewayStatusCode);
    }

    /*
     * Overriding this here, since we do not want to increse the success count here.
     */
    protected function persistReconciledAt($entity, string $reconciledType = ReconciledType::MIS)
    {
        $transaction = $entity->transaction;
        $time = time();
        $transaction->setReconciledAt($time);
        $transaction->saveOrFail();

        $this->pushSuccessReconMetrics($entity);
    }

    /**
     * Used for updating ARN and AuthCode in the parent function.
     * Override this because:
     * 1. ARN and AuthCode does not exist for emandate debit payments
     * 2. We do not save payment entity at the end of recon process here
     *
     * @param $rowDetails
     */
    protected function setPaymentAcquirerData($rowDetails)
    {
        return;
    }

    /**
     * Overriding this here because:
     * 1. We can not map the gateway captured field now,
     *    because the payment is not yet authorized.
     * 2. We do not save payment entity at the end of recon process here
     */
    protected function markGatewayCapturedAsTrue()
    {
        return;
    }

    /**
     * Override this in the gateway implementation to map error codes
     *
     * @param array $rowDetails
     * @return string
     */
    protected function getApiErrorCodeMapped(array $rowDetails)
    {
        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    public function recordGatewayFeeAndServiceTax($rowDetails)
    {
        // check failure case, for failed payments this function should not execute
        if (($this->payment->isFailed() === true) or ($this->payment->isCreated() === true))
        {
            return;
        }

        parent::recordGatewayFeeAndServiceTax($rowDetails);
    }
}
