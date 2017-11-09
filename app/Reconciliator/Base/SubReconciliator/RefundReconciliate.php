<?php

namespace RZP\Reconciliator\Base;

use RZP\Exception\LogicException;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Models\Base\PublicEntity;

use App;
use RZP\Trace\TraceCode;
use RZP\Exception\ReconciliationException;

use RZP\Gateway\AxisMigs;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\Messenger;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class RefundReconciliate extends Foundation\SubReconciliate
{
    /*******************
     * Instance objects
     *******************/

    // This will need to be overridden in each gateway's refund recon.
    const COLUMN_REFUND_AMOUNT = '';

    protected $repo;
    protected $trace;
    protected $app;
    protected $messenger;

    /**
     * @var Payment\Entity
     */
    protected $payment;

    /**
     * @var Refund\Entity
     */
    protected $refund;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->trace = $this->app['trace'];

        $this->messenger = new Messenger();
    }

    /**
     * This is the start of the actual reconciliation for refunds.
     * Reconciliation is done for each row in the file content.
     * Validates payment status.
     * Sets the reconciled_at.
     *
     * @param array $fileContents
     * @return array
     */
    public function startReconciliation($fileContents)
    {
        $this->extraDetails = $fileContents[Orchestrator::EXTRA_DETAILS];
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($row)
            {
                $this->runReconciliate($row);
            });
        }

        return $this->getSummary();
    }

    public function runReconciliate($row)
    {
        $rowDetails = $this->getRowDetailsStructured($row);

        if (empty($rowDetails) === true)
        {
            return;
        }

        $refundId = $rowDetails[BaseReconciliate::REFUND_ID];

        try
        {
            $this->runPreReconciledAtCheckRecon($rowDetails);

            $reconciled = $this->checkIfAlreadyReconciled($this->refund);

            if ($reconciled === true)
            {
                return;
            }

            // Increment the total count for the summary
            $this->setSummaryCount(self::TOTAL_SUMMARY, $refundId);

            $validate = $this->validateRefundDetails($row);

            if ($validate === true)
            {
                $persistSuccess = $this->persistReconciliationData();

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
                    'gateway'       => get_called_class()
                ]);

            $this->trace->traceException($ex);

            throw $ex;

            //return;
        }
    }

    protected function getRefundAmount(array $row)
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

        $validRefundDetails = ($validPaymentStatus and $validRefundAmount);

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
                    'gateway'    => get_called_class()
                ]);

            return false;
        }

        return true;
    }

    protected function persistReconciliationData()
    {
        $refundTransaction = $this->refund->transaction;

        if ($refundTransaction === null)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code' => TraceCode::RECON_MISMATCH,
                    'message'    => 'Refund transaction not found in DB.',
                    'refund_id'  => $this->refund->getId(),
                    'gateway'    => get_called_class()
                ]);

            return false;
        }

        // Sets the reconciled_at in the transactions entity, on a successful reconciliation.
        $this->persistReconciledAt($this->refund);

        return true;
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
                    'gateway'    => get_called_class()
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

        $rowDetails = [
            BaseReconciliate::REFUND_ID             => $refundId,
            BaseReconciliate::GATEWAY_SETTLED_AT    => $gatewaySettledAt,
            BaseReconciliate::ARN                   => $arn,
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
                [
                    'trace_code' => TraceCode::RECON_INFO_ALERT,
                    'message'    => 'Refund ID being sent in the file is not as expected.',
                    'row'        => $row,
                    'refund_id'  => $refundId,
                    'gateway'    => get_called_class()
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
                        'trace_code' => TraceCode::RECON_MISMATCH,
                        'message' => 'Unable to create a refund on API after finding it missing',
                        'row' => $row,
                        'refund_id' => $refundId,
                        'gateway' => get_called_class(),
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
                'gateway'    => get_called_class()
            ]);

        $paymentId = $this->getPaymentId($row);

        $refundAmount = $this->getRefundAmount($row);

        //
        // Checking refundAmount with `empty` because there should
        // never be 0 refund amount if the flow has reached here.
        //
        if (($paymentId === null) or (empty($refundAmount) === true))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'row' => $row,
                    'message' => 'Unable to get the payment ID or amount from the refund recon file',
                    'refund_id' => $refundId,
                    'refund_amount' => $refundAmount,
                    'payment_id' => $paymentId,
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
                if ($this->shouldForceUpdate(BaseReconciliate::ARN) === false)
                {
                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code'    => TraceCode::RECON_MISMATCH,
                            'message'       => 'Arn number for the refund entity does not match',
                            'row'           => $rowDetails,
                            'refund_id'     => $refund->getId(),
                            'gateway'       => get_called_class(),
                            'refund_arn'    => $currentArn,
                        ]);

                    return;
                }
            }
        }

        $refund->setReference1($reconArn);
        $refund->setStatusProcessed();

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
}
