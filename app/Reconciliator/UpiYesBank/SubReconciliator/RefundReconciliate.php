<?php

namespace RZP\Reconciliator\UpiYesBank\SubReconciliator;

use RZP\Models\Payment;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const ORDER_NUMBER          = 'order_no';
    const CUSTOMER_REF_NO       = 'customer_ref_no';
    const TRANSACTION_REMARK    = 'transaction_remarks';
    const TRANSACTION_STATUS    = 'transaction_status';

    const COLUMN_REFUND_AMOUNT    = 'transaction_amount';

    const SUCCESS = 'success';

    const BLACKLISTED_COLUMNS   = [];

    protected function getRefundId(array $row)
    {
        return $row[self::ORDER_NUMBER] ?? null;
    }

    protected function getArn(array $row)
    {
        return $row[self::CUSTOMER_REF_NO] ?? null;
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::CUSTOMER_REF_NO] ?? null;
    }

    protected function getReconRefundStatus(array $row)
    {
        $rowStatus = $row[self::TRANSACTION_STATUS] ?? null;

        if (strtolower($rowStatus) === self::SUCCESS)
        {
            return Payment\Refund\Status::PROCESSED;
        }

        return Payment\Refund\Status::FAILED;
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayRefund)
    {
        $npciRefId = (string) $gatewayRefund->getNpciReferenceId();

        if ((empty($npciRefId) === false) and
            ($npciRefId !== $referenceNumber))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->trace->info(
                TraceCode::RECON_MISMATCH,
                [
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'refund_id'                 => $this->refund->getId(),
                    'amount'                    => $this->refund->getBaseAmount(),
                    'payment_id'                => $this->payment->getId(),
                    'payment_amount'            => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $npciRefId,
                    'recon_reference_number'    => $referenceNumber,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        // We will only update the RRN if it is empty
        $gatewayRefund->setNpciReferenceId($referenceNumber);
    }
}
