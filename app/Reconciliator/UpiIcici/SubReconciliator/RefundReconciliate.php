<?php

namespace RZP\Reconciliator\UpiIcici\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Payment\Refund\Status;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const REFUND_ID            = 'merchanttranid';
    const COLUMN_REFUND_AMOUNT = 'refund_amount';
    const ORIGINAL_BANK_RRN    = 'original_bank_rrn';
    const REFUND_TRANS_DATE    = 'refund_transaction_date';
    const REFUND_TRANS_TIME    = 'refund_transaction_time';
    const REFUND_RRN           = 'refund_rrn';
    const STATUS               = 'status';
    const CUSTOMER_VPA         = 'customer_vpa';

    const BLACKLISTED_COLUMNS = [
        self::CUSTOMER_VPA,
    ];

    const SUCCESS = 'SUCCESS';

    protected function getRefundId(array $row)
    {
        if (empty($row[self::REFUND_ID]) === false)
        {
            return substr($row[self::REFUND_ID], 0, 14);
        }

        return null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $refundDate = $row[self::REFUND_TRANS_DATE] ?? null;

        $refundTime = $row[self::REFUND_TRANS_TIME] ?? null;

        if ((empty($refundDate) === true) or
            (empty($refundTime) === true))
        {
            return null;
        }

        $refundSettledAt = $refundDate . ' ' . $refundTime;

        return Carbon::createFromFormat('d-m-Y h:i a', $refundSettledAt, Timezone::IST)->getTimestamp();
    }

    /**
     * This method is called when refundId is not found in our DB. Therefore,
     * we would need to find it based on the UPI entity instead.
     *
     * @param array $row
     * @return null
     */
    protected function getPaymentId(array $row)
    {
        $rrn = $row[self::ORIGINAL_BANK_RRN] ?? null;

        if (empty($rrn) == true)
        {
            return null;
        }

        $upiEntity = $this->repo->upi->fetchByGatewayPaymentIdAndAction($rrn);

        return $upiEntity->getPaymentId();
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::REFUND_RRN] ?? null;
    }

    /**
     * Setting RRN in refund's reference1 attribute
     *
     * @param array $row
     * @return null
     */
    protected function getArn(array $row)
    {
        return $row[self::REFUND_RRN] ?? null;
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $reconAmount = $this->getReconRefundAmount($row);

        $refundAmount = $this->refund->getBaseAmount();

        if ($reconAmount !== $refundAmount)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'       => $this->refund->getId(),
                    'expected_amount' => $this->refund->getBaseAmount(),
                    'recon_amount'    => $this->getReconRefundAmount($row),
                    'currency'        => $this->refund->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getReconRefundAmount(array $row)
    {
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT]);
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayRefunds = $this->repo->upi->findByRefundIdAndAction($refundId, Action::REFUND);

        return $gatewayRefunds->first();
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
