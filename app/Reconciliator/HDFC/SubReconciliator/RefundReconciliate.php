<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID      = ['merchant_trackid', 'MERCHANT_TRACKID'];
    const COLUMN_REFUND_AMOUNT  = ['domestic_amt', 'DOMESTIC AMT'];
    const COLUMN_ARN            = ['arn_no', 'ARN NO'];

    const COLUMN_TERMINAL_NUMBER    = ['terminal_number', 'TERMINAL NUMBER'];

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

        foreach (self::COLUMN_REFUND_ID as $cri)
        {
            if (empty($row[$cri]) === false)
            {
                $refundId = $row[$cri];

                $refundId = trim(str_replace("'", '', $refundId));

                break;
            }
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

        foreach (self::COLUMN_ARN as $ca)
        {
            if (empty($row[$ca]) === false)
            {
                $arn = $row[$ca];

                $arn = trim(str_replace("'", '', $arn));

                if (stripos($arn, 'onus') !== false)
                {
                    $arn = 'NA';
                }

                break;
            }
        }

        return $arn;
    }

    protected function getReconRefundAmount(array $row)
    {
        $refundAmount = null;

        foreach (self::COLUMN_REFUND_AMOUNT as $cra)
        {
            if (isset($row[$cra]) === true)
            {
                $refundAmount = $row[$cra];

                break;
            }
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

        foreach (self::COLUMN_TERMINAL_NUMBER as $ctn)
        {
            if (empty($row[$ctn]) === false)
            {
                $terminalId = $row[$ctn];

                $terminalId = trim(str_replace("'", '', $terminalId));

                break;
            }
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
}
