<?php

namespace Reconciliator\Base;

use Models\Payment;
use Models\Card;
use Models\Card\IIN;
use Models\Transaction;

use Gateway\AxisMigs;

use Trace\TraceCode;
use App;

use Reconciliator\Orchestrator;
use Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Foundation\SubReconciliate
{
    /*******************
     * Instance objects
     *******************/

    protected $paymentRepo;
    protected $iinRepo;
    protected $transactionRepo;

    protected $payment;
    protected $paymentTransaction;

    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $repo = $this->app['repo'];

        $this->paymentRepo     = $repo->payment;
        $this->iinRepo         = $repo->iin;
        $this->transactionRepo = $repo->transaction;
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
            $this->runReconciliate($row, $extraDetails);
        }
    }

    public function runReconciliate($row, $extraDetails)
    {
        $rowDetails = $this->getRowDetailsStructured($row);

        if (empty($rowDetails) === true)
        {
            return;
        }

        try
        {
            $reconciled = $this->checkIfAlreadyReconciled($this->payment);

            if (($reconciled === true) or ($reconciled === null))
            {
                return;
            }

            // Validates that the payment status is not failed.
            $validate = $this->validatePaymentStatus();

            if ($validate === true)
            {
                $recordSuccess = $this->recordGatewayFeeAndServiceTax($rowDetails);

                if ($recordSuccess === true)
                {
                    $this->setReconciledAt($this->payment);
                }

                $this->setCardTypeIfAbsent($rowDetails[BaseReconciliate::CARD_TYPE]);
            }
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

            return;
        }
    }

    protected function getRowDetailsStructured($row)
    {
        $paymentId = $this->getPaymentId($row);

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        try
        {
            $this->payment = $this->paymentRepo->findOrFail($paymentId);
            $this->paymentTransaction = $this->payment->transaction;
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Payment or Payment Transaction not found in DB. -> ' . $ex->getMessage(),
                    'row'        => $row,
                    'payment_id' => $paymentId,
                    'gateway'    => get_called_class()
                ]);
            return null;
        }

        $cardType = $this->getCardType($row);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee = $this->getGatewayFee($row);

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

    protected function recordGatewayFeeAndServiceTax($rowDetails)
    {
        $reconGatewayFee = $rowDetails[BaseReconciliate::GATEWAY_FEE];
        $reconGatewayServiceTax = $rowDetails[BaseReconciliate::GATEWAY_SERVICE_TAX];


        if (($reconGatewayFee === null) or ($reconGatewayServiceTax === null))
        {
            return false;
        }

        if ($this->paymentTransaction === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_FAILURE,
                    'message'       => 'Transaction not present for the given payment ID.',
                    'row_details'   => $rowDetails,
                    'gateway'       => get_called_class()
                ]);

            return false;
        }

        $currentGatewayFee = $this->paymentTransaction->getGatewayFee();
        $currentGatewayServiceTax = $this->paymentTransaction->getServiceTax();

        $recordGatewayFeeSuccess = $this->recordGatewayFee($reconGatewayFee, $currentGatewayFee);

        if ($recordGatewayFeeSuccess === true)
        {
            $recordGatewayServiceTaxSuccess = $this->recordGatewayServiceTax($reconGatewayServiceTax,
                                                                             $currentGatewayServiceTax);

            if ($recordGatewayServiceTaxSuccess === true)
            {
                $this->paymentTransaction->saveOrFail();
                return true;
            }
        }

        return false;
    }

    protected function recordGatewayFee($reconGatewayFee, $currentGatewayFee)
    {
        if ($currentGatewayFee === 0)
        {
            $this->paymentTransaction->setGatewayFee($reconGatewayFee);
            return true;
        }
        else
        {
            if ($currentGatewayFee !== $reconGatewayFee)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'        => TraceCode::RECON_FAILURE,
                        'message'           => 'Gateway fee in the recon file does not match with the one stored in API.',
                        'recon_gateway_fee' => $reconGatewayFee,
                        'api_gateway_fee'   => $currentGatewayFee,
                        'gateway'           => get_called_class(),
                    ]);

                return false;
            }
            return true;
        }
    }

    protected function recordGatewayServiceTax($reconGatewayServiceTax, $currentGatewayServiceTax)
    {
        if ($currentGatewayServiceTax === 0)
        {
            $this->paymentTransaction->setGatewayServiceTax($reconGatewayServiceTax);
            return true;
        }
        else
        {
            if ($currentGatewayServiceTax !== $reconGatewayServiceTax)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'        => TraceCode::RECON_FAILURE,
                        'message'           => 'Gateway service tax in the recon file does not match with the one stored in API.',
                        'recon_gateway_fee' => $reconGatewayServiceTax,
                        'api_gateway_fee'   => $currentGatewayServiceTax,
                        'gateway'           => get_called_class(),
                    ]);

                return false;
            }
            return true;
        }
    }
}