<?php

namespace RZP\Reconciliator\Base\SubReconciliator;

use App;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Batch\Entity;
use RZP\Models\Payment\Refund;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\RequestProcessor;
use RZP\Exception\ReconciliationException;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class RefundReconciliate extends Base\Foundation\SubReconciliate
{
    /*******************
     * Instance objects
     *******************/

    // This will need to be overridden in each gateway's refund recon.
    // This will contain column having amount in case of domestic transaction
    const COLUMN_REFUND_AMOUNT = '';

    // This will need to be overridden in each gateway's refund recon.
    // This will contain column having amount in case of international transaction
    const COLUMN_INTERNATIONAL_REFUND_AMOUNT = '';

    // List of gateways whose refund status must be set to processed without ARN
    const GATEWAYS_PROCESSED_WO_ARN = [
        RequestProcessor\Base::UPI_ICICI,
        RequestProcessor\Base::UPI_AXIS
    ];

    // List of gateways for which despite MIS status
    // contains failure, should be sent to scrooge.
    const RECON_STATUS_FAILURE_GATEWAYS = [
        RequestProcessor\Base::UPI_SBI,
        RequestProcessor\Base::NETBANKING_SBI,
    ];

    // Keys used to send recon and gateway statuses to scrooge
    const RECON_STATUS   = 'recon_status';
    const GATEWAY_STATUS = 'gateway_status';

    /**
     * @var Payment\Entity
     */
    protected $payment;

    protected $reconciled;

    /**
     * @var Gateway Refund Entity
     */
    protected $gatewayRefund;

    /**
     * @var Refund\Entity
     */
    protected $refund;

    public function __construct(string $gateway = null, Entity $batch = null)
    {
        parent::__construct($gateway, $batch);

        $this->messenger->batch = $batch;

        $this->batch = $batch;
    }

    /**
     * @param $row
     * @return void|null
     * @throws ReconciliationException
     * @throws \RZP\Exception\LogicException
     */
    public function runReconciliate($row)
    {
        //
        // Resetting row attributes here which could have been set during
        // reconciliation of a particular row. This is mainly done for
        // resetting failUnprocessedRow attribute which should be reset
        // for each row.
        //
        $this->resetRowProcessingAttributes();

        $this->insertRowInOutputFile($row, Base\Reconciliate::REFUND);

        $rowDetails = $this->getRowDetailsStructured($row);

        if (empty($rowDetails) === true)
        {
            return $this->handleUnprocessedRow($row);
        }

        $refundId = $rowDetails[BaseReconciliate::REFUND_ID];

        $this->setMiscEntityDetailsInOutput($this->refund);

        $this->setTerminalDetailsInOutput($this->payment->terminal);

        $this->calculateAndSetNetAmountInOutputFile($row);

        try
        {
            $this->reconciled = $this->checkIfAlreadyReconciled($this->refund);

            $this->runPreReconciledAtCheckRecon($rowDetails);

            // Increment the total count for the summary
            $this->setSummaryCount(self::TOTAL_SUMMARY, $refundId);

            if ($this->reconciled === true)
            {
                $this->handleAlreadyReconciled($refundId, $this->refund->transaction->getReconciledAt());
            }
            else
            {
                $validate = $this->validateRefundDetails($row);

                if ($validate === true)
                {
                    $persistSuccess = $this->persistReconciliationData($rowDetails);

                    if ($persistSuccess === false)
                    {
                        $this->handlePersistReconciliationDataFailure($refundId);
                    }
                }
                else
                {
                    if ($this->shouldSendRefundToScroogeDespiteReconFailure($row) === false)
                    {
                        $this->removeFromScroogeRequest();
                    }

                    $this->handleFailedValidation($refundId);
                }
            }

            $this->setTransactionDetailsInOutput($this->refund->transaction);

            $this->releaseResourceForRecon($refundId);
        }
        catch (\Exception $ex)
        {
            // Ideally, there shouldn't be any exceptions thrown. They should be handled
            // in the respective reconciliation steps.

            // Remove from scrooge request when exception is thrown
            $this->removeFromScroogeRequest();

            // Increment the failure count for the summary.
            $this->setSummaryCount(self::FAILURES_SUMMARY, $refundId);

            $this->trace->info(
                TraceCode::RECON_FAILURE,
                [
                    'message'       => 'Unable to perform one of the reconciliation actions -> ' . $ex->getMessage(),
                    'refund_id'     => $refundId,
                    'extra_details' => $this->extraDetails,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);

            $this->trace->traceException($ex);

            if (empty(static::$reconOutputData[static::$currentRowNumber][self::RECON_STATUS]) === true)
            {
                // if the status is not set, then set it to failure
                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, 'Unable to perform one of the reconciliation actions -> ' . $ex->getMessage());
            }

            throw $ex;

            //return;
        }

        return null;
    }

    /**
     * In some cases, even when recon row's
     * validation fails, we still want to
     * send it's data to scrooge.
     *
     * @param $row
     * @return bool
     */
    protected function shouldSendRefundToScroogeDespiteReconFailure($row)
    {
        return ((in_array($this->gateway, self::RECON_STATUS_FAILURE_GATEWAYS)) and
                ($this->validateRefundReconStatus($row) === false));
    }

    /**
     * Fetches refund amount and sets in the
     * output file as negative as it is debit.
     *
     * @param array $row
     */
    protected function calculateAndSetNetAmountInOutputFile(array $row)
    {
        $grossAmt = intval($this->getReconRefundAmount($row));

        $netAmount = (-1) * $grossAmt / 100;

        $this->setReconNetAmountInOutput($netAmount);
    }

    public function resetRowProcessingAttributes()
    {
        $this->payment = null;
        $this->refund  = null;

        parent::resetRowProcessingAttributes();
    }

    protected function isScroogeRefund()
    {
        return empty(static::$scroogeReconciliate[$this->refund->getId()]) === false;
    }

    protected function handleAlreadyReconciled(string $entityId, int $reconciledAt = null)
    {
        // If this is a scrooge refund, do not count it for success or failure counts,
        // as this will be done during dispatch processing
        if ($this->isScroogeRefund() === true)
        {
            //
            // Send the API reconciledAt timestamp to scrooge, else if this txn is not
            // marked as reconciled in scrooge then scrooge will use its own current
            // timestamp to set reconciledAt and send back that new timestamp which will
            // further overwrite the API reconciledAt, which would be wrong.
            //
            static::$scroogeReconciliate[$entityId]->setReconciledAt($this->refund->transaction->getReconciledAt());

            $this->setRowReconStatusAndError(Base\InfoCode::ALREADY_RECONCILED, null, $reconciledAt);

            return;
        }

        parent::handleAlreadyReconciled($entityId, $reconciledAt);
    }

    protected function handleUnprocessedRow(array $row)
    {
        $this->removeFromScroogeRequest();

        parent::handleUnprocessedRow($row);
    }

    protected function handlePersistReconciliationDataFailure(string $refundId)
    {
        $this->removeFromScroogeRequest();

        parent::handlePersistReconciliationDataFailure($refundId);
    }

    protected function removeFromScroogeRequest()
    {
        // As validation failed, removing this refund from the array
        // so as not to send it to scrooge for processing
        if (empty($this->refund) === false)
        {
            $refundId = $this->refund->getId();

            //
            // Here we need not check if the gateway falls under scroogeGateway list,
            // if $scroogeReconciliate[$refundId] is set, that is sufficient condition.
            //
            if ($this->isScroogeRefund() === true)
            {
                unset(static::$scroogeReconciliate[$refundId]);
            }
        }
    }

    protected function getReconRefundAmount(array $row)
    {
        if ((static::COLUMN_REFUND_AMOUNT === '') and
            (static::COLUMN_INTERNATIONAL_REFUND_AMOUNT === ''))
        {
            return null;
        }

        $amountColumn = ($this->isInternationalRefund($row) === true) ?
                        static::COLUMN_INTERNATIONAL_REFUND_AMOUNT :
                        static::COLUMN_REFUND_AMOUNT;

        $refundAmountColumns = (is_array($amountColumn) === false) ?
                                [$amountColumn] :
                                 $amountColumn;

        $refundAmountColumn = array_first(
            $refundAmountColumns,
            function ($amount) use ($row)
            {
                return (array_key_exists($amount, $row) === true);
            });

        if ($refundAmountColumn === null)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::AMOUNT_ABSENT,
                    'refund_id'         => $this->refund->getId(),
                    'expected_column'   => $amountColumn,
                    'currency'          => $this->payment->getCurrency(),
                    'payment_id'        => $this->payment->getId(),
                    'gateway'           => $this->gateway,
                    'batch_id'          => $this->batchId,
                ]);

            return false;
        }

        return Helper::getIntegerFormattedAmount($row[$refundAmountColumn]);
    }

    /**
     * Gets amount of refund entity based on transaction currency
     */
    protected function getRefundEntityAmount()
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        if ($convertCurrency === true)
        {
            return $this->refund->getBaseAmount();
        }

        return $this->refund->getGatewayAmount();
    }

    /**
     * This function has to be overriden in child classes.
     * This will return true of current transaction is domestic or international
     * @param array $row
     * @return bool
     */
    protected function isInternationalRefund(array $row)
    {
        return false;
    }

    protected function runPreReconciledAtCheckRecon(array $rowDetails)
    {
        $this->persistGatewaySettledAt($this->refund, $rowDetails);

        $this->persistGatewayAmount($this->refund, $rowDetails);

        $this->persistRefundArn($rowDetails);

        $this->persistGatewayData($rowDetails);
    }

    protected function validateRefundDetails(array $row)
    {
        $validPaymentStatus = $this->validatePaymentStatus();

        $validRefundReconStatus = $this->validateRefundReconStatus($row);

        $validRefundAmount = $this->validateRefundAmountEqualsReconAmount($row);

        $validCurrencyCode = $this->validateRefundCurrencyEqualsReconCurrency($row);

        $validRefundDetails = (($validPaymentStatus === true) and
                               ($validRefundReconStatus === true) and
                               ($validRefundAmount === true) and
                               ($validCurrencyCode === true));

        if ($validRefundDetails === false)
        {
            $this->setFailureReason($validPaymentStatus, $validRefundReconStatus, $validRefundAmount, $validCurrencyCode);
        }

        return $validRefundDetails;
    }

    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus === Payment\Status::FAILED)
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'  => Base\InfoCode::REFUND_PAYMENT_FAILED,
                    'payment_id' => $this->payment->getId(),
                    'amount'     => $this->payment->getAmount(),
                    'gateway'    => $this->gateway,
                    'batch_id'   => $this->batchId,
                ]);

            return false;
        }

        return true;
    }

    protected function validateRefundReconStatus(array $row)
    {
        $refundReconStatus = $this->getReconRefundStatus($row);

        if ($refundReconStatus === Payment\Refund\Status::FAILED)
        {
            $this->trace->info(TraceCode::RECON_INFO, [
                'info_code'         => Base\InfoCode::MIS_FILE_REFUND_FAILED,
                'refund_id'         => $this->refund->getId(),
                'refund_status'     => $this->refund->getStatus(),
                'gateway'           => $this->gateway,
                'batch_id'          => $this->batchId,
            ]);

            return false;
        }

        return true;
    }

    // Sets appropriate error message
    protected function setFailureReason($validPaymentStatus, $validRefundReconStatus, $validRefundAmount, $validCurrencyCode)
    {
        switch (true)
        {
            case ($validPaymentStatus === false):
                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::REFUND_PAYMENT_FAILED);
                break;

            case ($validRefundReconStatus === false):
                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::MIS_FILE_REFUND_FAILED);
                break;

            case ($validRefundAmount ===  false):
                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::AMOUNT_MISMATCH);
                break;

            case ($validCurrencyCode ===  false):
                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::CURRENCY_MISMATCH);
                break;
        }
    }

    protected function persistReconciliationData(array $rowDetails)
    {
        $refundTransaction = $this->refund->transaction;

        if ($refundTransaction === null)
        {
            $createTransactionSuccess = $this->core->attemptToCreateMissingRefundTransaction($this->refund);

            if ($createTransactionSuccess === false)
            {
                $this->trace->info(
                    TraceCode::RECON_MISMATCH,
                    [
                        'info_code'     => Base\InfoCode::REFUND_TRANSACTION_ABSENT,
                        'refund_id'     => $this->refund->getId(),
                        'payment_id'    => $this->refund->payment->getId(),
                        'amount'        => $this->refund->getAmount(),
                        'gateway'       => $this->gateway,
                        'batch_id'      => $this->batchId,
                    ]);

                $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::REFUND_TRANSACTION_ABSENT);

                return false;
            }

            // Refresh both refund and transaction to get latest changes.
            // Reload txn because relation are cached.
            $this->refund->reload()->transaction->reload();
        }

        $this->persistReconciledAt($this->refund);

        $this->persistGatewaySettledAt($this->refund, $rowDetails);

        $this->persistGatewayAmount($this->refund, $rowDetails);

        $this->setRefundProcessedWithoutArn($this->refund);

        return true;
    }

    protected function setRefundProcessedWithoutArn(RefundEntity $refund)
    {
        //
        // We check if refund is marked as processed already.
        // If it's not, only then we check whether we allow
        // it to be marked as processed without the ARN. If ARN
        // was present, we would have already marked it as processed.
        //
        if (($refund->isProcessed() === false) and
            (in_array($this->gateway, self::GATEWAYS_PROCESSED_WO_ARN, true) === true))
        {
            if ($this->refund->isScrooge() === false)
            {
                $this->refund->setStatusProcessed();

                $this->repo->saveOrFail($refund);

                $this->core->pushRefundProcessedMetric($refund, $this->source);
            }
            else
            {
                static::$scroogeReconciliate[$this->refund->getId()]->setStatus(Refund\Status::PROCESSED);
            }
        }
    }

    /**
     * @param $row
     * @return array|null
     * @throws ReconciliationException
     */
    protected function getRowDetailsStructured($row)
    {
        $this->traceReconRow($row);

        $refund = $this->getApiRefundEntityFromRow($row);

        // If we cannot get the refund, return. No point of evaluating the row.
        if ($refund === null)
        {
            return null;
        }

        $refundId = $refund->getId();

        // Sets the corresponding payment for the refund.
        $this->payment = $this->refund->payment;

        // If payment is not present, return. There's something wrong with this transaction.
        if (empty($this->payment) === true)
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'  => Base\InfoCode::REFUND_PAYMENT_ABSENT,
                    'refund_id'  => $refundId,
                    'amount'     => $refund->getAmount(),
                    'gateway'    => $this->gateway,
                    'batch_id'   => $this->batchId,
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::REFUND_PAYMENT_ABSENT);

            throw new ReconciliationException(
                'Corresponding payment for the refund not found in the DB.',
                [
                    'refund_id' => $refundId,
                ]);
        }

        $gatewaySettledAt = $this->getGatewaySettledAt($row);

        $gatewayAmount = $this->getGatewayAmount($row);

        $arn = $this->getArn($row);

        $gatewayUtr = $this->getGatewayUtr($row);

        $gatewayTransactionId = $this->getGatewayTransactionId($row);

        $referenceNumber = $this->getReferenceNumber($row);

        $rowDetails = [
            BaseReconciliate::REFUND_ID              => $refundId,
            BaseReconciliate::GATEWAY_SETTLED_AT     => $gatewaySettledAt,
            BaseReconciliate::GATEWAY_AMOUNT         => $gatewayAmount,
            BaseReconciliate::ARN                    => trim($arn),
            BaseReconciliate::GATEWAY_UTR            => trim($gatewayUtr),
            BaseReconciliate::REFERENCE_NUMBER       => trim($referenceNumber),
            BaseReconciliate::GATEWAY_TRANSACTION_ID => trim($gatewayTransactionId),
        ];

        return $rowDetails;
    }

    protected function getApiRefundEntityFromRow(array $row)
    {
        $refundId = $this->getRefundId($row);

        // If refund id is not present, return. No point of evaluating the row.
        if (empty($refundId) === true)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::REFUND_ID_NOT_FOUND);

            return null;
        }

        if (UniqueIdEntity::verifyUniqueId($refundId, false) === false)
        {
            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::REFUND_ID_NOT_AS_EXPECTED);

            return null;
        }

        $acquire = $this->lockResourceForRecon($refundId);

        if($acquire === false)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => Base\InfoCode::UNABLE_TO_ACQUIRE_LOCK,
                    'refund_id'     => $refundId,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);
        }

        try
        {
            $this->refund = $this->repo->refund->findOrFail($refundId);
        }
        catch (\Exception $ex)
        {
            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'     => Base\InfoCode::REFUND_ABSENT,
                    'refund_id'     => $refundId,
                    'gateway'       => $this->gateway,
                    'batch_id'      => $this->batchId,
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED,  Base\InfoCode::REFUND_ABSENT);

            return null;
        }

        if ($this->refund->isScrooge() === true)
        {
            //
            // Check if this refund ID is already present in
            // $scroogeReconciliate and avoid replacing it.
            //
            if (isset(static::$scroogeReconciliate[$this->refund->getId()]) === true)
            {
                //
                // This refund is already being sent to scrooge and will be reconciled
                // (as per API). This is a duplicate refund row, So we should increment
                // the success count to account for this row.
                // If we do not increment success count here, then processed_count will never be
                // equal to success_count + failure_count, and batch will remain in `created` state.
                //
                $this->setSummaryCount(self::SUCCESSES_SUMMARY, $this->refund->getId());
            }
            else
            {
                // This will be unset if `validateRefundDetails` fails later in the flow.
                // However, cannot remove it here as this variable is being used in between the flow
                static::$scroogeReconciliate[$this->refund->getId()] = new Base\Foundation\ScroogeReconciliate;
            }
        }

        return $this->refund;
    }

    /**
     * Checks if amount in recon file matches the actual amount in refund entity
     * Implementation to be provided by child classes
     *
     * @param  array $row Row data
     *
     * @return bool
     */
    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $reconRefundAmount = $this->getReconRefundAmount($row);

        //
        //  If refund amount column is expected in gateway recon but not present in MIS.
        //  this will return false and amount validation fails.
        //
        if ($reconRefundAmount === false)
        {
            return false;
        }

        //
        // If refund column is not defined for the gateway recon, this will return
        // true. Because that means, either we are not recseiving refund amount column in MIS or
        // we do not want to validate amount for this gateway, in such cases, validation
        // always returns true.
        //
        if ($reconRefundAmount === null)
        {
            return true;
        }

        // To handle multi-currency, get amount/base amount of refund entity
        $refundEntityAmount = $this->getRefundEntityAmount();

        if ($refundEntityAmount !== $reconRefundAmount)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $refundEntityAmount,
                    'recon_amount'      => $reconRefundAmount,
                    'payment_id'        => $this->payment->getId(),
                    'currency'          => $this->payment->getCurrency(),
                    'gateway'           => $this->gateway,
                    'batch_id'          => $this->batchId,
                ]);

            return false;
        }

        return true;
    }

    /**
     * Checks the refund recon status being sent in the file
     * Override in child class
     * @param array $row
     * @return bool
     */
    protected function getReconRefundStatus(array $row)
    {
        //
        // The return value of this method must be mapped to one of the statuses in Payment\Refund\Status
        //
        return null;
    }

    /**
     * Checks if currency in recon file matches the actual currency in
     * refund entity. Implementation to be provided by child classes.
     *
     * @param  array $row Row data
     *
     * @return bool
     */
    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        return true;
    }

    /**
     * If this is being implemented in the child class,
     * the setter for storing the arn should be present
     * in the gateway entity.
     *
     * @param $row array
     * @return null
     */
    protected function getArn(array $row)
    {
        return null;
    }

    /**
     * If this is being implemented in the child class,
     * the setter for storing the arn should be present
     * in the gateway entity.
     *
     * @param $row array
     * @return null
     */
    protected function getGatewayTransactionId(array $row)
    {
        return null;
    }

    /**
     * If this is being implemented in the child class,
     * the setter for storing the payment reference_number
     * should be present in the gateway entity.
     *
     * @param $row array
     *
     * @return null
     */
    protected function getReferenceNumber(array $row)
    {
        return null;
    }

    /**
     * Saves the Arn number, if present in the refund entity
     *
     * @param $rowDetails array
     */
    protected function persistRefundArn(array $rowDetails)
    {
        $refund = $this->refund;

        if (empty($rowDetails[BaseReconciliate::ARN]) === true)
        {
            if ($refund->getStatus() !== Refund\Status::PROCESSED)
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code'     => Base\InfoCode::UNPROCESSED_REFUND_ARN_ABSENT,
                        'message'       => 'ARN absent for an unprocessed refund, not marked as processed.',
                        'payment_id'    => $this->payment->getId(),
                        'refund_id'     => $refund->getId(),
                        'refund_status' => $refund->getStatus(),
                        'gateway'       => $this->gateway,
                        'batch_id'      => $this->batchId,
                    ]);
            }

            return;
        }

        $reconArn = $rowDetails[BaseReconciliate::ARN];

        $refundAcquirerData = $refund->getAcquirerData();

        $currentArn = $refundAcquirerData[Refund\Entity::ARN] ?? "";

        if (empty($refundAcquirerData[Refund\Entity::ARN]) === false)
        {
            //
            // If the ARN in DB matches the
            // ARN from row, simply return
            //
            if ($currentArn === $reconArn)
            {
                //
                // We do not know about refund's arn and status in
                // scrooge, So need to send this refund to scrooge.
                //
                if ($this->isScroogeRefund() === true)
                {
                    static::$scroogeReconciliate[$this->refund->getId()]->setArn($reconArn);
                    static::$scroogeReconciliate[$this->refund->getId()]->setStatus(Refund\Status::PROCESSED);
                }

                return;
            }
            else if ($currentArn !== 'NA')
            {
                //
                // If the ARN in DB doesn't match the ARN from row,
                // there are three possibilities
                // - the value is NA
                //   don't do anything
                //   just continue and override it after this block.
                // - the value is not NA and force update is disabled
                //   raise an alert and return.
                // - If force update enabled, let recon
                //
                if ($this->shouldForceUpdate(RequestProcessor\Base::REFUND_ARN) === false)
                {
                    $this->trace->info(
                        TraceCode::RECON_MISMATCH,
                        [
                            'info_code'     => Base\InfoCode::DUPLICATE_ROW,
                            'message'       => 'Arn number for the refund entity does not match',
                            'refund_id'     => $refund->getId(),
                            'amount'        => $refund->getAmount(),
                            'refund_arn'    => $currentArn,
                            'recon_arn'     => $reconArn,
                            'gateway'       => $this->gateway,
                            'batch_id'      => $this->batchId,
                        ]);

                    return;
                }
            }
        }

        if ($this->isScroogeRefund() === true)
        {
            static::$scroogeReconciliate[$this->refund->getId()]->setArn($reconArn);
            static::$scroogeReconciliate[$this->refund->getId()]->setStatus(Refund\Status::PROCESSED);
        }
        else
        {
            $processor = $this->getNewProcessor($refund->merchant);

            if ($processor->isValidArn($reconArn) === true)
            {
                // To be deprecated later
                $processor->updateReference1AndTriggerEventArnUpdated($refund, $reconArn, false);
            }

            $refund->setStatusProcessed();

            // This needs to be present here and not in the calling function,
            // to ensure that if any failure happens, arn still gets saved.
            $this->repo->saveOrFail($refund);

            $this->core->pushRefundProcessedMetric($refund, $this->source);
        }
    }

    public function getNewProcessor($merchant)
    {
        return new Payment\Processor\Processor($merchant);
    }

    protected function persistGatewayData(array $rowDetails)
    {
        $gatewayRefund = $this->getGatewayRefund($this->refund->getId());

        if ($gatewayRefund === null)
        {
            return;
        }

        $this->persistGatewayArn($rowDetails, $gatewayRefund);

        $this->persistReferenceNumber($rowDetails, $gatewayRefund);

        $this->persistGatewayTransactionId($rowDetails, $gatewayRefund);

        $this->persistGatewayUtr($rowDetails, $gatewayRefund);

        if ($this->isScroogeRefund() === true)
        {
            //
            // getDirty() gets the attributes that have been changed since last sync.
            //
            static::$scroogeReconciliate[$this->refund->getId()]->setGatewayKeys($gatewayRefund->getDirty());
        }

        $this->repo->saveOrFail($gatewayRefund);
    }

    /**
     * Saving the Bank Payment Id from reconciliator file
     * Replacing existing value or adding it to the DB
     *
     * @param array        $rowDetails
     * @param PublicEntity $gatewayRefund
     */
    protected function persistReferenceNumber(array $rowDetails, PublicEntity $gatewayRefund)
    {
        if (empty($rowDetails[BaseReconciliate::REFERENCE_NUMBER]) === true)
        {
            return;
        }

        $referenceNumber = $rowDetails[BaseReconciliate::REFERENCE_NUMBER];

        $this->setReferenceNumberInGateway($referenceNumber, $gatewayRefund);
    }

    /**
     * Getting the gatewayRefund associated with payment entity.
     * It is implemented in the child class.
     *
     * @param string $refundId
     *
     * @return null
     */
    protected function getGatewayRefund(string $refundId)
    {
        return null;
    }

    /**
     * Sets the arn number in the corresponding gateway
     *
     * @param $rowDetails array
     * @param $gatewayRefund PublicEntity
     */
    protected function persistGatewayArn(array $rowDetails, PublicEntity $gatewayRefund)
    {
        if (empty($rowDetails[BaseReconciliate::ARN]) === true)
        {
            return;
        }

        $arn = $rowDetails[BaseReconciliate::ARN];

        $this->setArnInGateway($arn, $gatewayRefund);
    }

    /**
     * Sets the arn number in the corresponding gateway
     *
     * @param $rowDetails array
     * @param $gatewayRefund PublicEntity
     */
    protected function persistGatewayTransactionId(array $rowDetails, PublicEntity $gatewayRefund)
    {
        if (empty($rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID]) === true)
        {
            return;
        }

        $gatewayTransactionId = $rowDetails[BaseReconciliate::GATEWAY_TRANSACTION_ID];

        $this->setGatewayTransactionId($gatewayTransactionId, $gatewayRefund);
    }

    /**
     * This function is implemented in the child class
     * Every gateway has a different name mapped for "arn"
     * e.g. : hdfc calls it 'arn_no'
     *
     * If this is being implemented in child class,
     * make sure, the corresponding setter is present in the gateway
     *
     * @param $arn string
     * @param $gatewayRefund PublicEntity
     */
    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        return;
    }

    /**
     * The reason that it is implemented this way is because different
     * gateway entities may have different attribute names to store the
     * gateway Transaction ID.
     * So, other gateways can implement this function with the
     * appropriate setter.
     *
     * @param string       $gatewayTransactionId
     * @param PublicEntity $gatewayRefund
     */
    protected function setGatewayTransactionId(string $gatewayTransactionId, PublicEntity $gatewayRefund)
    {
        $dbGatewayTransactionId = (string) $gatewayRefund->getGatewayTransactionId();

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayTransactionId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getAmount(),
                    'payment_id'                => $this->payment->getId(),
                    'payment_amount'            => $this->payment->getAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);

            return;
        }

        $gatewayRefund->setGatewayTransactionId($gatewayTransactionId);
    }

    /**
     * The reason that it is implemented this way is because different
     * gateway entities may have different attribute names to store the
     * reference number.
     * So, other gateways can implement this function with the
     * appropriate setter.
     *
     * @param string       $referenceNumber
     * @param PublicEntity $gatewayRefund
     */
    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayRefund)
    {
        $dbReferenceNumber = (string) $gatewayRefund->getBankPaymentId();

        if ((empty($dbReferenceNumber) === false) and
            ($dbReferenceNumber !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getAmount(),
                    'payment_id'                => $this->payment->getId(),
                    'payment_amount'            => $this->payment->getAmount(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway,
                    'batch_id'                  => $this->batchId,
                ]);

            return;
        }

        $gatewayRefund->setBankPaymentId($referenceNumber);
    }
}
