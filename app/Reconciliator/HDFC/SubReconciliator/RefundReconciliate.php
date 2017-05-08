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
    const COLUMN_RRN            = ['arn_no', 'ARN NO'];

    protected function getRefundId(array $row)
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

    protected function getRrn(array $row)
    {
        $rrn = null;

        foreach (self::COLUMN_RRN as $cr)
        {
            if (empty($row[$cr]) === false)
            {
                $rrn = $row[$cr];

                $rrn = trim(str_replace("'", '', $rrn));

                break;
            }
        }

        return $rrn;
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

    protected function setRrnInGateway(string $rrn, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setArnNo($rrn);
    }
}
