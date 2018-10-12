<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
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
    protected function setPaymentAndTransaction($row, $paymentId)
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
                    'info_code'  => 'PAYMENT_ABSENT',
                    'message'    => 'Payment not found in DB. -> ' . $ex->getMessage(),
                    'row'        => $row,
                    'payment_id' => $paymentId,
                    'gateway'    => $this->gateway
                ]);

            throw $ex;
        }
    }

    /**
     * We can not use Payment\Gateway::FORCE_AUTHORIZE_GATEWAYS here because
     * for the same gateway, based on method, we'll have to segregate this list.
     * For now, setting force autorize to true for all the emandate debit cases.
     *
     * @param Payment\Entity $payment
     */
    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = true;
    }

    protected function getRowDetailsStructured($row)
    {
        $rowDetails = parent::getRowDetailsStructured($row);

        $gatewayToken = $this->getGatewayToken($row);

        if (empty($gatewayToken) === false)
        {
            $rowDetails[BaseReconciliate::GATEWAY_TOKEN] = trim($gatewayToken);
        }

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
                $persistSuccess = $this->persistReconciliationData($rowDetails);

                if ($persistSuccess === false)
                {
                    // Increment the failure count for the summary.
                    $this->setSummaryCount(self::FAILURES_SUMMARY, $paymentId);
                }
            }
            else
            {
                // Increment the failure count for the summary.
                $this->setSummaryCount(self::FAILURES_SUMMARY, $paymentId);
            }
        }

        //
        // Payment can be updated from setPaymentAcquirerData before validation or
        // from markGatewayCapturedAsTrue after validation, for both cases we are
        // saving payment entity here from single location to save update queries
        //
        $this->repo->saveOrFail($this->payment);
    }
}
