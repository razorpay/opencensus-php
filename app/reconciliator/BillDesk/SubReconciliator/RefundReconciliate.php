<?php

namespace Reconciliator\BillDesk;

use Reconciliator\Base;
use Reconciliator\Messenger;


class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID = 'Ref. 1';

    protected $messenger;


    public function __construct()
    {
        $this->messenger = new Messenger();
        parent::__construct();
    }


    protected function getRefundId($row)
    {
        // TODO: This is actually the payment ID.
        // Check with Shk on how to match the refund ID in api with
        // Billdesk's refund ID. (Show the billdesk refund recon file)
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }
}