<?php

namespace Reconciliator\Axis;


use Reconciliator\Base;
use Reconciliator\Base\Reconciliate as BaseReconciliate;
use Reconciliator\Messenger;


class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID   = 'merchant_trans_ref';

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
}