<?php

namespace RZP\Reconciliator\CardFssHdfc\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_GATEWAY_TRANSACTION_ID   = 'aggregator_transaction_id';
    const COLUMN_REFUND_ID                = 'merchant_track_id';
    const COLUMN_REFUND_AMOUNT            = 'transaction_amount';
    const COLUMN_REFERENCE_TRANSACTION_ID = 'reference_tran_id';

    /**
     * If we are not able to find refund id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In CardFSS MIS, last row can have some string like "END OF REPORT" or some random value`
     * So if less than 20% data is present in a row, we don't mark row unprocessing status as failure.
     */
    const MIN_ROW_FILLED_DATA_RATIO = 0.20;

    protected function getRefundId(array $row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        if (empty($refundId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }
        
        if (empty($refundId) === false)
        {
            $refundId = trim(str_replace("'", '', $refundId));
            
            // Sometimes we get digits appended in refund ID
            // so take first 14 chars only.
            //
            return substr($refundId, 0, 14);
        }

        return null;

    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntity = $this->getGatewayRefund($refundId);

        if ($gatewayEntity === null)
        {
            return null;
        }

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getGatewayRefund(string $refundId)
    {
        return $this->repo
                    ->card_fss
                    ->findOrFailRefundByRefundId($refundId);
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setRef($referenceNumber);
    }

    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = $row[self::COLUMN_REFUND_AMOUNT];

        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($refundAmount);
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getBaseAmount() !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => 'Refund amount mismatch',
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $this->refund->getBaseAmount(),
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
                    'gateway'           => $this->gateway,
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_GATEWAY_TRANSACTION_ID] ?? null;
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::COLUMN_REFERENCE_TRANSACTION_ID] ?? null;
    }

    protected function getArn(array $row)
    {
        //
        // In MIS file, we are not receiving ARN hence storing RRN in ARN field of refund entity.
        // This is done because for reporting purposes, we need reference number in refund entity.
        //
        return $row[self::COLUMN_REFERENCE_TRANSACTION_ID] ?? null;
    }

    /**
     * This function evaluate and marks the row processing as success or failure based on
     * percentage of data available in a row.
     *
     * @param $row
     */
    protected function evaluateRowProcessedStatus(array $row)
    {
        $nonEmptyData = array_filter($row, function($value) {
            return filled($value);
        });

        $rowFilledRatio = count($nonEmptyData) / count($row);

        if ($rowFilledRatio < self::MIN_ROW_FILLED_DATA_RATIO)
        {
            $this->setFailUnprocessedRow(false);
        }
    }
}
