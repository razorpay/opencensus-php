<?php

namespace RZP\Reconciliator\Base;

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

    protected $repo;

    protected $payment;
    protected $refund;

    protected $app;
    protected $messenger;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];

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
            $reconciled = $this->checkIfAlreadyReconciled($this->refund);

            if ($reconciled === true)
            {
                return;
            }

            // Increment the total count for the summary
            $this->setSummaryCount(self::TOTAL_SUMMARY, $refundId);

            // Validates that the payment status is not failed.
            $validate = $this->validatePaymentStatus();

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

            $this->app['trace']->traceException($ex);

            throw $ex;

            //return;
        }
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
        $this->app['trace']->info(
            TraceCode::RECON_FILE_ROW,
            $row
        );

        $refundId = $this->getRefundId($row);

        // If refund id is not present, return. No point of evaluating the row.
        if (empty($refundId) === true)
        {
            return null;
        }

        if (UniqueIdEntity::verifyUniqueId($refundId) === false)
        {
            $this->app['trace']->info(
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
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Refund not found in DB. -> ' . $ex->getMessage(),
                    'row'        => $row,
                    'refund_id'  => $refundId,
                    'gateway'    => get_called_class()
                ]);

            throw $ex;

            //return null;
        }

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

        $rowDetails = [
            BaseReconciliate::REFUND_ID => $refundId,
        ];

        return $rowDetails;
    }
}