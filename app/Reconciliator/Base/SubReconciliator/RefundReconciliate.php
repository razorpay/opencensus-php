<?php

namespace RZP\Reconciliator\Base;

use App;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Refund;
use RZP\Reconciliator\Messenger;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Reconciliator\RequestProcessor;
use RZP\Exception\ReconciliationException;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class RefundReconciliate extends Foundation\SubReconciliate
{
    /*******************
     * Instance objects
     *******************/

    // This will need to be overridden in each gateway's refund recon.
    const COLUMN_REFUND_AMOUNT = '';

    // List of gateways whose refund status must be set to processed without ARN
    const GATEWAYS_PROCESSED_WO_ARN = [
        RequestProcessor\Base::UPI_ICICI
    ];

    protected $messenger;

    /**
     * @var Payment\Entity
     */
    protected $payment;

    /**
     * @var Refund\Entity
     */
    protected $refund;

    public function __construct(string $gateway = null)
    {
        parent::__construct($gateway);

        $this->messenger = new Messenger();
    }

    public function runReconciliate($row)
    {
        //
        // Resetting row attributes here which could have been set during
        // reconciliation of a particular row. This is mainly done for
        // resetting failUnprocessedRow attribute which should be reset
        // for each row.
        //
        $this->resetRowProcessingAttributes();

        $rowDetails = $this->getRowDetailsStructured($row);

        if (empty($rowDetails) === true)
        {
            return $this->handleUnprocessedRow($row);
        }

        $refundId = $rowDetails[BaseReconciliate::REFUND_ID];

        try
        {
            $this->runPreReconciledAtCheckRecon($rowDetails);

            $reconciled = $this->checkIfAlreadyReconciled($this->refund);

            // Increment the total count for the summary
            $this->setSummaryCount(self::TOTAL_SUMMARY, $refundId);

            if ($reconciled === true)
            {
                $this->handleAlreadyReconciled($refundId);

                return;
            }

            $validate = $this->validateRefundDetails($row);

            if ($validate === true)
            {
                $persistSuccess = $this->persistReconciliationData($rowDetails);

                if ($persistSuccess === false)
                {
                    // Increment the failure count for the summary.
                    $this->setSummaryCount(self::FAILURES_SUMMARY, $refundId);
                }
            }
            else
            {
                // Increment the failure count for the summary.
                $this->setSummaryCount(self::FAILURES_SUMMARY, $refundId);
            }
        }
        catch (\Exception $ex)
        {
            // Ideally, there shouldn't be any exceptions thrown. They should be handled
            // in the respective reconciliation steps.

            // Increment the failure count for the summary.
            $this->setSummaryCount(self::FAILURES_SUMMARY, $refundId);

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_FAILURE,
                    'message'       => 'Unable to perform one of the reconciliation actions -> ' . $ex->getMessage(),
                    'row'           => $row,
                    'extra_details' => $this->extraDetails,
                    'gateway'       => $this->gateway
                ]);

            $this->trace->traceException($ex);

            throw $ex;

            //return;
        }
    }

    public function resetRowProcessingAttributes()
    {
        $this->payment = null;
        $this->refund  = null;

        parent::resetRowProcessingAttributes();
    }

    protected function getReconRefundAmount(array $row)
    {
        if (isset($row[static::COLUMN_REFUND_AMOUNT]) === false)
        {
            return null;
        }

        $refundAmount = floatval($row[static::COLUMN_REFUND_AMOUNT]) * 100;

        return $refundAmount;
    }

    protected function runPreReconciledAtCheckRecon(array $rowDetails)
    {
        $this->persistGatewaySettledAt($this->refund, $rowDetails);

        $this->persistRefundArn($rowDetails);

        $this->persistGatewayData($rowDetails);
    }

    protected function validateRefundDetails(array $row)
    {
        $validPaymentStatus = $this->validatePaymentStatus();

        $validRefundAmount = $this->validateRefundAmountEqualsReconAmount($row);

        $validCurrencyCode = $this->validateRefundCurrencyEqualsReconCurrency($row);

        $validRefundDetails = (($validPaymentStatus === true) and
                               ($validRefundAmount === true) and
                               ($validCurrencyCode === true));

        return $validRefundDetails;
    }

    protected function validatePaymentStatus()
    {
        $paymentStatus = $this->payment->getStatus();

        if ($paymentStatus === Payment\Status::FAILED)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Payment status is failed.',
                    'payment_id' => $this->payment->getId(),
                    'gateway'    => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function persistReconciliationData(array $rowDetails)
    {
        $refundTransaction = $this->refund->transaction;

        if ($refundTransaction === null)
        {
            $createTransactionSuccess = $this->attemptToCreateMissingRefundTransaction();

            if ($createTransactionSuccess === false)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_MISMATCH,
                        'message'       => 'Refund transaction not found in DB',
                        'refund_id'     => $this->refund->getId(),
                        'gateway'       => $this->gateway
                    ]);

                return false;
            }

            // Refresh both refund and transaction to get latest changes.
            // Reload txn because relation are cached.
            $this->refund->reload()->transaction->reload();
        }

        $this->persistReconciledAt($this->refund);

        $this->persistGatewaySettledAt($this->refund, $rowDetails);

        $this->setRefundProcessedWithoutArn();

        return true;
    }

    protected function setRefundProcessedWithoutArn()
    {
        //
        // We check if refund is marked as processed already.
        // If it's not, only then we check whether we allow
        // it to be marked as processed without the ARN. If ARN
        // was present, we would have already marked it as processed.
        //
        if (($this->refund->isProcessed() === false) and
            (in_array($this->gateway, self::GATEWAYS_PROCESSED_WO_ARN, true) === true))
        {
            $this->refund->setStatusProcessed();

            $this->repo->saveOrFail($this->refund);
        }
    }

    protected function attemptToCreateMissingRefundTransaction()
    {
        $paymentTransaction = $this->payment->transaction;

        if ($paymentTransaction === null)
        {
            return false;
        }

        try
        {
            $txn = $this->createMissingRefundTransaction();

            if ($txn === null)
            {
                return false;
            }

            return true;
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_FAILURE,
                    'failure_code'  => 'REFUND_TRANSACTION_CREATE_FAIL',
                    'message'       => 'Refund transaction create failed with -> ' . $ex->getMessage(),
                    'payment_id'    => $this->payment->getId(),
                    'refund_id'     => $this->refund->getId(),
                    'gateway'       => $this->gateway,
                ]);

            $this->trace->traceException($ex);

            return false;
        }
    }

    protected function createMissingRefundTransaction()
    {
        assertTrue($this->refund->transaction === null);

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'info_code'     => 'REFUND_TRANSACTION_CREATE_RECON',
                'message'       => 'Attempting to create refund transaction in recon',
                'payment_id'    => $this->payment->getId(),
                'refund_id'     => $this->refund->getId(),
                'gateway'       => $this->gateway
            ]);

        $processor = new Payment\Processor\Processor($this->refund->merchant);

        $txn = $processor->createTransactionForRefund($this->refund, $this->payment);

        // This is required to save the association of the transaction with the refund.
        $this->repo->saveOrFail($this->refund);

        return $txn;
    }

    protected function getRowDetailsStructured($row)
    {
        $this->app['trace']->info(
            TraceCode::RECON_FILE_ROW,
            $row
        );

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
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Corresponding payment for the refund not found in DB.',
                    'row'        => $row,
                    'refund_id'  => $refundId,
                    'gateway'    => $this->gateway
                ]);

            throw new ReconciliationException(
                'Corresponding payment for the refund not found in the DB.',
                [
                    'refund_id' => $refundId,
                ]);

            //return null;
        }

        $gatewaySettledAt = $this->getGatewaySettledAt($row);

        $arn = $this->getArn($row);

        $gatewayTransactionId = $this->getGatewayTransactionId($row);

        $referenceNumber = $this->getReferenceNumber($row);

        $rowDetails = [
            BaseReconciliate::REFUND_ID              => $refundId,
            BaseReconciliate::GATEWAY_SETTLED_AT     => $gatewaySettledAt,
            BaseReconciliate::ARN                    => trim($arn),
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
            return null;
        }

        if (UniqueIdEntity::verifyUniqueId($refundId, false) === false)
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'message'    => 'Refund ID being sent in the file is not as expected.',
                    'row'        => $row,
                    'refund_id'  => $refundId,
                    'gateway'    => $this->gateway
                ]);

            return null;
        }

        try
        {
            $this->refund = $this->repo->refund->findOrFail($refundId);
        }
        catch (\Exception $ex)
        {
            $refundSuccess = $this->createRefundOnApi($row, $refundId, $ex);

            if ($refundSuccess === false)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_MISMATCH,
                        'message'       => 'Unable to create a refund on API after finding it missing',
                        'row'           => $row,
                        'refund_id'     => $refundId,
                        'gateway'       => $this->gateway,
                    ]);

                return null;
            }

            $this->refund = $this->repo->refund->findOrFail($refundId);
        }

        return $this->refund;
    }

    /**
     * This will create a refund on the API side. It will also check that
     * the refund on the gateway side is already created.
     *
     * The created refund and the refund id in the transaction entity
     * will have the refund ID set explicitly.
     *
     * Each gateway needs to implement this on its own.
     *
     * @param array      $row
     * @param string     $refundId
     * @param \Exception $ex
     *
     * @return bool returns true if successfully created. False otherwise.
     */
    protected function createRefundOnApi(array $row, string $refundId, \Exception $ex)
    {
        $this->messenger->raiseReconAlert(
            [
                'trace_code' => TraceCode::RECON_INFO_ALERT,
                'message'    => 'Refund not found in DB. -> ' . $ex->getMessage(),
                'row'        => $row,
                'refund_id'  => $refundId,
                'gateway'    => $this->gateway
            ]);

        $paymentId = $this->getPaymentId($row);

        $refundAmount = $this->getReconRefundAmount($row);

        //
        // Checking refundAmount with `empty` because there should
        // never be 0 refund amount if the flow has reached here.
        //
        if (($paymentId === null) or (empty($refundAmount) === true))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'row'           => $row,
                    'message'       => 'Unable to get the payment ID or amount from the refund recon file',
                    'refund_id'     => $refundId,
                    'refund_amount' => $refundAmount,
                    'payment_id'    => $paymentId,
                ]);

            return false;
        }

        $payment = $this->repo->payment->findOrFail($paymentId);

        $merchant = $payment->merchant;

        $processor = new Payment\Processor\Processor($merchant);

        try
        {
            $processor->createRefundOnApiFromRecon($payment, $refundId, $refundAmount);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);

            return false;
        }

        return true;
    }

    /**
     * Checks if amount in recon file matches the actual amount in refund entity
     * Implementation to be provided by child clasess
     *
     * @param  array $row Row data
     *
     * @return bool
     */
    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        return true;
    }


    /**
     * Checks if currency in recon file matches the actual currency in refund entity
     * Implementation to be provided by child clasess
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
        if (empty($rowDetails[BaseReconciliate::ARN]) === true)
        {
            return;
        }

        $reconArn = $rowDetails[BaseReconciliate::ARN];

        $refund = $this->refund;

        $refundAcquirerData = $refund->getAcquirerData();

        if (empty($refundAcquirerData[Refund\Entity::ARN]) === false)
        {
            $currentArn = $refundAcquirerData[Refund\Entity::ARN];

            //
            // If the ARN in DB matches the
            // ARN from row, simply return
            //
            if ($currentArn === $reconArn)
            {
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
                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code'    => TraceCode::RECON_MISMATCH,
                            'info_code'     => 'DUPLICATE_ROW',
                            'message'       => 'Arn number for the refund entity does not match',
                            'row'           => $rowDetails,
                            'refund_id'     => $refund->getId(),
                            'gateway'       => $this->gateway,
                            'refund_arn'    => $currentArn,
                        ]);

                    return;
                }
            }
        }

        $refund->setReference1($reconArn);
        $refund->setStatusProcessed();

        // This needs to be present here and not in the calling function,
        // to ensure that if any failure happens, arn still gets saved.
        $this->repo->saveOrFail($refund);
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
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => 'DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayTransactionId,
                    'gateway'                   => $this->gateway
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
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => 'DATA_MISMATCH',
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'payment_id'                => $this->payment->getId(),
                    'db_reference_number'       => $dbReferenceNumber,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayRefund->setBankPaymentId($referenceNumber);
    }
}
