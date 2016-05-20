<?php

namespace Reconciliator\Axis\SubReconciliator;


use Reconciliator\Base\SubReconciliator;
use Reconciliator\Base\Reconciliate as BaseReconciliate;


class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const ROW_REFUND_ID   = 'merchant_trans_ref';
    const ROW_CARD_TYPE   = 'card_type';
    const ROW_SERVICE_TAX = 'service_tax145';
    const ROW_FEE         = 'commission';

    
    public function __construct()
    {
        parent::__construct();
    }
    

    protected function getRefundId($row)
    {
        $refundId = $row[self::ROW_REFUND_ID];

        return $refundId;
    }


    protected function getServiceTax($row)
    {
        $serviceTax = $row[self::ROW_SERVICE_TAX];

        // TODO: Verify this with shk.
        return floatval($serviceTax);
    }


    protected function getFee($row)
    {
        $fee = $row[self::ROW_FEE];

        // TODO: Verify this with shk.
        return floatval($fee);
    }


    protected function getCardType($row)
    {
        if (isset($row[self::ROW_CARD_TYPE]) === false)
        {
            return null;
        }

        $cardType = strtolower($row[self::ROW_CARD_TYPE]);

        if ($cardType === 'c')
        {
            $cardType = BaseReconciliate::CREDIT;
        }
        else if ($cardType === 'd')
        {
            $cardType = BaseReconciliate::DEBIT;
        }
        else
        {
            // TODO: Raise an alert for card type being present in the row
            // but the value is not what was expected.
        }

        return $cardType;
    }
}