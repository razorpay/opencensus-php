<?php

namespace RZP\Models\Payment\Processor;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Transfer\Metric as TransferMetric;
use RZP\Trace\Tracer;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Feature;

trait Transfer
{
    /**
     * Create a transfer payment entity and a corresponding transaction
     *
     * @param  array          $input
     * @param  Payment\Entity $originPayment
     *
     * @return Payment\Entity
     */
    public function processTransfer(array $input, Payment\Entity $originPayment = null, $transfer = null) : Payment\Entity
    {
        try
        {
            $transferPayment = $this->repo->payment->findByTransferIdAndMerchant($transfer->getId(), $transfer->getToId());

            $this->trace->info(
                TraceCode::TRANSFER_PAYMENT_EXISTS,
                [
                    'payment_id'    => $transferPayment->getId(),
                ]
            );
        }
        catch (BadRequestException $e)
        {
            if ($e->getCode() === ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND)
            {
                $transferPayment = null;
            }
            else
            {
                throw $e;
            }
        }

        if ((empty($transferPayment) === false) and ($transferPayment->hasTransaction() === true))
        {
            return $transferPayment;
        }

        if ($transferPayment === null) {
            $paymentData = $this->getTransferPaymentData($input, $originPayment);
            $transferPaymentId = $input['id'];

            try {
                // Attempt to create the payment entity with the initial data
                $transferPayment = $this->processPayment($paymentData, $transferPaymentId, $input, $originPayment);

            } catch (\RZP\Exception\BadRequestException $e) {
                // This catch block manages errors in a sequential manner where contact validation is checked before email validation.
                // If both contact and email are invalid, we first handle the contact validation issue by clearing the contact data.
                // After retrying the payment creation without the contact data, if an email validation error occurs, it is handled in the next catch block.

                // Define error codes related to contact issues
                $contactErrorCodes = [
                    ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_LONG,
                    ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INVALID_COUNTRY_CODE,
                    ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_INCORRECT_FORMAT,
                    ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT
                ];

                // Check if the exception is related to contact validation
                $contactIssue = in_array($e->getCode(), $contactErrorCodes, true);

                if ($contactIssue) {
                    // Record contact validation failure metric
                    (new TransferMetric())->pushContactValidationFailureMetrics();

                    // Clear the contact information and retry
                    $paymentData[Payment\Entity::CONTACT] = null;

                    try {
                        // Retry creating the payment entity with contact data removed
                        $transferPayment = $this->processPayment($paymentData, $transferPaymentId, $input, $originPayment);

                    } catch (\RZP\Exception\BadRequestValidationFailureException $retryException) {
                        // If the retry fails due to email, handle it
                        $transferPayment = $this->handleEmailValidationException($retryException, $paymentData, $transferPaymentId, $input, $originPayment);
                    }

                }
            }
            catch (\RZP\Exception\BadRequestValidationFailureException $e) {
                // If the retry fails due to email, handle it
                $transferPayment = $this->handleEmailValidationException($e, $paymentData, $transferPaymentId, $input, $originPayment);
            }
        }

        $processViaLedgerReverseShadow = false;

        $subMerchant = $this->repo->merchant->findOrFail($transfer->getToId());

        if (($transfer->merchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true) and
            ($subMerchant->isFeatureEnabled(Feature\Constants::PG_LEDGER_REVERSE_SHADOW) === true))
        {
            $processViaLedgerReverseShadow = true;
        }

        $sourceChannel = null;

        if ($originPayment !== null)
        {
            $sourceChannel = $originPayment->getSourceChannel();
        }

        if ($processViaLedgerReverseShadow === true)
        {
            // Skip transaction creation for reverse shadow mode
            if ($sourceChannel !== null)
            {
                $transferPayment[Payment\Entity::SOURCE_CHANNEL] = $sourceChannel;
            }
            return $transferPayment;
        }

        $txnCore = new Transaction\Core;

        list($txn, $feesSplit) = $txnCore->createFromPaymentTransferred($transferPayment);

        $this->repo->saveOrFail($txn);

        $transferPayment->setTax($txn->getTax());

        if ($this->merchant->isFeeBearerCustomer() === false)
        {
            //set and fee values from txn
            $transferPayment->setFee($txn->getFee());
        }

        $txnCore->saveFeeDetails($txn, $feesSplit);

        if ($sourceChannel !== null)
        {
            $transferPayment[Payment\Entity::SOURCE_CHANNEL] = $sourceChannel;
        }

        return $transferPayment;
    }

    /**
     * Handle the email validation issue, clearing the email data and retrying the payment process.
     * @throws BadRequestValidationFailureException
     */
    private function handleEmailValidationException(\RZP\Exception\BadRequestValidationFailureException $e, array &$paymentData, $transferPaymentId, $input, $originPayment): Payment\Entity
    {
        if ($this->isEmailValidationFailureException($e)) {
            // Record email validation failure metric
            (new TransferMetric())->pushEmailValidationFailureMetrics();
            // Only email is invalid, clear email and retry
            $paymentData[Payment\Entity::EMAIL] = null;

            // Retry creating the payment entity with email data removed
            return $this->processPayment($paymentData, $transferPaymentId, $input, $originPayment);

        } else {
            // Rethrow the exception if it's not related to email issues
            throw $e;
        }
    }

    /**
     * Function to check if the exception is related to invalid email address
     */
    private function isEmailValidationFailureException(\RZP\Exception\BadRequestValidationFailureException $e): bool {
        return $e->getCode() === ErrorCode::BAD_REQUEST_VALIDATION_FAILURE &&
            (strcmp($e->getMessage(), 'The email must be a valid email address.') == 0);
    }

    /**
     * Helper func to create, log and process the payment by creating the payment entity and handling currency conversions.
     */
    private function processPayment(array &$paymentData, $transferPaymentId, $input, $originPayment): Payment\Entity
    {
        $transferPayment = $this->createAndLogPayment($paymentData, $transferPaymentId, $input);

        // Set payment attributes and process currency conversions
        $this->setPaymentAttributes($transferPayment);
        $this->processCurrencyConversionsForTransfer($originPayment, $transferPayment);

        return $transferPayment;
    }


    /**
     * Helper function to create a payment entity and log the result.
     *
     * @param array $paymentData The data for creating the payment entity.
     * @param string $transferPaymentId The ID of the transfer payment.
     * @param array $input The original input data.
     * @return mixed The created payment entity.
     */
    private function createAndLogPayment(array $paymentData, $transferPaymentId, array $input)
    {
        // Start a trace span to monitor the payment entity creation process
        $transferPayment = Tracer::inSpan(
            ['name' => 'transfer.process.create_transfer_payment.create_payment'],
            function() use ($paymentData, $transferPaymentId) {
                return $this->createPaymentEntity($paymentData, null, $transferPaymentId);
            }
        );

        // Prepare input trace data for logging, removing sensitive information
        $inputTrace = $input;
        unset($inputTrace['fta_data']['bank_account']['account_number'], $inputTrace['fta_data']['bank_account']['beneficiary_name']);

        // Log the successful creation of the payment entity
        $this->trace->info(
            TraceCode::PAYMENT_CREATED,
            ['payment_id' => $transferPayment->getId(), 'input' => $inputTrace]
        );

        return $transferPayment;
    }

    protected function setPaymentAttributes(Payment\Entity $payment)
    {
        $payment->setStatus(Payment\Status::CAPTURED);

        $payment->setGatewayCaptured(true);

        $payment->setAuthorizeTimestamp();

        $payment->setCaptureTimestamp();

        $payment->setAttribute(Payment\Entity::CREATED_AT, time());
    }

    protected function getTransferPaymentData(array $input, $originPayment)
    {
        $paymentData = [
            Payment\Entity::AMOUNT          => $input['amount'],
            Payment\Entity::CONTACT         => $input['contact'] ?? null,
            Payment\Entity::EMAIL           => $input['email'] ?? null,
            Payment\Entity::CURRENCY        => $input['currency'],
            Payment\Entity::ON_HOLD         => $input['on_hold'] ?? 0,
            Payment\Entity::ON_HOLD_UNTIL   => $input['on_hold_until'] ?? null,
            Payment\Entity::METHOD          => Payment\Method::TRANSFER,
            Payment\Entity::NOTES           => $input['notes'],
        ];

        $now = Carbon::now()->getTimestamp();

        if ((empty($paymentData[Payment\Entity::ON_HOLD_UNTIL]) === false) &&
            ($now >= $paymentData[Payment\Entity::ON_HOLD_UNTIL]))
        {
            // If the current time has exceeded the on_hold until timestamp set in the transfer, then it is
            // no longer necessary to keep the payment entity on hold. So the values are unset here.
            $paymentData[Payment\Entity::ON_HOLD_UNTIL] = null;
            $paymentData[Payment\Entity::ON_HOLD] = 0;
        }

        if ($originPayment !== null)
        {
            $paymentData[Payment\Entity::CONTACT] = $originPayment->getContact();

            $paymentData[Payment\Entity::EMAIL]   = $originPayment->getEmail();
        }

        return $paymentData;
    }

    /**
     * Set the base amount for the transfer payment
     * derived from the conversion rate applied to the
     * parent payment (if defined),
     * else converts for the transfer payment
     *
     * @todo: implementation pending
     *
     * @param  Payment\Entity|null  $originPayment
     * @param  Payment\Entity       $transferPayment
     */
    protected function processCurrencyConversionsForTransfer($originPayment, Payment\Entity $transferPayment)
    {
        if ($originPayment === null)
        {
            $this->processCurrencyConversions($transferPayment);
        }
        else if ($originPayment->getCurrency() === $transferPayment->getCurrency())
        {
            //
            // Note: This will need change for currencies other than INR. For the last
            // possible transfer on a payment, the transferBaseAmount generated may
            // not match the actual amount untransferred, caused by (floor) $amount
            //
            $conversionFactor = $originPayment->getCurrencyConversionRate();

            $transferBaseAmount = $transferPayment->getAmount() * $conversionFactor;

            $transferBaseAmount = (int) floor($transferBaseAmount);

            $transferPayment->setBaseAmount($transferBaseAmount);
        }
        else
        {
            // @todo: Different transfer currency
            //
            // Validate if 1. currency supported and 2. convert allowed for marketplace.
            //
            // If original payment date = today:
            // call processCurrencyConversions()
            //
            // else:
            // get historical rate on payment date, for transfer currency
            // calc and set baseAmount
        }
    }

}
