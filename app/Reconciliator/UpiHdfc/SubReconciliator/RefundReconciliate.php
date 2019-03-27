<?php

namespace RZP\Reconciliator\UpiHdfc\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_ID              = 'new_refund_order_id';
    const COLUMN_TRANSACTION_AMOUNT     = 'transaction_amount';
    const COLUMN_TRANSACTION_DATE       = 'transaction_date';
    const COLUMN_CUSTOMER_REF_NO        = 'customer_ref_no';
    const COLUMN_TRANSACTION_TYPE       = 'drcr';
    const COLUMN_TRANSACTION_STATUS     = 'transaction_status';
    const COLUMN_TRANSACTION_REMARKS    = 'transaction_remarks';

    const SUCCESS   = 'SUCCESS';
    const DEBIT     = 'Debit';
    const APPROVED  = 'Approved';

    protected function getRefundId(array $row)
    {
        //
        // Here MIS file contains payment (credit) entries as well.
        // As we only process refund (debit) entries with transaction status as 'SUCCESS',
        // we return refundId as null so as to not process the payment entries, or
        // the refund entries having status != success
        //
        if ((empty($row[self::COLUMN_TRANSACTION_TYPE]) === true) or
            (empty($row[self::COLUMN_TRANSACTION_STATUS]) === true) or
            ($row[self::COLUMN_TRANSACTION_TYPE] !== self::DEBIT) or
            ($row[self::COLUMN_TRANSACTION_STATUS] !== self::SUCCESS))
        {
            $this->setFailUnprocessedRow(false);

            return null;
        }

        // check if we have our refund ID in the row
        if ($this->checkIfUnexpectedRefundId($row) === true)
        {
            return null;
        }

        if (empty($row[self::COLUMN_REFUND_ID]) === false)
        {
            return substr($row[self::COLUMN_REFUND_ID], 0, 14);
        }

        return null;
    }

    protected function checkIfUnexpectedRefundId($row)
    {
        if ((empty($row[self::COLUMN_TRANSACTION_REMARKS]) === false) and
            ($row[self::COLUMN_TRANSACTION_REMARKS] === self::APPROVED) and
            (empty($row[self::COLUMN_REFUND_ID]) === false) and
            (strpos($row[self::COLUMN_REFUND_ID], 'UPI') === 0))
        {
            //
            // This happens  where we get refund ID like 'UPI12345...' etc
            // This is a case when refund has been issued manually from
            // bank's dashboard so we do not get our refund id in the column.
            //

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'  => Base\InfoCode::RECON_UNEXPECTED_REFUND,
                    'refund_id'  => $row[self::COLUMN_REFUND_ID],
                    'gateway'    => $this->gateway
                ]);

            return true;
        }

        return false;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $refundSettledAt = $row[self::COLUMN_TRANSACTION_DATE] ?? null;

        if (empty($refundSettledAt) === true)
        {
            return null;
        }

        return Carbon::createFromFormat('d-M-Y H:i:s', $refundSettledAt, Timezone::IST)->getTimestamp();
    }

    protected function getReferenceNumber(array $row)
    {
        return $row[self::COLUMN_CUSTOMER_REF_NO] ?? null;
    }

    /**
     * Setting RRN in refund's reference1 attribute
     *
     * @param array $row
     * @return null
     */
    protected function getArn(array $row)
    {
        return $row[self::COLUMN_CUSTOMER_REF_NO] ?? null;
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
        return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_TRANSACTION_AMOUNT] ?? null);
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayRefunds = $this->repo->upi->findByRefundIdAndAction($refundId, Action::REFUND);

        return $gatewayRefunds->last();
    }

    protected function setReferenceNumberInGateway(string $referenceNumber, PublicEntity $gatewayRefund)
    {
        $npciRefId = (string) $gatewayRefund->getNpciReferenceId();

        if ((empty($npciRefId) === false) and
            ($npciRefId !== $referenceNumber))
        {
            $this->trace->info(TraceCode::RECON_INFO_ALERT, [
                'info_code'                 => Base\InfoCode::DATA_MISMATCH,
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
