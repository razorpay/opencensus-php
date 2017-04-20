<?php

namespace RZP\Reconciliator\Base;

use RZP\Exception\LogicException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;

use App;
use RZP\Trace\TraceCode;
use RZP\Exception\ReconciliationException;

use RZP\Gateway\AxisMigs;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\Messenger;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class RefundReconciliate extends Foundation\SubReconciliate
{
    /*******************
     * Instance objects
     *******************/

    // This will need to be overridden in each gateway's refund recon.
    const COLUMN_REFUND_AMOUNT = '';

    protected $repo;
    protected $trace;
    protected $app;
    protected $messenger;

    protected $payment;
    protected $refund;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->trace = $this->app['trace'];

        $this->messenger = new Messenger();
    }

    /**
     * This is the start of the actual reconciliation for refunds.
     * Reconciliation is done for each row in the file content.
     * Validates payment status.
     * Sets the reconciled_at.
     *
     * @param array $fileContents
     * @return array
     */
    public function startReconciliation($fileContents)
    {
        $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($row, $extraDetails)
            {
                $this->runReconciliate($row, $extraDetails);
            });
        }

        return $this->getSummary();
    }

    public function runReconciliate($row, $extraDetails)
    {
        $rowDetails = $this->getRowDetailsStructured($row);

        if (empty($rowDetails) === true)
        {
            return;
        }

        $refundId = $rowDetails[BaseReconciliate::REFUND_ID];

        try
        {
            $this->runPreReconciledAtCheckRecon($rowDetails);

            $reconciled = $this->checkIfAlreadyReconciled($this->refund);

            if ($reconciled === true)
            {
                return;
            }

            // Increment the total count for the summary
            $this->setSummaryCount(self::TOTAL_SUMMARY, $refundId);

            $validate = $this->validateRefundDetails($row);

            if ($validate === true)
            {
                $persistSuccess = $this->persistReconciliationData();

                if ($persistSuccess === false)
                {
                    // Increment the failure count for the summary.
                    $this->setSummaryCount(self::FAILURES_SUMMARY, $refundId);
                }
            }
            else
            {
                // Increment the failure count for the summary.
                $this->setSummaryCount(self::FAILURES_SUMMARY, $refundId);
            }
        }
        catch (\Exception $ex)
        {
            // Ideally, there shouldn't be any exceptions thrown. They should be handled
            // in the respective reconciliation steps.

            // Increment the failure count for the summary.
            $this->setSummaryCount(self::FAILURES_SUMMARY, $refundId);

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_FAILURE,
                    'message'       => 'Unable to perform one of the reconciliation actions -> ' . $ex->getMessage(),
                    'row'           => $row,
                    'extra_details' => $extraDetails,
                    'gateway'       => get_called_class()
                ]);

            $this->trace->traceException($ex);

            throw $ex;

            //return;
        }
    }

    protected function getRefundAmount(array $row)
    {
        if (isset($row[static::COLUMN_REFUND_AMOUNT]) === false)
        {
            return null;
        }

        $refundAmount = floatval($row[static::COLUMN_REFUND_AMOUNT]) * 100;

        return $refundAmount;
    }

    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
        $this->persistGatewaySettledAt($this->refund, $rowDetails);
    }

    protected function validateRefundDetails(array $row)
    {
        $validPaymentStatus = $this->validatePaymentStatus();

        $validRefundAmount = $this->validateRefundAmountEqualsReconAmount($row);

        $validRefundDetails = ($validPaymentStatus and $validRefundAmount);

        return $validRefundDetails;
    }

    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus === Payment\Status::FAILED)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Payment status is failed.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    protected function persistReconciliationData()
    {
        $refundTransaction = $this->refund->transaction;

        if ($refundTransaction === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Refund transaction not found in DB.',
                    'refund_id'  => $this->refund->getId(),
                    'gateway'    => get_called_class()
                ]);

            return false;
        }

        // Sets the reconciled_at in the transactions entity, on a successful reconciliation.
        $this->persistReconciledAt($this->refund);

        return true;
    }

    protected function getRowDetailsStructured($row)
    {
        $this->trace->info(
            TraceCode::RECON_FILE_ROW,
            $row
        );

        $refund = $this->getApiRefundEntityFromRow($row);

        // If we cannot get the refund, return. No point of evaluating the row.
        if ($refund === null)
        {
            return null;
        }

        $refundId = $refund->getId();

        // Sets the corresponding payment for the refund.
        $this->payment = $this->refund->payment;

        // If payment is not present, return. There's something wrong with this transaction.
        if (empty($this->payment) === true)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Corresponding payment for the refund not found in DB.',
                    'row'        => $row,
                    'refund_id'  => $refundId,
                    'gateway'    => get_called_class()
                ]);

            throw new ReconciliationException(
                'Corresponding payment for the refund not found in the DB.',
                [
                    'refund_id' => $refundId,
                ]
            );

            //return null;
        }

        $gatewaySettledAt = $this->getGatewaySettledAt($row);

        $rowDetails = [
            BaseReconciliate::REFUND_ID             => $refundId,
            BaseReconciliate::GATEWAY_SETTLED_AT    => $gatewaySettledAt,
        ];

        return $rowDetails;
    }

    protected function getApiRefundEntityFromRow(array $row)
    {
        $refundId = $this->getRefundId($row);

        // If refund id is not present, return. No point of evaluating the row.
        if (empty($refundId) === true)
        {
            return null;
        }

        if (UniqueIdEntity::verifyUniqueId($refundId) === false)
        {
            $this->trace->info(
                [
                    'trace_code' => TraceCode::RECON_INFO_ALERT,
                    'message'    => 'Refund ID being sent in the file is not as expected.',
                    'row'        => $row,
                    'refund_id'  => $refundId,
                    'gateway'    => get_called_class()
                ]);

            return null;
        }

        try
        {
            $this->refund = $this->repo->refund->findOrFail($refundId);
        }
        catch (\Exception $ex)
        {
            $refundSuccess = $this->createRefundOnApi($row, $refundId, $ex);

            if ($refundSuccess === false)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code' => TraceCode::RECON_MISMATCH,
                        'message' => 'Unable to create a refund on API after finding it missing',
                        'row' => $row,
                        'refund_id' => $refundId,
                        'gateway' => get_called_class(),
                    ]);

                return null;
            }

            $this->refund = $this->repo->refund->findOrFail($refundId);
        }

        return $this->refund;
    }

    /**
     * This will create a refund on the API side. It will also check that
     * the refund on the gateway side is already created.
     *
     * The created refund and the refund id in the transaction entity
     * will have the refund ID set explicitly.
     *
     * Each gateway needs to implement this on its own.
     *
     * @param array      $row
     * @param string     $refundId
     * @param \Exception $ex
     *
     * @return bool returns true if successfully created. False otherwise.
     */
    protected function createRefundOnApi(array $row, string $refundId, \Exception $ex)
    {
        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_INFO_ALERT,
                'message'    => 'Refund not found in DB. -> ' . $ex->getMessage(),
                'row'        => $row,
                'refund_id'  => $refundId,
                'gateway'    => get_called_class()
            ]);

        $paymentId = $this->getPaymentId($row);

        $refundAmount = $this->getRefundAmount($row);

        $rrn = $this->getRRN($row);

        if (($paymentId === null) or ($refundAmount === null))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'row' => $row,
                    'message' => 'Unable to get the payment ID or amount from the refund recon file',
                    'refund_id' => $refundId,
                    'refund_amount' => $refundAmount,
                    'rrn' => $rrn,
                    'payment_id' => $paymentId,
                ]);

            return false;
        }

        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchant = $payment->merchant;

        $processor = new Payment\Processor\Processor($merchant);

        try
        {
            $processor->createRefundOnApiFromRecon($payment, $refundId, $refundAmount, $rrn);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            return false;
        }

        return true;
    }

    /**
     * Checks if amount in recon file matches the actual amount in refund entity
     * Implementation to be provided by child clasess
     *
     * @param  array $row Row data
     *
     * @return bool
     */
    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        return true;
    }

    /**
     * If this is being implemented in the child class,
     * the setter for storing the rrn should be present
     * in the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getRRN(array $row)
    {
        return null;
    }
}
