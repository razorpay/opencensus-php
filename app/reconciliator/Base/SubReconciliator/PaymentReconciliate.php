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
    protected $paymentIin;
    protected $paymentTransaction;

    protected $app;
    protected $repo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];

        $this->paymentRepo     = $this->repo->payment;
        $this->iinRepo         = $this->repo->iin;
        $this->transactionRepo = $this->repo->transaction;
        $this->cardRepo        = $this->repo->card;
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
            $validate = $this->validatePaymentStatus($row);

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

    protected function validatePaymentStatus($row)
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus !== Payment\Status::FAILED)
        {
            return true;
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_INFO_ALERT,
                'message'    => 'Payment status is failed. Trying to authorize.',
                'payment_id' => $this->payment->getId(),
                'gateway'    => get_called_class()
            ]);

        return $this->tryAuthorizeFailedPayment($row);
    }

    protected function tryAuthorizeFailedPayment($row)
    {
        $paymentService = new Payment\Service();

        try
        {
            // Try to make it authorized
            $verifyResponse = $paymentService->verifyPayment($this->payment);
        }
        catch(\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                    'message'    => 'Verification/Authorization threw an exception. -> ' . $ex->getMessage(),
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]);

            $this->app['trace']->traceException($ex);

            return false;
        }

        if ($verifyResponse === Verify::AUTHORIZED)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'    => 'Verify returned authorized.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]
            );

            return $this->handleVerifyAuthorized();
        }

        if ($verifyResponse === Verify::SUCCESS)
        {
            return $this->handleVerifySuccess($row);
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

    protected function handleVerifySuccess($row)
    {
        $authorizeSuccess = $this->forceAuthorizeFailed($row);

        if ($authorizeSuccess === true)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'    => 'Verify did not authorize. Force authorized the failed payment.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class(),
                ]
            );

            return $this->handleVerifyAuthorized();
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                'message'    => 'Verify returned failed. Payment is still in failed state.',
                'payment_id' => $this->payment->getId(),
                'gateway'    => get_called_class()
            ]);

        return false;
    }


    /**
     * This should be implemented in the child class if the gateway requires
     * a force authorization from failed state. If no force authorization,
     * it means that the payment is still in failed state and hence
     * should return back false.
     *
     * @return bool
     */
    protected function forceAuthorizeFailed($row)
    {
        return false;
    }

    protected function handleVerifyAuthorized()
    {
        $this->payment = $this->paymentRepo->findOrFail($this->payment->getId());

        // Set the payment transaction for the row.
        $this->paymentTransaction = $this->payment->transaction;

        if ($this->paymentTransaction === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                    'message'    => 'Transaction is null after verifying and authorizing the payment.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]);

            return false;
        }

        return true;
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
            TraceCode::RECON_FILE_ROW,
            $row
        );

        $paymentId = $this->getPaymentId($row);

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        $this->setPaymentAndTransaction($row, $paymentId);

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

    protected function setPaymentAndTransaction($row, $paymentId)
    {
        try
        {
            $this->payment = $this->paymentRepo->findOrFail($paymentId);
            $this->paymentTransaction = $this->payment->transaction;

            //
            // It's possible that the payment is in failed state and hence the transaction
            // is not present. While validating the payment status, we check for failed status
            // and try to verify and authorize. We handle an empty transaction there.
            //
            if ($this->paymentTransaction === null)
            {
                // The row details are already traced and can be retrieved from Splunk.
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code' => TraceCode::RECON_INFO_ALERT,
                        'message'    => 'Payment Transaction not found in DB.',
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


    /**
     * If IIN is missing, a new IIN is created with the card type (debit/credit)
     * and card locale (domestic/international).
     * If IIN is already present, we persist the card type and the card locale.
     *
     * @param array $rowDetails
     */
    protected function persistCardDetailsIfAbsent($rowDetails)
    {
        $reconCardType = !empty($rowDetails[BaseReconciliate::CARD_TYPE]) ?
                         $rowDetails[BaseReconciliate::CARD_TYPE] :
                         null;

        $reconCardLocale = !empty($rowDetails[BaseReconciliate::CARD_LOCALE]) ?
                           $rowDetails[BaseReconciliate::CARD_LOCALE] :
                           null;

        // TODO: Handle this better.
        if (($reconCardType === null) and ($reconCardLocale === null))
        {
            return;
        }

        $this->paymentIin = $this->payment->card->iinRelation;

        if ($this->paymentIin === null)
        {
            $this->createMissingIin($reconCardType, $reconCardLocale);

            return;
        }

        if (empty($reconCardType) === false)
        {
            $this->persistCardType($reconCardType);
        }

        if (empty($reconCardLocale) === false)
        {
            $this->persistCardLocale($reconCardLocale);
        }

        $this->repo->saveOrFail($this->paymentIin);
    }

    /**
     * This function should be called only if the payment
     * is sure to have a corresponding entity for card.
     *
     * @param String $reconCardType
     * @throws ReconciliationException
     */
    protected function persistCardType($reconCardType)
    {
        // Assumption: This function will not be called if IIN is missing.
        // If IIN is missing, it will be created and this function will not be called.

        $iinCardType = $this->paymentIin->getType();

        if ((empty($iinCardType) === true) or ($iinCardType === Card\Type::UNKNOWN))
        {
            $this->paymentIin->setType($reconCardType);
        }
        else
        {
            $this->updateCardTypeIfRequired($iinCardType, $reconCardType);
        }
    }

    protected function updateCardTypeIfRequired($iinCardType, $reconCardType)
    {
        if ($iinCardType !== $reconCardType)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'         => 'Card types in recon file and db do not match. Updating.',
                    'recon_card_type' => $reconCardType,
                    'iin_card_type'   => $iinCardType,
                    'payment_id'      => $this->payment->getId(),
                    'gateway'         => get_called_class()
                ]);

            $this->paymentIin->setType($reconCardType);
        }
    }

    protected function createMissingIin($reconCardType, $reconCardLocale)
    {
        $this->app['trace']->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'         => 'IIN absent for the card. Creating.',
                'card_id'         => $this->payment->card->getId(),
                'payment_id'      => $this->payment->getId(),
                'gateway'         => get_called_class()
            ]);

        $card = $this->payment->card;

        $iinId = $card->getIin();
        $cardNetwork = $card->getNetwork();

        if ($reconCardLocale === BaseReconciliate::INTERNATIONAL)
        {
            $countryCode = null;
        }
        else
        {
            $countryCode = 'IN';
        }

        $entityAttributes = [
            IIN\Entity::IIN     => $iinId,
            IIN\Entity::NETWORK => $cardNetwork,
            IIN\Entity::TYPE    => $reconCardType,
            IIN\Entity::COUNTRY => $countryCode,
        ];

        $iin = (new IIN\Entity())->build($entityAttributes);

        $this->repo->saveOrFail($iin);
    }

    protected function persistCardLocale($reconCardLocale)
    {
        // Assumption: This function will not be called if IIN is missing.
        // If IIN is missing, it will be created and this function will not be called.

        //
        // If $reconCardLocale is not set/is null, we default it to domestic.
        //
        if ($reconCardLocale === BaseReconciliate::INTERNATIONAL)
        {
            $countryCode = null;
            $reconInternational = true;
        }
        else
        {
            $countryCode = 'IN';
            $reconInternational = false;
        }

        $currentInternational = $this->paymentIin->isInternational();

        if (($currentInternational === false) and ($reconInternational === true))
        {
            $this->paymentIin->setCountryCode($countryCode);
            // Make sure that international returns true in this case, after the country code is set.
            assert($this->paymentIin->isInternational);
        }
        else if (($currentInternational === true) and ($reconInternational === false))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'              => TraceCode::RECON_MISMATCH,
                    'message'                 => 'DB says international but recon says domestic',
                    'payment_id'              => $this->payment->getId(),
                    'iin_id'                  => $this->paymentIin->getId(),
                    'gateway'                 => get_called_class()
                ]);
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
                        'trace_code'                 => TraceCode::RECON_FAILURE,
                        'message'                    => 'Gateway service tax in the recon file does not match with the one stored in API.',
                        'recon_gateway_service_tax'  => $reconGatewayServiceTax,
                        'api_gateway_service_tax'    => $currentGatewayServiceTax,
                        'gateway'                    => get_called_class(),
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