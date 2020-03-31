<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

use App;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\RequestProcessor;
use RZP\Exception\ReconciliationException;
use RZP\Models\Transaction\ReconciledType;
use RZP\Reconciliator\Base\SubReconciliator\PaymentReconciliate as BasePaymentReconciliate;

class ManualReconciliate extends CombinedReconciliate
{
    const RECON_TYPE    = 'recon_type';
    const RECON_ID      = 'recon_id';
    const AMOUNT        = 'amount';

    const BLACKLISTED_COLUMNS = [];

    // Note : we take GATEWAY_FEE, GATEWAY_SERVICE_TAX
    // from Base/Reconciliate in the recon file, as the
    // same in being used in recordGatewayFeeAndServiceTax()
    // of Base/PaymentReconciliate

    //
    // These fields must be there for each row.
    // ARN and reference_number check will happen
    // later based on payment method
    //
    const REQUIRED_FIELDS = [
        self::RECON_TYPE,
        self::RECON_ID,
        self::AMOUNT,
    ];

    protected $payment;
    protected $paymentTransaction;

    public function startReconciliationV2(array $fileContents, Batch\Processor\Base $batchProcessor)
    {
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        $batch = $batchProcessor->batch;

        try
        {
            foreach ($fileContents as $row)
            {
                $this->trace->info(TraceCode::RECON_MANUAL_FILE_ROW, $row);

                $valid = $this->validateAndSetRow($row);

                if ($valid === false)
                {
                    $this->insertRowInOutputFile($row);

                    $this->setRowReconStatusAndError(
                        Base\InfoCode::RECON_FAILED,
                        Base\InfoCode::RECON_INSUFFICIENT_DATA_FOR_MANUAL_RECON);

                    //
                    // Throw exception, as we dont want to process the file,
                    // even if validation fails for one row
                    //
                    throw new ReconciliationException(Base\InfoCode::RECON_INSUFFICIENT_DATA_FOR_MANUAL_RECON);
                }

                try
                {
                    $reconId = $row[self::RECON_ID];

                    if ($row[self::RECON_TYPE] === Base\Reconciliate::PAYMENT)
                    {
                        $this->insertRowInOutputFile($row, Base\Reconciliate::PAYMENT);

                        $success = $this->processPaymentRecon($reconId, $row);

                        $this->setTransactionDetailsInOutput($this->paymentTransaction);

                        //
                        // Here We just increment failure summary in case recon was unsuccessful.
                        // In case it was success, we have already incremented success summary
                        // before reaching to this point. so not doing again.
                        //
                        if ($success === false)
                        {
                            $this->setSummaryCount(self::FAILURES_SUMMARY, $reconId);
                        }
                    }
                    else
                    {
                        // Unexpected recon type
                        $this->insertRowInOutputFile($row);

                        $this->setRowReconStatusAndError(
                            Base\InfoCode::RECON_UNPROCESSED_SUCCESS,
                            Base\InfoCode::RECON_UNABLE_TO_IDENTIFY_RECON_TYPE);

                        $this->setSummaryCount(self::SUCCESSES_SUMMARY, $reconId);
                    }
                }
                catch (\Exception $e)
                {
                    $this->setSummaryCount(self::FAILURES_SUMMARY, $reconId);

                    throw $e;
                }
                finally
                {
                    $batch->incrementProcessedCount();
                }
            }
        }
        finally
        {
            $this->setReconOutputData($batchProcessor);
            $this->updateBatchWithSummary($batch);
        }
    }

    protected function processPaymentRecon(string $paymentId, array $row)
    {
        try
        {
            $this->payment = $this->repo->payment->findOrFail($paymentId);
            $this->paymentTransaction = $this->payment->transaction;
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::PAYMENT_ABSENT,
                    'payment_id'      => $paymentId,
                    'recon_amount'    => $row[self::AMOUNT],
                    'gateway'         => $this->gateway,
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::PAYMENT_ABSENT);

            throw $ex;
        }

        $this->setMiscEntityDetailsInOutput($this->payment);

        $this->setTerminalDetailsInOutput($this->payment->terminal);

        // check if already reconciled
        if ($this->payment->transaction->isReconciled() === true)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::ALREADY_RECONCILED, null, $this->payment->transaction->getReconciledAt());

            $this->setSummaryCount(self::SUCCESSES_SUMMARY, $paymentId);

            return true;
        }

        $valid = $this->validatePaymentDetailsAndTransaction($row);

        if ($valid === false)
        {
            return false;
        }

        $persist = $this->persistPaymentReferenceNumber($row);

        if ($persist === false)
        {
            return false;
        }

        $success = $this->savePaymentGatewayFeeAndTax($row, $paymentId);

        if ($success === false)
        {
            return false;
        }

        $this->persistReconciledAt($this->payment, ReconciledType::MANUAL);

        $this->repo->saveOrFail($this->payment);

        return true;
    }

    /**
     * Validates payment status, amount and
     * checks if payment transaction exists
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentDetailsAndTransaction(array $row)
    {
        // Check status
        if ($this->payment->isFailed() === true)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::RAZORPAY_FAILED_PAYMENT_RECON,
                    'payment_id'      => $this->payment->getId(),
                    'recon_amount'    => $row[self::AMOUNT],
                    'gateway'         => $this->payment->getGateway()
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RAZORPAY_FAILED_PAYMENT_RECON);

            return false;
        }

        $dbAmount = $this->getPaymentEntityAmount();

        // Check amount mismatch
        if ($dbAmount !== $row[self::AMOUNT])
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $dbAmount,
                    'recon_amount'    => $row[self::AMOUNT],
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->payment->getGateway()
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::AMOUNT_MISMATCH);

            return false;
        }

        // Check if transaction exists
        if ($this->payment->transaction === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::PAYMENT_TRANSACTION_ABSENT,
                    'payment_id'      => $this->payment->getId(),
                    'amount'          => $this->payment->getBaseAmount(),
                    'gateway'         => $this->payment->getGateway()
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::PAYMENT_TRANSACTION_ABSENT);

            return false;
        }

        return true;
    }

    /**
     * Gets amount of payment entity based on transaction currency
     * @return mixed
     */
    protected function getPaymentEntityAmount()
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        return ($convertCurrency === true) ? $this->payment->getBaseAmount() : $this->payment->getGatewayAmount();
    }

    /**
     * Compare the gateway fee and service tax given in recon row
     * and saved in DB if there are no mismatch in the fee/tax amount.
     *
     * @param array $row
     * @param string $paymentId
     * @return bool
     */
    protected function savePaymentGatewayFeeAndTax(array $row, string $paymentId)
    {
        $row[Base\Reconciliate::GATEWAY_FEE] = $this->getGatewayFee($row);

        $row[Base\Reconciliate::GATEWAY_SERVICE_TAX] = $this->getGatewayServiceTax($row);

        $basePaymentRecon = new BasePaymentReconciliate($this->gateway);

        $basePaymentRecon->setPaymentAndTransaction($row, $paymentId);

        $success = $basePaymentRecon->recordGatewayFeeAndServiceTax($row);

        return $success;
    }

    protected function getGatewayFee($row)
    {
        // Convert fee into basic unit of currency. (ex: paise)
        $fee = floatval($row[Base\Reconciliate::GATEWAY_FEE]) * 100;

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return intval(number_format($fee, 2, '.', ''));
    }

    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into basic unit of currency. (ex: paise)
        $serviceTax = floatval($row[Base\Reconciliate::GATEWAY_SERVICE_TAX]) * 100;

        return intval(number_format($serviceTax, 2, '.', ''));
    }

    /**
     * Checks if referenece number/ARN available in the row
     * and save them.
     *
     * @param array $row
     * @return bool
     */
    protected function persistPaymentReferenceNumber(array $row)
    {
        // check for reference number and ARN in the row
        $this->validateReferenceNumbers($row);

        try
        {
            $subReconciliatorObject = $this->getSubReconciliatorObject(Base\Reconciliate::PAYMENT);

            $gatewayPayment = $subReconciliatorObject->getGatewayPayment($this->payment->getId());

            if ($gatewayPayment !== null)
            {
                $subReconciliatorObject->persistReferenceNumber($row, $gatewayPayment);

                $this->repo->saveOrFail($gatewayPayment);
            }

            if (empty($row[Base\Reconciliate::ARN]) === false)
            {
                $this->payment->setReference1($row[Base\Reconciliate::ARN]);
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::RECON_PERSIST_REFERENCE_NUMBER_FAILED,
                    'message'           => $ex->getMessage(),
                    'payment_id'        => $this->payment->getId(),
                    'reference_number'  => $row[Base\Reconciliate::REFERENCE_NUMBER],
                    'arn'               => $row[Base\Reconciliate::ARN],
                    'gateway'           => $this->gateway,
                ]
            );

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_PERSIST_REFERENCE_NUMBER_FAILED);

            return false;
        }

        return true;
    }

    /**
     * We expect ARN (for card payments) and
     * reference number for (non-card payments)
     * to be given in the row
     *
     * @param array $row
     * @throws ReconciliationException
     */
    protected function validateReferenceNumbers(array $row)
    {
        if ($this->payment->getMethod() === Payment\Method::CARD)
        {
            // For card gateways, we need the ARN
            if (empty($row[Base\Reconciliate::ARN]) === true)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'info_code'     => Base\InfoCode::RECON_ARN_ABSENT_FOR_MANUAL_RECON,
                        'payment_id'    => $this->payment->getId(),
                        'amount'        => $this->payment->getBaseAmount(),
                        'method'        => $this->payment->getMethod(),
                        'gateway'       => $this->gateway,
                    ]
                );

                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_ARN_ABSENT_FOR_MANUAL_RECON);

                throw new ReconciliationException(Base\InfoCode::RECON_ARN_ABSENT_FOR_MANUAL_RECON);
            }
        }
        else
        {
            if (empty($row[Base\Reconciliate::REFERENCE_NUMBER]) === true) {
                $this->messenger->raiseReconAlert(
                    [
                        'info_code'     => Base\InfoCode::RECON_REF_NUMBER_ABSENT_FOR_MANUAL_RECON,
                        'payment_id'    => $this->payment->getId(),
                        'amount'        => $this->payment->getBaseAmount(),
                        'method'        => $this->payment->getMethod(),
                        'gateway'       => $this->gateway,
                    ]
                );

                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_REF_NUMBER_ABSENT_FOR_MANUAL_RECON);

                throw new ReconciliationException(Base\InfoCode::RECON_REF_NUMBER_ABSENT_FOR_MANUAL_RECON);
            }
        }
    }

    /**
     * Set the reference 1 if not already saved or
     * @param Payment\Entity $payment
     * @param string $reference1
     */
    protected function setPaymentReference1(Payment\Entity $payment, string $reference1)
    {
        $dbReference1 = $payment->getReference1();

        $isReconciled = $payment->transaction->isReconciled();

        if ((empty($dbReference1) === false) and
            ($dbReference1 !== $reference1) and
            ($this->shouldForceUpdate(RequestProcessor\Base::PAYMENT_ARN) === false))
        {
            $infoCode = ($isReconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'payment_id'                => $payment->getId(),
                    'amount'                    => $payment->getAmount(),
                    'db_reference_number'       => $dbReference1,
                    'recon_reference_number'    => $reference1,
                    'gateway'                   => $payment->getGateway(),
                ]);

            return;
        }

        $payment->setReference1($reference1);
    }

    /**
     * Checks if all required info is present for the row,
     * sets the amount in paisa
     *
     * @param $row
     * @return bool
     */
    protected function validateAndSetRow(& $row)
    {
        foreach (self::REQUIRED_FIELDS as $field)
        {
            if (empty($row[$field]) === true)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'info_alert'        => Base\InfoCode::RECON_INSUFFICIENT_DATA_FOR_MANUAL_RECON,
                        'required_column'   => $field,
                        'gateway'           => $this->gateway,
                    ]
                );

                return false;
            }
        }

        $amount = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::AMOUNT] ?? null);

        $row[self::AMOUNT]  = $amount;

        return true;
    }
}
