<?php

namespace RZP\Reconciliator\Base;

use RZP\Exception\ReconciliationException;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Transaction;
use RZP\Models\Payment\Verify\Result as VerifyResult;
use RZP\Reconciliator\Messenger;

use RZP\Gateway\AxisMigs;

use Rzp\Trace\TraceCode;
use App;
use RZP\Models\Base\PublicCollection;

use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Foundation\SubReconciliate
{
    const GATEWAY_FEES_ABSENT_GATEWAYS = [
        Orchestrator::KOTAK
    ];

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
    protected $messenger;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->messenger = new Messenger();

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

        $paymentId = $rowDetails[BaseReconciliate::PAYMENT_ID];

        try
        {
            $this->runPreReconciledAtCheckRecon($rowDetails);

            $reconciled = $this->checkIfAlreadyReconciled($this->payment);

            if ($reconciled === true)
            {
                return;
            }

            // Increment the total count for the summary
            $this->setSummaryCount(self::TOTAL_SUMMARY, $paymentId);

            // Validates that the payment status is not failed.
            $validate = $this->validatePaymentStatus($row);

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
        catch (\Exception $ex)
        {
            // Ideally, there shouldn't be any exceptions thrown. They should be handled
            // in the respective reconciliation steps.

            // Increment the failure count for the summary.
            $this->setSummaryCount(self::FAILURES_SUMMARY, $paymentId);

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

    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
        $this->persistGatewaySettledAt($this->payment, $rowDetails);
    }

    protected function validatePaymentStatus($row)
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus !== Payment\Status::FAILED)
        {
            return true;
        }

        $this->app['trace']->info(
            TraceCode::RECON_INFO,
            [
                'message'    => 'Payment status is failed. Trying to authorize.',
                'payment_id' => $this->payment->getId(),
                'gateway'    => get_called_class()
            ]);

        return $this->tryAuthorizeFailedPayment($row);
    }

    protected function tryAuthorizeFailedPayment($row)
    {
        $paymentService = new Payment\Service;

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

        if ($verifyResponse === VerifyResult::AUTHORIZED)
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO,
                [
                    'message'    => 'Verify returned authorized.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]
            );

            return $this->handleVerifyAuthorized();
        }

        if ($verifyResponse === VerifyResult::SUCCESS)
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

        $this->persistGatewaySettledAt($this->payment, $rowDetails);

        return $recordSuccess;
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

        $gatewaySettledAt = $this->getGatewaySettledAt($row);

        $rowDetails = [
            BaseReconciliate::PAYMENT_ID            => $paymentId,
            BaseReconciliate::GATEWAY_SERVICE_TAX   => $serviceTax,
            BaseReconciliate::GATEWAY_FEE           => $fee,
            BaseReconciliate::GATEWAY_SETTLED_AT    => $gatewaySettledAt,
        ];

        // For wallets and netbanking, $cardDetails would be empty.
        // We do an array_filter because sometimes, due to parsing errors,
        // we may not be able to get some card details which we would have
        // expected to get, due to which their corresponding values would be null.
        if (empty(array_filter($cardDetails)) === false)
        {
            $rowDetails[BaseReconciliate::CARD_DETAILS] = array_filter($cardDetails);
        }

        return $rowDetails;
    }

    /**
     * For wallets and netbanking, there will be no card, hence we
     * send an empty array for these payment methods.
     *
     * @param $row
     * @return array
     */
    protected function getCardDetails($row)
    {
        return [];
    }

    /**
     * A few netbanking gateways do not provide us with
     * gateway service tax in their reconciliation files.
     * For them, we mark the gateway service tax as null.
     *
     * @return null
     */
    protected function getGatewayServiceTax($row)
    {
        return null;
    }

    /**
     * A few netbanking gateways do not provide us with
     * gateway fees in their reconciliation files.
     * For them, we mark the gateway fees as null.
     *
     * @return null
     */
    protected function getGatewayFee($row)
    {
        return null;
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
                $this->app['trace']->info(
                    TraceCode::RECON_INFO,
                    [
                        'message'    => 'Payment Transaction not found in DB.',
                        'info_code'  => 'PAYMENT_TRANSACTION_ABSENT',
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

    /**
     * If IIN is missing, a new IIN is created with the card type (debit/credit)
     * and card locale (domestic/international).
     * If IIN is already present, we persist the card type and the card locale.
     *
     * @param array $rowDetails
     */
    protected function persistCardDetailsIfAbsent($rowDetails)
    {
        if (empty($rowDetails[BaseReconciliate::CARD_DETAILS]) === true)
        {
            return;
        }

        $cardDetails = $rowDetails[BaseReconciliate::CARD_DETAILS];

        $this->paymentIin = $this->payment->card->iinRelation;

        if ($this->paymentIin === null)
        {
            $this->createMissingIin($cardDetails);

            return;
        }

        $this->persistCardType($cardDetails[BaseReconciliate::CARD_TYPE]);

        if (empty($cardDetails[BaseReconciliate::CARD_LOCALE]) === false)
        {
            $this->persistCardLocale($cardDetails[BaseReconciliate::CARD_LOCALE]);
        }

        if (empty($cardDetails[BaseReconciliate::CARD_TRIVIA]) === false)
        {
            $this->persistCardTrivia($cardDetails[BaseReconciliate::CARD_TRIVIA]);
        }

        if (empty($cardDetails[BaseReconciliate::ISSUER]) === false)
        {
            $this->persistIssuer($cardDetails[BaseReconciliate::ISSUER]);
        }

        $this->repo->saveOrFail($this->paymentIin);
    }

    protected function persistIssuer($reconIssuer)
    {
        $iinIssuer = $this->paymentIin->getIssuer();

        if (empty($iinIssuer) === true)
        {
            $this->paymentIin->setIssuer($reconIssuer);
        }
        else
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'    => 'IIN_ISSUER_ALREADY_PRESENT',
                    'message'      => 'IIN already contains issuer. Not updating it.',
                    'payment_id'   => $this->payment->getId(),
                    'iin_id'       => $this->paymentIin->getKey(),
                    'recon_issuer' => $reconIssuer,
                    'iin_issuer'   => $iinIssuer,
                    'gateway'      => get_called_class()
                ]);
        }
    }

    protected function persistCardTrivia($reconCardTrivia)
    {
        $iinTrivia = $this->paymentIin->getTrivia();

        if (empty($iinTrivia) === true)
        {
            $this->paymentIin->setTrivia($reconCardTrivia);
        }
        else
        {
            $this->app['trace']->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'         => 'IIN_TRIVIA_ALREADY_PRESENT',
                    'message'           => 'IIN already contains trivia. Not updating it.',
                    'payment_id'        => $this->payment->getId(),
                    'iin_id'            => $this->paymentIin->getKey(),
                    'recon_card_trivia' => $reconCardTrivia,
                    'iin_card_trivia'   => $iinTrivia,
                    'gateway'           => get_called_class()
                ]);
        }
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
                    'info_code'       => 'CARD_TYPE_MISMATCH',
                    'recon_card_type' => $reconCardType,
                    'iin_card_type'   => $iinCardType,
                    'payment_id'      => $this->payment->getId(),
                    'gateway'         => get_called_class()
                ]);

            $this->paymentIin->setType($reconCardType);
        }
    }

    protected function createMissingIin($reconCardDetails)
    {
        $this->app['trace']->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'     => 'IIN absent for the card. Creating.',
                'info_code'   => 'IIN_CREATE',
                'card_id'     => $this->payment->card->getId(),
                'payment_id'  => $this->payment->getId(),
                'gateway'     => get_called_class()
            ]);

        $reconCardType = null;
        $reconCardLocale = null;

        //
        // Card type should always be set to create an IIN. Otherwise,
        // the IIN validator will throw an error.
        //
        $reconCardType = $reconCardDetails[BaseReconciliate::CARD_TYPE];

        if (empty($reconCardDetails[BaseReconciliate::CARD_LOCALE]) === false)
        {
            $reconCardLocale = $reconCardDetails[BaseReconciliate::CARD_LOCALE];
        }

        $card = $this->payment->card;

        $iinId = $card->getIin();
        $cardNetwork = $card->getNetwork();

        // If the reconCardLocale is not set (null), then we set
        // the country code to India.
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

        if (empty($reconCardDetails[BaseReconciliate::ISSUER]) === false)
        {
            $entityAttributes[IIN\Entity::ISSUER] = $reconCardDetails[BaseReconciliate::ISSUER];
        }

        if (empty($reconCardDetails[BaseReconciliate::CARD_TRIVIA]) === false)
        {
            $entityAttributes[IIN\Entity::TRIVIA] = $reconCardDetails[BaseReconciliate::CARD_TRIVIA];
        }

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
            $this->paymentIin->setCountry($countryCode);

            // Make sure that international returns true in this case, after the country code is set.
            assertTrue($this->paymentIin->isInternational());

            $this->app['trace']->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => 'IIN_INTERNATIONAL_SET',
                    'message'    => 'Setting an IIN to international.',
                    'iin_id'     => $this->paymentIin->getKey(),
                    'gateway'    => get_called_class(),
                    'payment_id' => $this->payment->getId(),
                ]
            );
        }
        else if (($currentInternational === true) and ($reconInternational === false))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'  => TraceCode::RECON_MISMATCH,
                    'message'     => 'DB says international but recon says domestic',
                    'payment_id'  => $this->payment->getId(),
                    'iin_id'      => $this->paymentIin->getKey(),
                    'gateway'     => get_called_class()
                ]);
        }
    }

    protected function recordGatewayFeeAndServiceTax($rowDetails)
    {
        $reconGatewayFee = $rowDetails[BaseReconciliate::GATEWAY_FEE];
        $reconGatewayServiceTax = $rowDetails[BaseReconciliate::GATEWAY_SERVICE_TAX];

        $calledClass = get_called_class();

        $nullTaxAndFeesAllowed = $this->isNullGatewayFeesAndTaxAllowed($calledClass);

        if ((($reconGatewayFee === null) or ($reconGatewayServiceTax === null)) and
            ($nullTaxAndFeesAllowed === false))
        {
            return false;
        }

        if ($this->paymentTransaction === null)
        {
            $createTransactionSuccess = $this->attemptToCreateMissingPaymentTransaction();

            if ($createTransactionSuccess === false)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_FAILURE,
                        'failure_code'  => 'PAYMENT_TRANSACTION_ABSENT',
                        'message'       => 'Transaction not present for the given payment ID.',
                        'row_details'   => $rowDetails,
                        'gateway'       => get_called_class()
                    ]);

                return false;
            }

            // Refresh both payment and transaction to get latest changes.
            // Reload txn because relation are cached.
            $this->paymentTransaction = $this->payment->reload()->transaction->reload();
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

    protected function isNullGatewayFeesAndTaxAllowed($calledClass)
    {
        foreach (self::GATEWAY_FEES_ABSENT_GATEWAYS as $gatewayFeesAbsentGateway)
        {
            $checkClass = 'RZP\\Reconciliator\\' . studly_case($gatewayFeesAbsentGateway) . '\\PaymentReconciliate';

            if ($calledClass === $checkClass)
            {
                return true;
            }
        }

        return false;
    }

    protected function attemptToCreateMissingPaymentTransaction()
    {
        $cardNetwork = null;

        $card = $this->payment->card;

        if ($card !== null)
        {
            $cardNetwork = $card->getNetworkCode();
        }

        $isHDFCDICL = ($cardNetwork === Card\Network::DICL) and
                      ($this->payment->isGateway(Payment\Gateway::HDFC) === true);

        $isNotCapturedButAuthorized = ($this->payment->isCaptured() === false) and
                                      ($this->payment->hasBeenAuthorized() === true);

        if (($isHDFCDICL === true) or ($isNotCapturedButAuthorized === true))
        {
            try
            {
                $this->createMissingPaymentTransaction();

                return true;
            }
            catch (\Exception $ex)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'                        => TraceCode::RECON_FAILURE,
                        'failure_code'                      => 'PAYMENT_TRANSACTION_CREATE_FAIL',
                        'message'                           => 'Payment transaction create failed with -> '. $ex->getMessage(),
                        'is_hdfc_dicl'                      => $isHDFCDICL,
                        'is_not_captured_but_authorized'    => $isNotCapturedButAuthorized,
                        'payment_id'                        => $this->payment->getId(),
                        'gateway'                           => get_called_class()
                    ]);

                $this->app['trace']->traceException($ex);

                return false;
            }
        }

        return false;
    }

    protected function createMissingPaymentTransaction()
    {
        assertTrue($this->payment->transaction === null);

        $this->app['trace']->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'info_code'                         => 'PAYMENT_TRANSACTION_CREATE',
                'message'                           => 'Attempting to create payment transaction in recon',
                'payment_id'                        => $this->payment->getId(),
                'gateway'                           => get_called_class()
            ]);

        list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentAuthorized($this->payment);

        $this->repo->saveOrFail($txn);
        // This is required to save the association of the transaction with the payment.
        $this->repo->saveOrFail($this->payment);

        $this->saveFeeDetails($txn, $feesSplit);
    }

    protected function saveFeeDetails($txn, $feesSplit)
    {
        foreach ($feesSplit as $feeSplit)
        {
            $feeSplit->transaction()->associate($txn);

            $this->repo->saveOrFail($feeSplit);
        }
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
