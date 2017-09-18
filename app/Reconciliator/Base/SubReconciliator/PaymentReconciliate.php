<?php

namespace RZP\Reconciliator\Base;

use App;
use RZP\Models\Card;
use RZP\Models\Payment;
use Rzp\Trace\TraceCode;
use RZP\Models\Card\IIN;
use RZP\Gateway\AxisMigs;
use RZP\Models\Transaction;
use RZP\Reconciliator\Messenger;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Orchestrator;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\ReconciliationException;
use RZP\Models\Payment\Verify\Result as VerifyResult;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class PaymentReconciliate extends Foundation\SubReconciliate
{
    const GATEWAY_FEES_ABSENT_GATEWAYS = [
        Orchestrator::KOTAK,
        Orchestrator::NETBANKING_AXIS,
        Orchestrator::NETBANKING_ICICI,
        Orchestrator::NETBANKING_FEDERAL,
        Orchestrator::NETBANKING_RBL,
        Orchestrator::NETBANKING_INDUSIND,
        Orchestrator::NETBANKING_BOB,
        Orchestrator::JIOMONEY,
        Orchestrator::VIRTUAL_ACC_KOTAK,
        Orchestrator::NETBANKING_PNB,
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
    protected $trace;
    protected $messenger;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->trace = $this->app['trace'];
        $this->messenger = new Messenger;

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

            $this->trace->traceException($ex);

            throw $ex;
        }
    }

    protected function runPreReconciledAtCheckRecon($rowDetails)
    {
        $this->persistGatewaySettledAt($this->payment, $rowDetails);
    }

    protected function validatePaymentDetails(array $row)
    {
        $validPaymentStatus = $this->validatePaymentStatus($row);

        $validPaymentAmount = $this->validatePaymentAmountEqualsReconAmount($row);

        $validPaymentDetails = ($validPaymentStatus and $validPaymentAmount);

        return $validPaymentDetails;
    }

    /**
     * Validates that the payment status is not failed.
     *
     * @param $row
     *
     * @return bool
     */
    protected function validatePaymentStatus($row)
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus !== Payment\Status::FAILED)
        {
            return true;
        }

        $this->trace->info(
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
        //
        // If a gateway has implemented force authorization,
        // always use that, instead of verify. There's no
        // need for running verify if force authorization is present.
        //
        if ($this->shouldAttemptForceAuthorizeFailed() === true)
        {
            return $this->handleForceAuthorization($row);
        }
        else
        {
            return $this->handleVerifyPayment();
        }
    }

    protected function handleVerifyPayment()
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

            $this->trace->traceException($ex);

            return false;
        }

        switch($verifyResponse)
        {
            case VerifyResult::AUTHORIZED:

                $this->trace->info(
                    TraceCode::RECON_INFO,
                    [
                        'message'    => 'Verify returned authorized.',
                        'payment_id' => $this->payment->getId(),
                        'gateway'    => get_called_class()
                    ]
                );

                $authorizeSuccess = $this->handleVerifyAuthorized();

                break;

            case VerifyResult::SUCCESS:

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                        'message'    => 'Verify returned failed. Payment is still in failed state.',
                        'payment_id' => $this->payment->getId(),
                        'gateway'    => get_called_class()
                    ]);

                $authorizeSuccess = false;

                break;

            default:

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_FAILED_VERIFY,
                        'message'       => 'Verify command failed or unable to recognize the response.',
                        'payment_id'    => $this->payment->getId(),
                        'verify_status' => $verifyResponse,
                        'gateway'       => get_called_class()
                    ]);

                $authorizeSuccess = false;
        }

        return $authorizeSuccess;
    }

    protected function handleForceAuthorization(array $row)
    {
        $authorizeSuccess = $this->forceAuthorizeFailed($row);

        if ($authorizeSuccess === true)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'    => 'Force authorized the failed payment.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class(),
                ]);

            $authResponse = $this->handleVerifyAuthorized();
        }
        else
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_FAILED_VERIFY,
                    'message'    => 'Unable to force authorize the payment. Payment is still in failed state.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => get_called_class()
                ]);

            $authResponse = false;
        }

        return $authResponse;
    }

    /**
     * This function will be called if the gateway requires a force
     * authorization from failed state. If no force authorization,
     * it means that the payment is still in failed state and hence
     * should return back false.
     *
     * @param array $row
     *
     * @return bool
     */
    protected function forceAuthorizeFailed(array $row)
    {
        $paymentService = new Payment\Service;

        $paymentId = $this->payment->getPublicId();

        $this->messenger->raiseReconAlert(
            [
                'trace_code'      => TraceCode::RECON_INFO_ALERT,
                'message'         => 'Payment status is failed. Doing force authorize',
                'payment_id'      => $this->payment->getId(),
                'gateway'         => get_called_class()
            ]);

        $input = $this->getInputForForceAuthorize($row);

        // If there's any issue during authorize, the function throws an exception.
        $response = $paymentService->forceAuthorizeFailed($paymentId, $input);

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'info_code' => 'FORCE_AUTHORIZATION_RESPONSE',
                'message'   => 'Response received from force authorization',
                'response'  => $response
            ]
        );

        if ((empty($response['status']) === false) and
            ($response['status'] === Payment\Status::AUTHORIZED))
        {
            return true;
        }

        return false;
    }

    protected function handleVerifyAuthorized()
    {
        $this->payment = $this->paymentRepo->findOrFail($this->payment->getId());

        // Set the payment transaction for the row.
        $this->paymentTransaction = $this->payment->transaction;

        if ($this->paymentTransaction !== null)
        {
            $success = true;
        }
        else
        {
            $createTransactionSuccess = $this->attemptToCreateMissingPaymentTransaction();

            if ($createTransactionSuccess === true)
            {
                $this->paymentTransaction = $this->payment->reload()->transaction;

                $success = true;
            }
            else
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_FAILURE,
                        'failure_code'  => 'PAYMENT_TRANSACTION_ABSENT',
                        'message'       => 'Unable to create payment transaction after verifying',
                        'payment_id'    => $this->payment->getId(),
                        'gateway'       => get_called_class()
                    ]);

                $success = false;
            }
        }

        return $success;
    }

    protected function persistReconciliationData($rowDetails)
    {
        // If the row is present in MIS file, it means it's captured on the gateway end.
        $this->markGatewayCapturedAsTrue();

        $recordSuccess = $this->recordGatewayFeeAndServiceTax($rowDetails);

        if ($recordSuccess === true)
        {
            $this->persistReconciledAt($this->payment);
        }

        $this->persistCardDetailsIfAbsent($rowDetails);

        $this->persistGatewayData($rowDetails);

        $this->persistGatewaySettledAt($this->payment, $rowDetails);

        return $recordSuccess;
    }

    protected function getRowDetailsStructured($row)
    {
        $this->trace->info(
            TraceCode::RECON_FILE_ROW,
            $row
        );

        $paymentId = $this->getPaymentId($row);

        // If payment id is not present, return. No point of evaluating the row.
        if (empty($paymentId) === true)
        {
            return null;
        }

        $referenceNumber = $this->getReferenceNumber($row);

        $gatewayPaymentDate = $this->getGatewayPaymentDate($row);

        $this->setPaymentAndTransaction($row, $paymentId);

        $cardDetails = $this->getCardDetails($row);

        $serviceTax = $this->getGatewayServiceTax($row);

        $fee = $this->getGatewayFee($row);

        $gatewaySettledAt = $this->getGatewaySettledAt($row);

        $customerDetails = $this->getCustomerDetails($row);

        $accountDetails = $this->getNbAccountDetails($row);

        $rowDetails = [
            BaseReconciliate::PAYMENT_ID           => $paymentId,
            BaseReconciliate::GATEWAY_SERVICE_TAX  => $serviceTax,
            BaseReconciliate::GATEWAY_FEE          => $fee,
            BaseReconciliate::GATEWAY_SETTLED_AT   => $gatewaySettledAt,
            BaseReconciliate::REFERENCE_NUMBER     => $referenceNumber,
            BaseReconciliate::GATEWAY_PAYMENT_DATE => $gatewayPaymentDate
        ];

        // For wallets and netbanking, $cardDetails would be empty.
        // We do an array_filter because sometimes, due to parsing errors,
        // we may not be able to get some card details which we would have
        // expected to get, due to which their corresponding values would be null.
        if (empty(array_filter($cardDetails)) === false)
        {
            $rowDetails[BaseReconciliate::CARD_DETAILS] = array_filter($cardDetails);
        }

        // We set customer details only for netbanking
        if (empty(array_filter($customerDetails)) === false)
        {
            $rowDetails[BaseReconciliate::CUSTOMER_DETAILS] = array_filter($customerDetails);
        }

        // We set account details only for netbanking
        if (empty(array_filter($accountDetails)) === false)
        {
            $rowDetails[BaseReconciliate::ACCOUNT_DETAILS] = array_filter($accountDetails);
        }

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
                $this->trace->info(
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

        // if iin is locked for editing, skip persisting recon data for iin
        if ($this->paymentIin->isLocked() === true)
        {
            // add trace ?
            return;
        }

        if (empty($cardDetails[BaseReconciliate::CARD_TYPE]) === false)
        {
            $this->persistCardType($cardDetails[BaseReconciliate::CARD_TYPE]);
        }

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

    protected function persistAccountDetails(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::ACCOUNT_DETAILS]) === true)
        {
            return;
        }

        $accountDetails = $rowDetails[BaseReconciliate::ACCOUNT_DETAILS];

        $this->persistDebitAccount($accountDetails, $gatewayPayment);

        $this->persistCreditAccount($accountDetails, $gatewayPayment);
    }

    /**
     * Saving Gateway Data into DB
     *
     * @param array $rowDetails
     */
    protected function persistGatewayData(array $rowDetails)
    {
        $gatewayPayment = $this->getGatewayPayment($this->payment->getId());

        if ($gatewayPayment === null)
        {
            return;
        }

        $this->persistAccountDetails($rowDetails, $gatewayPayment);

        $this->persistReferenceNumber($rowDetails, $gatewayPayment);

        $this->persistGatewayPaymentDate($rowDetails, $gatewayPayment);

        $this->persistCustomerDetails($rowDetails, $gatewayPayment);

        $gatewayPayment->saveOrFail();
    }

    /**
     * Saving the Bank Payment Id from reconciliator file
     * Replacing existing value or adding it to the DB
     *
     * @param array        $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistReferenceNumber(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::REFERENCE_NUMBER]) === true)
        {
            return;
        }

        $referenceNumber = $rowDetails[BaseReconciliate::REFERENCE_NUMBER];

        $this->setReferenceNumberInGateway($referenceNumber, $gatewayPayment);
    }

    /**
     * Updates the gateway payment entity with payment date from recon file
     *
     * @param array        $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistGatewayPaymentDate(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::GATEWAY_PAYMENT_DATE]) === true)
        {
            return;
        }

        $gatewayPaymentDate = $rowDetails[BaseReconciliate::GATEWAY_PAYMENT_DATE];

        $this->setGatewayPaymentDateInGateway($gatewayPaymentDate, $gatewayPayment);
    }

    /**
     * Saving customer information into the DB
     *
     * @param array        $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistCustomerDetails(array $rowDetails, PublicEntity $gatewayPayment)
    {
        if (empty($rowDetails[BaseReconciliate::CUSTOMER_DETAILS]) === true)
        {
            return;
        }

        $customerDetails = $rowDetails[BaseReconciliate::CUSTOMER_DETAILS];

        if (empty($customerDetails[BaseReconciliate::CUSTOMER_ID]) === false)
        {
            $this->persistNbCustomerId($customerDetails, $gatewayPayment);
        }

        if (empty($customerDetails[BaseReconciliate::CUSTOMER_NAME]) === false)
        {
            $this->persistCustomerName($customerDetails, $gatewayPayment);
        }
    }

    /**
     * Saving customer Id into the DB
     *
     * @param array        $customerDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistNbCustomerId(array $customerDetails, PublicEntity $gatewayPayment)
    {
        $customerId = $customerDetails[BaseReconciliate::CUSTOMER_ID];

        $gatewayPayment->setCustomerId($customerId);
    }

    /**
     * Saving customer Name into the DB
     *
     * @param array        $customerDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistCustomerName(array $customerDetails, PublicEntity $gatewayPayment)
    {
        $customerName = $customerDetails[BaseReconciliate::CUSTOMER_NAME];

        $gatewayPayment->setCustomerName($customerName);
    }

    /**
     * Saving debit account into the DB
     *
     * @param array        $accountDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistDebitAccount(array $accountDetails, PublicEntity $gatewayPayment)
    {
        if (empty($accountDetails[BaseReconciliate::ACCOUNT_NUMBER]) === true)
        {
            return;
        }

        $accountNumber = $accountDetails[BaseReconciliate::ACCOUNT_NUMBER];

        $gatewayPayment->setAccountNumber($accountNumber);
    }

    /**
     * Saving credit account into the DB
     *
     * @param array        $accountDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistCreditAccount(array $accountDetails, PublicEntity $gatewayPayment)
    {
        if (empty($accountDetails[BaseReconciliate::CREDIT_ACCOUNT_NUMBER]) === true)
        {
            return;
        }

        $accountNumber = $accountDetails[BaseReconciliate::CREDIT_ACCOUNT_NUMBER];

        $gatewayPayment->setCreditAccountNumber($accountNumber);
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
            $this->trace->info(
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
            $this->trace->info(
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
            $this->trace->info(
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
        $this->trace->info(
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

            $this->trace->info(
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

    protected function markGatewayCapturedAsTrue()
    {
        $currentGatewayCaptured = $this->payment->getGatewayCaptured();

        if ($currentGatewayCaptured === true)
        {
            return;
        }

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'         => 'Gateway Captured not set for the payment',
                'info_code'       => 'GATEWAY_CAPTURED_NOT_SET',
                'payment_id'      => $this->payment->getId(),
                'gateway'         => get_called_class()
            ]);

        $this->payment->setGatewayCaptured(true);

        $this->repo->saveOrFail($this->payment);
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
                $message = 'Payment transaction create failed with -> '. $ex->getMessage();

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'                        => TraceCode::RECON_FAILURE,
                        'failure_code'                      => 'PAYMENT_TRANSACTION_CREATE_FAIL',
                        'message'                           => $message,
                        'is_hdfc_dicl'                      => $isHDFCDICL,
                        'is_not_captured_but_authorized'    => $isNotCapturedButAuthorized,
                        'payment_id'                        => $this->payment->getId(),
                        'gateway'                           => get_called_class()
                    ]);

                $this->trace->traceException($ex);

                return false;
            }
        }

        return false;
    }

    protected function createMissingPaymentTransaction()
    {
        assertTrue($this->payment->transaction === null);

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'info_code'     => 'PAYMENT_TRANSACTION_CREATE',
                'message'       => 'Attempting to create payment transaction in recon',
                'payment_id'    => $this->payment->getId(),
                'gateway'       => get_called_class()
            ]);

        //
        // We should always create a transaction if the payment comes in the recon file.
        // This is needed because currently nodal and merchant transactions are tracked via
        // a single transaction entity.
        // If the merchant has not captured the payment, we should create the transaction WITHOUT
        // the fees/service_tax.
        // If the merchant has captured the payment, we should create the transaction WITH fee/service_tax.
        //
        if ($this->payment->hasBeenCaptured() === true)
        {
            list($txn, $feesSplit) = (new Transaction\Core)->createOrUpdateFromPaymentCaptured($this->payment);
        }
        else
        {
            list($txn, $feesSplit) = (new Transaction\Core)->createFromPaymentAuthorized($this->payment);
        }

        $this->repo->saveOrFail($txn);
        // This is required to save the association of the transaction with the payment.
        $this->repo->saveOrFail($this->payment);

        $this->saveFeeDetails($txn, $feesSplit);
    }

    protected function saveFeeDetails(Transaction\Entity $txn, PublicCollection $feesSplit)
    {
        foreach ($feesSplit as $feeSplit)
        {
            $feeSplit->transaction()->associate($txn);

            $this->repo->saveOrFail($feeSplit);
        }

        $this->trace->info(
            TraceCode::FEES_BREAKUP_CREATED,
            [
                'transaction_id'    => $txn->getId(),
                'payment_id'        => $txn->getEntityId(),
                'fee_split'         => $feesSplit->toArrayPublic(),
            ]);
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
                $message = 'Gateway fee in the recon file does not match with the one stored in API.';

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'        => TraceCode::RECON_FAILURE,
                        'message'           => $message,
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
                $message = 'Gateway service tax in the recon file does not match with the one stored in API.';

                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'                 => TraceCode::RECON_FAILURE,
                        'message'                    => $message,
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
     * If this is being implemented in the child class, ensure that
     * the setter for storing the reference number is present
     * in the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getReferenceNumber($row)
    {
        return null;
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setter for storing the gateway payment date is present
     * in the gateway entity.
     * @param $row
     * @return null
     */
    protected function getGatewayPaymentDate($row)
    {
        return null;
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setters for customerId and customerName are present
     * for the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getCustomerDetails($row)
    {
        return [];
    }

    /**
     * If this is being implemented in the child class, ensure that
     * the setters for accountNumber and creditAccountNumber are present
     * for the gateway entity.
     *
     * @param $row
     * @return null
     */
    protected function getNbAccountDetails($row)
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

    /**
     * This function should be implemented in the child class
     * It will fetch the input required to do force authorize
     * on the gateway.
     *
     * @return array
     */
    protected function getInputForForceAuthorize($row)
    {
        return [];
    }

    /**
     * This function should be implemented in the child class
     * It tells whether we should attempt force authorize on
     * the gateway.
     *
     * @return bool
     */
    protected function shouldAttemptForceAuthorizeFailed()
    {
        return false;
    }

    /**
     * Getting the gatewayPayment associated with payment entity.
     * It is implemented in the child class.
     *
     * NOTE: If this is being implemented in the child class,
     * ensure that the relevant setters are implemented in the entity.
     */
    protected function getGatewayPayment($paymentId)
    {
        return null;
    }

    /**
     * Checks if amount in recon file matches the actual amount in payment entity
     * Implementation to be provided by child classes
     *
     * @param  array $row Row data
     *
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        return true;
    }

    /**
     * The reason that it is implemented this way is because different
     * gateway entities may have different attribute names to store the
     * reference number.
     * So, other gateways can implement this function with the
     * appropriate setter.
     *
     * @param string       $referenceNumber
     * @param PublicEntity $gatewayPayment
     */
    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setBankPaymentId($referenceNumber);
    }

    /**
     * Sets the given gateway payment date in gateway entity. Gateways storing this value
     * as a different attribute can override this function accordingly.
     *
     * @param string       $gatewayPaymentDate
     * @param PublicEntity $gatewayPayment
     */
    protected function setGatewayPaymentDateInGateway(string $gatewayPaymentDate, PublicEntity $gatewayPayment)
    {
        $gatewayPayment->setDate($gatewayPaymentDate);
    }
}
