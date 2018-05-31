<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'merchant_trackid';
    const COLUMN_REFUND_AMOUNT      = 'domestic_amt';
    const COLUMN_ARN                = 'arn_no';
    const COLUMN_SEQUENCE_NUMBER    = 'sequence_number';

    const COLUMN_TERMINAL_NUMBER    = 'terminal_number';

    /**
     * If we are not able to find refund id to reconcile,
     * this ratio defines the minimum proportion of columns to be filled in a valid row.
     * In HDFC MIS, many gst params and other params are always set to 0,
     * therefore if less than 10% of data is present, we don't mark row as failure.
     */
    const MIN_ROW_FILLED_DATA_RATIO = 0.10;

    /**
     * In case refund id is not set, function will return null,
     * row will be marked as failure in such case.
     *
     * @param array $row
     *
     * @return null|string
     */
    protected function getRefundId($row)
    {
        if ($this->isCybersource($row) === true)
        {
            $refundId = $this->getRefundIdForCybersource($row);
        }
        else
        {
            $refundId = $this->getRefundIdForFss($row);
        }

        if (empty($refundId) === true)
        {
            $this->evaluateRowProcessedStatus($row);
        }

        return $refundId;
    }

    protected function getRefundIdForFss(array $row)
    {
        $refundId = null;

        if (empty($row[self::COLUMN_REFUND_ID]) === false)
        {
            $refundId = $row[self::COLUMN_REFUND_ID];

            $refundId = trim(str_replace("'", '', $refundId));
        }

        return $refundId;
    }

    protected function getRefundIdForCybersource(array $row)
    {
        //
        // Currently, the way to get refundId for a Cybersource
        // refund is the same as for FSS refund. Keeping two
        // different functions for clarity sake and easy reading.
        //
        return $this->getRefundIdForFss($row);
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

    protected function getArn(array $row)
    {
        $arn = null;

        if (empty($row[self::COLUMN_ARN]) === false)
        {
            $arn = $row[self::COLUMN_ARN];

            $arn = trim(str_replace("'", '', $arn));

            if (stripos($arn, 'onus') !== false)
            {
                $arn = $this->getRRNForOnusTransaction($row);
            }
        }

        return $arn;
    }

    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = null;

        if (isset($row[self::COLUMN_REFUND_AMOUNT]) === true)
        {
            $refundAmount = $row[self::COLUMN_REFUND_AMOUNT];
        }

        return floatval($refundAmount) * 100;
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayEntities = $this->repo->hdfc->findSuccessfulRefundByRefundId($refundId);

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $refundEntity = $gatewayEntities->first();

        return $refundEntity;
    }

    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setArnNo($arn);
    }

    protected function isCybersource(array $row)
    {
        $terminalId = null;

        if (empty($row[self::COLUMN_TERMINAL_NUMBER]) === false)
        {
            $terminalId = $row[self::COLUMN_TERMINAL_NUMBER];

            $terminalId = trim(str_replace("'", '', $terminalId));
        }

        $isCybersource = (in_array($terminalId, Reconciliate::CYBERSOURCE_HDFC_TERMINAL_IDS, true) === true);

        return $isCybersource;
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
            return ((filled($value)) and ($value !== "' "));
        });

        $rowFilledRatio = count($nonEmptyData) / count($row);

        if ($rowFilledRatio < self::MIN_ROW_FILLED_DATA_RATIO)
        {
            $this->setFailUnprocessedRow(false);
        }
    }

    /**
     * In case of onus transaction, we don't receive ARN.
     * Storing 12 digit RRN in place of ARN, to share as a transaction reference with customers
     * If that is also not set, ARN will be set as 'NA'
     * @param array $row
     * @return string
     */
    protected function getRRNForOnusTransaction(array $row): string
    {
        $sequenceNumber = 'NA';

        if (empty($row[self::COLUMN_SEQUENCE_NUMBER]) === false)
        {
            $columnSeqNumber = $row[self::COLUMN_SEQUENCE_NUMBER];

            $sequenceNumberValue = str_replace("'", '', $columnSeqNumber);

            if (filled($sequenceNumberValue) === true)
            {
                $sequenceNumber = trim($sequenceNumberValue);
            }
        }

        return $sequenceNumber;
    }
}
