<?php

namespace RZP\Reconciliator\UpiHdfc\SubReconciliator;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Action;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_ID              = 'new_refund_order_id';
    const COLUMN_TRANSACTION_AMOUNT     = 'transaction_amount';
    const COLUMN_TRANSACTION_DATE       = 'transaction_date';
    const COLUMN_CUSTOMER_REF_NO        = 'customer_ref_no';
    const COLUMN_TRANSACTION_TYPE       = 'drcr';
    const COLUMN_TRANSACTION_STATUS     = 'transaction_status';
    const COLUMN_TRANSACTION_REMARKS    = 'transaction_remarks';

    // Blacklisted columns
    const COLUMN_PAYER_VPA              = 'payer_virtual_address';
    const COLUMN_PAYEE_VPA              = 'payee_virtual_address';
    const COLUMN_DEVICE_TYPE            = 'device_type';
    const COLUMN_APP                    = 'app';
    const COLUMN_DEVICE_OS              = 'device_os';
    const COLUMN_DEVICE_MOBILE_NO       = 'device_mobile_no';
    const COLUMN_DEVICE_LOCATION        = 'device_location';
    const COLUMN_IP_ADDRESS             = 'ip_address';

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_PAYEE_VPA,
        self::COLUMN_PAYER_VPA,
        self::COLUMN_DEVICE_TYPE,
        self::COLUMN_APP,
        self::COLUMN_DEVICE_OS,
        self::COLUMN_DEVICE_MOBILE_NO,
        self::COLUMN_DEVICE_LOCATION,
        self::COLUMN_IP_ADDRESS,
    ];

    const SUCCESS   = 'SUCCESS';
    const DEBIT     = 'Debit';
    const APPROVED  = 'Approved';

    protected function getRefundId(array $row)
    {
        $refundId = null;

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
            $refundId = substr($row[self::COLUMN_REFUND_ID], 0, 14);
        }

        return $refundId;
    }

    protected function checkIfUnexpectedRefundId($row)
    {
        if ((empty($row[self::COLUMN_TRANSACTION_REMARKS]) === false) and
            (empty($row[self::COLUMN_REFUND_ID]) === false) and
            (strpos($row[self::COLUMN_TRANSACTION_REMARKS], self::APPROVED) === 0) and
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

            $this->setFailUnprocessedRow(false);

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

        $gatewaySettledAtTimestamp = null;

        try
        {
            $gatewaySettledAtTimestamp = Carbon::parse($refundSettledAt)->setTimezone(Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::INFO,
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'message'   => 'Unable to parse settlement date -> ' . $ex->getMessage(),
                    'date'      => $refundSettledAt,
                    'gateway'   => $this->gateway,
                ]);
        }

        return $gatewaySettledAtTimestamp;
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
