<?php

namespace RZP\Reconciliator\CardFssSbi\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\SubReconciliator\Helper as Helper;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const ONUS_INDICATOR = 'onus';

    const COLUMN_REFUND_AMOUNT = ReconciliationFields::TRANSACTION_AMOUNT;

    public function getRefundId(array $row)
    {
        $refundId = $row[ReconciliationFields::MERCHANT_TXN_NO] ?? null;

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
        $paymentId = $row[ReconciliationFields::PRCHS_MERCHANT_TXNNO] ?? null;

        return trim(str_replace("'", '', $paymentId));
    }

    protected function getGatewayRefund(string $refundId)
    {
        return $this->repo
                    ->card_fss
                    ->findOrFailRefundByRefundId($refundId);
    }

    protected function getReconRefundAmount(array $row)
    {
        $amt = $row[ReconciliationFields::TRANSACTION_AMOUNT] ?? null;

        return Helper::getIntegerFormattedAmount($amt);
    }

    protected function getArn(array $row)
    {
        $rrn = $row[ReconciliationFields::TXN_REF] ?? null;

        if (empty($rrn) === true)
        {
            $this->reportMissingColumn($row, ReconciliationFields::TXN_REF);
        }

        // Removing dependency on onus indicator while saving arn.
        // Slack thread - https://razorpay.slack.com/archives/CGXKVCMAL/p1622102381070900

        //$onusIndicator = $this->getOnusIndicator($row);

//        if ($onusIndicator === self::ONUS_INDICATOR)
//        {
//            return $rrn;
//        }

        return $rrn;
    }

    protected function getOnusIndicator($row)
    {
        return strtolower($row[ReconciliationFields::ONUS_INDICATOR] ?? '');
    }

    protected function setArnInGateway(string $arn, PublicEntity $gatewayRefund)
    {
        $gatewayRefund->setRef($arn);
    }
}
