<?php

namespace Reconciliator\Base\SubReconciliator;

use Models\Payment;
use Models\Card\IIN;
use Models\Transaction;
use Models\Payment\Refund;

use Trace\TraceCode;

use Gateway\AxisMigs;

use Reconciliator\Orchestrator;

use Reconciliator\Base\Reconciliate as BaseReconciliate;


class RefundReconciliate
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

                $this->setCardTypeIfAbsent($rowDetails[BaseReconciliate::CARD_TYPE]);

                $this->recordRrn();
            }
            catch (\Exception $ex)
            {
                // Ideally, there shouldn't be any exceptions thrown. They should be handled
                // in the respective reconciliation steps.

                $this->messenger->raiseReconAlert([ 'trace_code' => TraceCode::RECON_FAILURE,
                                                    'message' => 'Unable to perform one of the reconciliation 
                                                                  actions -> ' . $ex->getMessage(),
                                                    'row' => $row,
                                                    'extra_details' => $extraDetails,
                                                    'gateway' => get_called_class()], true
                );
                
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
            $this->messenger->raiseReconAlert([ 'trace_code' => TraceCode::RECON_MISMATCH,
                                                'message' => 'Refund not found in DB. -> ' . $ex->getMessage(),
                                                'row' => $row,
                                                'refund_id' => $refundId,
                                                'gateway' => get_called_class()], true
            );
            return null;
        }

        // Sets the corresponding payment for the refund.
        $this->payment = $this->refund->payment;

        // If payment is not present, return. There's something wrong with this transaction.
        if (empty($this->payment) === true)
        {
            return null;
        }

        // Gets the card type details
        $cardType = $this->getCardType($row);

        // Gets the gateway service tax
        $serviceTax = $this->getServiceTax($row);

        // Gets the gateway fees
        $fee = $this->getFee($row);

        // Assign values to return
        $rowDetails = [
            BaseReconciliate::REFUND_ID           => $refundId,
            BaseReconciliate::CARD_TYPE           => $cardType,
            BaseReconciliate::GATEWAY_SERVICE_TAX => $serviceTax,
            BaseReconciliate::GATEWAY_FEE         => $fee,
        ];

        return $rowDetails;
    }


    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus === Payment\Status::FAILED)
        {
            $this->messenger->raiseReconAlert([ 'trace_code' => TraceCode::RECON_MISMATCH,
                                                'message' => 'Payment status is failed.',
                                                'payment_id' => $this->payment->getId(),
                                                'gateway' => get_called_class()], true
            );
        }
    }


    protected function setCardTypeIfAbsent($reconCardType)
    {
        if (empty($reconCardType) === true)
        {
            return;
        }

        $paymentIin = $this->payment->card->iinRelation;

        $iinCardType = $paymentIin->getType();

        if (empty($iinCardType) === true)
        {
            $paymentIin->setType($reconCardType);
            $this->iinRepo->saveOrFail($paymentIin);
        }
        else
        {
            if ($iinCardType !== $reconCardType)
            {
                $this->messenger->raiseReconAlert([ 'trace_code' => TraceCode::RECON_MISMATCH,
                                                    'message' => 'Card types in recon file and db do not match.',
                                                    'recon_card_type' => $reconCardType,
                                                    'iin_card_type' => $iinCardType,
                                                    'payment_id' => $this->payment->getId(),
                                                    'gateway' => get_called_class()], true
                );
            }
        }
    }


    protected function recordRrn()
    {
        // TODO: Figure out what to do here.
    }
}