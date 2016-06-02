<?php

namespace Reconciliator\Axis\SubReconciliator;


use Reconciliator\Base\SubReconciliator;
use Reconciliator\Base\Reconciliate as BaseReconciliate;
use Reconciliator\Messenger;
use Trace\TraceCode;


class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID   = 'merchant_trans_ref';
    const COLUMN_CARD_TYPE   = 'card_type';
    const COLUMN_SERVICE_TAX = 'service_tax145';
    const COLUMN_FEE         = 'commission';

    protected $messenger;


    public function __construct()
    {
        $this->messenger = new Messenger();
        parent::__construct();
    }


    protected function getRefundId($row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }


    protected function getServiceTax($row)
    {
        $serviceTax = $row[self::COLUMN_SERVICE_TAX];

        // TODO: Verify this with shk.
        return floatval($serviceTax);
    }


    protected function getFee($row)
    {
        $fee = $row[self::COLUMN_FEE];

        // TODO: Verify this with shk.
        return floatval($fee);
    }


    protected function getCardType($row)
    {
        if (isset($row[self::COLUMN_CARD_TYPE]) === false)
        {
            return null;
        }

        $cardType = strtolower($row[self::COLUMN_CARD_TYPE]);

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
            $this->messenger->raiseReconAlert(
                [ 
                    'trace_code'      => TraceCode::RECON_PARSE_ERROR,
                    'message'         => 'Unable to figure out the card type.',
                    'recon_card_type' => $cardType,
                    'row'             => $row,
                    'gateway'         => get_class()
                ]);

            // It's as good as no card type present in the row.
            return null;
        }

        return $cardType;
    }
}