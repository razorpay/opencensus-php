<?php

namespace Reconciliator\Base;

use Models\Payment;
use Models\Card;
use Models\Card\IIN;
use Models\Transaction;
use Models\Payment\Refund;

use App;
use Trace\TraceCode;

use Gateway\AxisMigs;
use Reconciliator\Orchestrator;
use Reconciliator\Base\Reconciliate as BaseReconciliate;


class RefundReconciliate extends Foundation\SubReconciliate
{
    /*******************
     * Instance objects
     *******************/

    protected $gatewayRepo;
    protected $paymentRepo;
    protected $refundRepo;
    protected $iinRepo;
    protected $transactionRepo;

    protected $payment;
    protected $refund;

    protected $app;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        // These are being used by the parent classes.
        $this->paymentRepo     = new Payment\Repository;
        $this->iinRepo         = new IIN\Repository;
        $this->gatewayRepo     = new AxisMigs\Repository;
        $this->transactionRepo = new Transaction\Repository;
        $this->refundRepo      = new Refund\Repository;
    }


    /**
     * This is the start of the actual reconciliation.
     * Reconciliation is done for each row in the file content.
     * Validates payment status.
     * Records gateway fees.
     * Records gateway service tax.
     * Sets card type (debit/credit).
     * Records rrn.
     *
     * @param array $fileContents
     */
    public function startReconciliation($fileContents)
    {
        $extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $rowDetails = $this->getRowDetailsStructured($row);

            if (empty($rowDetails) === true)
            {
                continue;
            }

            try
            {
                // Validates that the payment status is not failed.
                $this->validatePaymentStatus();

                $this->recordRrn();
            }
            catch (\Exception $ex)
            {
                // Ideally, there shouldn't be any exceptions thrown. They should be handled
                // in the respective reconciliation steps.

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_FAILURE,
                        'message'       => 'Unable to perform one of the reconciliation actions -> ' . $ex->getMessage(),
                        'row'           => $row,
                        'extra_details' => $extraDetails,
                        'gateway'       => get_called_class()
                    ]);

                $this->app['trace']->traceException($ex);

                continue;
            }
        }
    }


    protected function getRowDetailsStructured($row)
    {
        // Gets refund ID
        $refundId = $this->getRefundId($row);

        // If refund id is not present, return. No point of evaluating the row.
        if (empty($refundId) === true)
        {
            return null;
        }

        try
        {
            $this->refund = $this->refundRepo->findOrFail($refundId);
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

            return null;
        }

        // Sets the corresponding payment for the refund.
        $this->payment = $this->refund->payment;

        // If payment is not present, return. There's something wrong with this transaction.
        if (empty($this->payment) === true)
        {
            return null;
        }

        $rowDetails = [
            BaseReconciliate::REFUND_ID => $refundId,
        ];

        return $rowDetails;
    }


    protected function recordRrn()
    {
        // TODO: Figure out what to do here.
    }
}