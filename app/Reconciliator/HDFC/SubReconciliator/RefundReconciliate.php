<?php

namespace RZP\Reconciliator\HDFC;

use RZP\Reconciliator\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
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

    protected function getRefundId($row)
    {
        if ($this->isCybersource($row) === true)
        {
            $paymentId = $this->getRefundIdForCybersource($row);
        }
        else
        {
            $paymentId = $this->getRefundIdForFss($row);
        }

        return $paymentId;
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
        // Currently, we have no way to get a refund ID
        // from the MIS file.
        //
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

    protected function getArn(array $row)
    {
        $arn = null;

        foreach (self::COLUMN_ARN as $ca)
        {
            if (empty($row[$ca]) === false)
            {
                $arn = $row[$ca];

                $arn = trim(str_replace("'", '', $arn));

                if (strpos($arn, 'onus') !== false)
                {
                    $arn = 'NA';
                }

                break;
            }
        }

        return $arn;
    }

    protected function getRefundAmount(array $row)
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
}
