<?php

namespace RZP\Reconciliator\NetbankingSbi\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Gateway\Netbanking;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Refund;
use RZP\Exception\BadRequestException;
use RZP\Exception\ReconciliationException;
use RZP\Gateway\Netbanking\Sbi\ReconFields\RefundReconFields;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const SUCCESS               = 'success';
    const DECLINED              = 'declined';
    const FAILURE               = 'failure';
    const VALIDATION_FAILURE    = 'validation failure';
    const COLUMN_REFUND_AMOUNT  = RefundReconFields::AMOUNT;

    const VALID_STATUS          = [
                                    self::SUCCESS,
                                    self::FAILURE,
                                    self::DECLINED,
                                    self::VALIDATION_FAILURE,
                                  ];

    const REFUND_STATUS         = 'refund_status';
    const ERROR_DESCRIPTION     = 'error_description';

    const BLACKLISTED_COLUMNS = [];

    const REFERENCE3          = 'reference3';
    const SEQUENCE_NO         = 'sequence_no';

    protected function getRefundId(array $row)
    {
        $paymentId      = $row[RefundReconFields::MERCHANT_REF_NO] ?? null;

        $sequenceNumber = $row[RefundReconFields::SEQ_NO] ?? null;

        if ((empty($paymentId) === true) or (empty($sequenceNumber) === true))
        {
            $this->setFailUnprocessedRow(false);

            return null;
        }

        $sequenceNumber = (int) $sequenceNumber;

        $refundId = null;

        try
        {
            $this->refund = $this->repo->refund->findByPaymentIdAndReference3($paymentId, $sequenceNumber);

            $refundId = $this->refund->getId();
        }
        catch (BadRequestException $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'            => TraceCode::RECON_MISMATCH,
                    'info_code'             => Base\InfoCode::REFUND_ABSENT,
                    'payment_id'            => $paymentId,
                    'sequence_number'       => $sequenceNumber,
                    'gateway'               => $this->gateway
                ]);

            $this->setFailUnprocessedRow(true);
        }

        return $refundId;
    }

    /**
     * Returns refund reference number to be stored in refund entity. We store rrn only for successful refunds
     *
     * @param array $row
     * @return mixed|null
     * @throws ReconciliationException
     *
     */
    protected function getArn(array $row)
    {
        $status = $this->getReconRefundStatus($row);

        if (($status === Refund\Status::PROCESSED) and (empty($row[RefundReconFields::REFUND_REF_NO]) === false))
        {
            return $row[RefundReconFields::REFUND_REF_NO];
        }

        return null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = strtolower($row[RefundReconFields::STATUS] ?? null);

        if (in_array($rowStatus, self::VALID_STATUS, true) === false)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::RECON_ROW_INVALID_FORMAT_FOUND,
                    'status'            => $rowStatus,
                    'refund_id'         => $this->refund->getId(),
                    'gateway'           => $this->gateway,
                ]);

            $this->setRowReconStatusAndError(Base\InfoCode::RECON_FAILED, Base\InfoCode::RECON_ROW_INVALID_FORMAT_FOUND);

            throw new ReconciliationException(Base\InfoCode::RECON_ROW_INVALID_FORMAT_FOUND);
        }

        if ($rowStatus === self::SUCCESS)
        {
            return Refund\Status::PROCESSED;
        }
        elseif ($rowStatus === self::DECLINED)
        {
            return self::DECLINED;
        }

        return Refund\Status::FAILED;
    }

    /**
     * In addition to the validations in base, in case the status of refund is reported as failed in recon file,
     * the sequence number has to be incremented as this is expected by sbi. Also based on the status we update
     * gateway_refunded of refund.
     *
     * @param array $row
     * @return bool
     * @throws ReconciliationException
     *
     */
    protected function validateRefundDetails(array $row)
    {
        $validRefundDetails = parent::validateRefundDetails($row);

        // Validation failure not related to refund status
        if (($validRefundDetails === false) and ($this->validateRefundReconStatus($row) === true))
        {
            return $validRefundDetails;
        }

        $status = $this->getReconRefundStatus($row);

        $refund = $this->refund;

        if ($status === Refund\Status::PROCESSED)
        {
            static::$scroogeReconciliate[$this->refund->getId()]->setGatewayKeys([
                self::GATEWAY_STATUS      => self::SUCCESS,
            ]);

            $refund = $this->setGatewayRefunded(true, $refund);
        }
        else
        {
            $refund = $this->incrementSequenceCount($refund);

            $updatedReference3 = $refund->getReference3();

            if ($this->shouldRetry($row) === false)
            {
                $refund = $this->setGatewayRefunded(false, $refund);
            }

            static::$scroogeReconciliate[$this->refund->getId()]->setGatewayKeys([
                self::GATEWAY_STATUS      => $status,
                self::SEQUENCE_NO         => $updatedReference3,
            ]);
        }

        $this->repo->saveOrFail($refund);

        return $validRefundDetails;
    }

    protected function shouldRetry($row)
    {
        $status = strtolower($row[RefundReconFields::STATUS]);

        return $status !== self::DECLINED;
    }

    /**
     * We set gateway_refunded to true for success status response from sbi and false if we do not want to retry the
     * refund anymore. We keep it as null if we got a failure response but we wish to retry it again. Retry is
     * determined based on status. If the status is declined then we do not retry the refund
     *
     * @param bool $status
     * @param $refund
     * @return mixed
     *
     */
    private function setGatewayRefunded(bool $status, $refund)
    {
        $currentGatewayRefunded = $refund->getGatewayRefunded();

        if ($currentGatewayRefunded === true)
        {
            return $refund;
        }

        $this->trace->info(
            TraceCode::RECON_INFO,
            [
                'message'         => 'Set Gateway Refunded of refund',
                'info_code'       => Base\InfoCode::RECON_GATEWAY_REFUNDED,
                'payment_id'      => $this->payment->getId(),
                'refund'          => $this->refund->getId(),
                'status'          => $status,
                'gateway'         => $this->gateway,

            ]);

        $refund->setGatewayRefunded($status);

        return $refund;
    }

    /**
     * Sbi treats each retry as a new refund request. Hence we would need to increment the sequence count for the failed
     * refunds that we wish to retry.
     *
     * @param $refundEntity
     * @return array
     *
     */
    private function incrementSequenceCount($refundEntity)
    {
        $newSeqNo = Refund\Core::getNewRefundSequenceNumberForPayment($this->payment);

        $refundEntity->setReference3($newSeqNo);

        return $refundEntity;
    }

    /**
     * Additional row details of status and error description is required when persisting recon data
     *
     * @param $row
     * @return array|null
     * @throws ReconciliationException
     * @throws \RZP\Exception\LogicException
     */
    protected function getRowDetailsStructured($row)
    {
        $rowDetails = parent::getRowDetailsStructured($row);

        if (empty($rowDetails) === true)
        {
            return $this->handleUnprocessedRow($row);
        }

        $rowDetails[self::REFUND_STATUS] = $this->getReconRefundStatus($row);

        if ($rowDetails[self::REFUND_STATUS] === Refund\Status::FAILED)
        {
            $rowDetails[self::ERROR_DESCRIPTION] = $row[RefundReconFields::REMARKS] ?? null;
        }

        return $rowDetails;
    }
}
