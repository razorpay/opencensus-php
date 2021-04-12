<?php

namespace RZP\Reconciliator\UpiSbi\SubReconciliator;

use RZP\Models\Payment;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const RRN                     = 'transrefno';
    const BANK_REMARK             = 'bankremark';
    const COLUMN_REFUND_ID        = ['refreqno', 'refundreqno'];
    const COLUMN_REFUND_AMOUNT    = 'refundreqamt';

    const SUCCESS = 'refund success';
    const FAILED_STATUSES = ['invalid orderno', 'refund not allowed for failed transaction'];

    const BLACKLISTED_COLUMNS = [];

    protected function getRefundId(array $row)
    {
        return Base\SubReconciliator\Helper::getArrayFirstValue($row, self::COLUMN_REFUND_ID);
    }

    protected function getArn(array $row)
    {
        return $row[self::RRN] ?? null;
    }

    /**
     * Scrooge wants to receive a status as well.
     * If a refund is not assorted in success
     * or failed status by defined params, scrooge
     * should receive pending as the transaction's
     * status from recon. This logic is kept in
     * API so as to not have gateway specific
     * logic in scrooge codebase.
     *
     * @param array $row
     * @return bool|string
     */
    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = strtolower($row[self::BANK_REMARK] ?? null);

        $bankRemark = $row[self::BANK_REMARK] ?? null;

        if ($rowStatus === self::SUCCESS)
        {
            static::$scroogeReconciliate[$this->refund->getId()]->setGatewayKeys([
                self::RECON_STATUS   => Payment\Refund\Status::PROCESSED,
                self::GATEWAY_STATUS => $bankRemark,
                ]
            );

            return Payment\Refund\Status::PROCESSED;
        }
        elseif (in_array($rowStatus,self::FAILED_STATUSES) === true)
        {
            static::$scroogeReconciliate[$this->refund->getId()]->setGatewayKeys([
                self::RECON_STATUS   => Payment\Refund\Status::FAILED,
                self::GATEWAY_STATUS => $bankRemark,
                ]
            );

            return Payment\Refund\Status::FAILED;
        }

        static::$scroogeReconciliate[$this->refund->getId()]->setGatewayKeys([
            self::RECON_STATUS   => Payment\Refund\Status::PENDING,
            self::GATEWAY_STATUS => $bankRemark,
            ]
        );

        return Payment\Refund\Status::FAILED;
    }
}
