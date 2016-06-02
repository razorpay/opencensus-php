<?php

namespace Reconciliator\Base;


use Models\Payment;
use Models\Card;
use Models\Card\IIN;
use Models\Transaction;
use Trace\TraceCode;

use Gateway\AxisMigs;

use Reconciliator\Orchestrator;

use Reconciliator\Base\Reconciliate as BaseReconciliate;


class PaymentReconciliate extends Foundation\SubReconciliate
{

    /*******************
     * Instance objects
     *******************/

    protected $gatewayRepo;
    protected $paymentRepo;
    protected $iinRepo;
    protected $transactionRepo;

    protected $payment;

    protected $app;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        // These are being used by the parent classes.
        $this->paymentRepo     = new Payment\Repository;
        $this->iinRepo         = new IIN\Repository;
        $this->gatewayRepo     = new AxisMigs\Repository;
        $this->transactionRepo = new Transaction\Repository;
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

                // Stores the gateway fees
                $this->recordGatewayFee($rowDetails[BaseReconciliate::GATEWAY_FEE]);

                // Stores the gateway service tax
                $this->recordGatewayServiceTax($rowDetails[BaseReconciliate::GATEWAY_SERVICE_TAX]);

                $this->setCardTypeIfAbsent($rowDetails[BaseReconciliate::CARD_TYPE]);
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
        // Gets payment ID
        $paymentId = $this->getPaymentId($row);

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        try
        {
            $this->payment = $this->paymentRepo->findOrFail($paymentId);
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Payment not found in DB. -> ' . $ex->getMessage(),
                    'row'        => $row,
                    'payment_id' => $paymentId,
                    'gateway'    => get_called_class()
                ]);
            return null;
        }

        $cardType = $this->getCardType($row);

        // Gets the gateway service tax
        $serviceTax = $this->getServiceTax($row);

        // Gets the gateway fees
        $fee = $this->getFee($row);

        $rowDetails = [
            BaseReconciliate::PAYMENT_ID          => $paymentId,
            BaseReconciliate::CARD_TYPE           => $cardType,
            BaseReconciliate::GATEWAY_SERVICE_TAX => $serviceTax,
            BaseReconciliate::GATEWAY_FEE         => $fee,
        ];

        return $rowDetails;
    }


    protected function setCardTypeIfAbsent($reconCardType)
    {
        if (empty($reconCardType) === true)
        {
            return;
        }

        $paymentIin = $this->payment->card->iinRelation;

        $iinCardType = $paymentIin->getType();

        if ((empty($iinCardType) === true) or ($iinCardType === Card\Type::UNKNOWN))
        {
            $paymentIin->setType($reconCardType);
            $this->iinRepo->saveOrFail($paymentIin);
        }
        else
        {
            if ($iinCardType !== $reconCardType)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'      => TraceCode::RECON_MISMATCH,
                        'message'         => 'Card types in recon file and db do not match.',
                        'recon_card_type' => $reconCardType,
                        'iin_card_type'   => $iinCardType,
                        'payment_id'      => $this->payment->getId(),
                        'gateway'         => get_called_class()
                    ]);
            }
        }
    }


    protected function recordGatewayFee($reconGatewayFee)
    {
        if ($reconGatewayFee === null)
        {
            return;
        }

        $transaction = $this->payment->transaction();

        if ($transaction === null)
        {
            // TODO: raise an alert about transaction being absent for an entity id.
        }

        $currentGatewayFee = $transaction->getGatewayFee();

        if ($currentGatewayFee === null)
        {
            $transaction->setGatewayFee($reconGatewayFee);
        }
        else
        {
            if ($currentGatewayFee !== $reconGatewayFee)
            {
                // TODO: raise an alert about stored service tax and recon service tax not being the same
            }
        }
    }


    protected function recordGatewayServiceTax($reconServiceTax)
    {
        if ($reconServiceTax === null)
        {
            return;
        }

        $transaction = $this->payment->transaction();

        if ($transaction === null)
        {
            // TODO: raise an alert about transaction being absent for an entity id.
        }

        $currentGatewayServiceTax = $transaction->getGatewayServiceTax();

        if ($currentGatewayServiceTax === null)
        {
            $transaction->setGatewayServiceTax($reconServiceTax);
        }
        else
        {
            if ($currentGatewayServiceTax !== $reconServiceTax)
            {
                // TODO: raise an alert about stored service tax and recon service tax not being the same
            }
        }
    }
}