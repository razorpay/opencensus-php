<?php

namespace Reconciliator\Base;

use EE\Exception\ReconciliationException;
use Models\Payment;
use Models\Card;
use Models\Card\IIN;
use Models\Transaction;
use Models\Payment\Verify;

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
    protected $cardRepo;
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
        $this->cardRepo        = $repo->card;
    }

    /**
     * This is the start of the actual reconciliation.
     * Reconciliation is done for each row in the file content.
     * Validates payment status.
     * Records gateway fees.
     * Records gateway service tax.
     * Sets card details (debit/credit, international).
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

            if ($reconciled === true)
            {
                return;
            }

            // Validates that the payment status is not failed.
            $validate = $this->validatePaymentStatus();

            if ($validate === true)
            {
                $this->persistReconciliationData($rowDetails);
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

            throw $ex;

            //return;
        }
    }

    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus !== Payment\Status::FAILED)
        {
            return true;
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_MISMATCH,
                'message'    => 'Payment status is failed. Trying to authorize.',
                'payment_id' => $this->payment->getId(),
                'gateway'    => get_called_class()
            ]);

        return $this->tryAuthorizeFailedPayment();
    }

    protected function tryAuthorizeFailedPayment()
    {
        $paymentService = new Payment\Service();

        // Try to make it authorized
        $verifyResponse = $paymentService->verifyPayment($this->payment);

        if ($verifyResponse === Verify::AUTHORIZED)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECONCILIATION_INFO_ALERT,
                    'message'    => 'Verify returned authorized.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]);
            
            // Set the payment transaction for the row.
            $this->paymentTransaction = $this->payment->transaction;

            return true;
        }

        if ($verifyResponse === Verify::SUCCESS)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                    'message'    => 'Verify returned failed. Payment is still in failed state.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]);

            return false;
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code'    => TraceCode::RECON_FAILED_VERIFY,
                'message'       => 'Verify command failed or unable to recognize the response.',
                'payment_id'    => $this->payment->getId(),
                'verify_status' => $verifyResponse,
                'gateway'       => get_called_class()
            ]);

        return false;
    }

    protected function persistReconciliationData($rowDetails)
    {
        $recordSuccess = $this->recordGatewayFeeAndServiceTax($rowDetails);

        if ($recordSuccess === true)
        {
            $this->persistReconciledAt($this->payment);
        }

        $this->persistCardDetailsIfAbsent($rowDetails);
    }

    protected function getRowDetailsStructured($row)
    {
        $this->app['trace']->info(
            TraceCode::RECONCILIATION_FILE_ROW,
            $row
        );

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

            if ($this->paymentTransaction === null)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code' => TraceCode::RECON_MISMATCH,
                        'message'    => 'Payment Transaction not found in DB.',
                        'row'        => $row,
                        'payment_id' => $paymentId,
                        'gateway'    => get_called_class()
                    ]);
            }
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

            throw $ex;

            //return null;
        }

        $cardDetails = $this->getCardDetails($row);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee = $this->getGatewayFee($row);

        $rowDetails = [
            BaseReconciliate::PAYMENT_ID          => $paymentId,
            BaseReconciliate::GATEWAY_SERVICE_TAX => $serviceTax,
            BaseReconciliate::GATEWAY_FEE         => $fee,
        ];

        $this->setCardDetailsInRowDetails($cardDetails, $rowDetails);

        return $rowDetails;
    }

    protected function setCardDetailsInRowDetails($cardDetails, & $rowDetails)
    {
        if (empty($cardDetails[BaseReconciliate::CARD_TYPE]) === false)
        {
            $rowDetails[BaseReconciliate::CARD_TYPE] = $cardDetails[BaseReconciliate::CARD_TYPE];
        }

        if (empty($cardDetails[BaseReconciliate::CARD_LOCALE]) === false)
        {
            $rowDetails[BaseReconciliate::CARD_LOCALE] = $cardDetails[BaseReconciliate::CARD_LOCALE];
        }
    }

    protected function persistCardDetailsIfAbsent($rowDetails)
    {
        if (empty($rowDetails[BaseReconciliate::CARD_TYPE]) === false)
        {
            $this->persistCardTypeIfAbsent($rowDetails[BaseReconciliate::CARD_TYPE]);
        }

        if (empty($rowDetails[BaseReconciliate::CARD_LOCALE]) === false)
        {
            $this->persistCardLocaleIfAbsent($rowDetails[BaseReconciliate::CARD_LOCALE]);
        }
    }


    /**
     * This function should be called only if the payment
     * is sure to have a corresponding entity for card.
     *
     * @param String $reconCardType
     * @throws ReconciliationException
     */
    protected function persistCardTypeIfAbsent($reconCardType)
    {
        $paymentIin = $this->payment->card->iinRelation;

        if ($paymentIin === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECONCILIATION_INFO_ALERT,
                    'message'         => 'IIN absent for the card.',
                    'card_id'         => $this->payment->card->getId(),
                    'payment_id'      => $this->payment->getId(),
                    'gateway'         => get_called_class()
                ]);

            return;
        }

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

                throw new ReconciliationException(
                    'Card types in recon file and db do not match.',
                    [
                        'recon_card_type' => $reconCardType,
                        'iin_card_type'   => $iinCardType,
                    ]
                );

                //return;
            }
        }
    }

    protected function persistCardLocaleIfAbsent($reconCardLocale)
    {
        if ($reconCardLocale === BaseReconciliate::INTERNATIONAL)
        {
            $reconInternational = true;
        }
        else
        {
            $reconInternational = false;
        }

        $paymentCard = $this->payment->card;

        $isCardInternational = $paymentCard->isInternational();

        if (empty($isCardInternational) === true)
        {
            $paymentCard->setInternational($reconInternational);
            $this->cardRepo->saveOrFail($paymentCard);
        }
        else
        {
            if ($isCardInternational !== $reconInternational)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'              => TraceCode::RECON_MISMATCH,
                        'message'                 => 'Card locales in recon file and db do not match.',
                        'recon_card_locale'       => $reconCardLocale,
                        'is_stored_international' => $isCardInternational,
                        'payment_id'              => $this->payment->getId(),
                        'gateway'                 => get_called_class()
                    ]);

                throw new ReconciliationException(
                    'Card locales in recon file and db do not match.',
                    [
                        'recon_card_locale'         => $reconCardLocale,
                        'is_stored_international'   => $isCardInternational,
                    ]
                );

                //return;

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

            throw new ReconciliationException(
                'Transaction not present for the given payment ID.',
                [
                    'row_details' => $rowDetails,
                    'gateway'     => get_called_class(),
                ]
            );

            //return false;
        }

        $currentGatewayFee = $this->paymentTransaction->getGatewayFee();
        $currentGatewayServiceTax = $this->paymentTransaction->getGatewayServiceTax();

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

                throw new ReconciliationException(
                    'Gateway fee in the recon file does not match with the one stored in API.',
                    [
                        'recon_gateway_fee' => $reconGatewayFee,
                        'api_gateway_fee'   => $currentGatewayFee,
                    ]
                );

                //return false;
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
                        'recon_gateway_service_tax' => $reconGatewayServiceTax,
                        'api_gateway_service_tax'   => $currentGatewayServiceTax,
                        'gateway'           => get_called_class(),
                    ]);

                throw new ReconciliationException(
                    'Gateway service tax in the recon file does not match with the one stored in API.',
                    [
                        'recon_gateway_service_tax' => $reconGatewayServiceTax,
                        'api_gateway_service_tax'   => $currentGatewayServiceTax,
                    ]
                );

                //return false;
            }
            return true;
        }
    }
}