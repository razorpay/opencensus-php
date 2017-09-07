<?php

namespace RZP\Reconciliator\Freecharge;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;

class RefundReconciliate extends Base\RefundReconciliate
{
    const COLUMN_REFUND_ID     = 'Transaction Id';
    const COLUMN_PAYMENT_ID    = 'Order Id';
    const COLUMN_SETTLED_AT    = 'Settlement Date';
    const COLUMN_REFUND_AMOUNT = 'Total Transaction Amount';

    const SETTLEMENT_DATE_FORMAT = 'jS F Y';

    // Refund id in MIS file is in the format
    // <merchant_id>_<refund_id>_<some number>. So we need to take the element at
    // index 1 after converting to an array.
    const REFUND_ID_INDEX = 1;

    protected function getRefundId(array $row)
    {
        $columnRefundId = $row[self::COLUMN_REFUND_ID];

        if (empty($columnRefundId) === true)
        {
            return null;
        }

        $columnRefundId = explode('_', $columnRefundId);

        $refundId = $columnRefundId[self::REFUND_ID_INDEX];

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $paymentId = $row[self::COLUMN_PAYMENT_ID];

        $paymentId = Payment\Entity::verifyIdAndSilentlyStripSign($paymentId);

        return $paymentId;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(
                                    self::SETTLEMENT_DATE_FORMAT,
                                    $row[self::COLUMN_SETTLED_AT],
                                    Timezone::IST);

            $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Unable to parse settlement date -> ' . $ex->getMessage(),
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            $this->app['trace']->traceException($ex);
        }

        return $gatewaySettledAt;
    }

    protected function getRefundAmount(array $row)
    {
        $refundAmount = parent::getRefundAmount($row);

        return intval(number_format($refundAmount, 2, '.', ''));
    }

    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        if ($this->refund->getAmount() !== $this->getRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_INFO_ALERT,
                    'message'       => 'Refund amount mismatch',
                    'row'           => $row,
                    'gateway'       => get_called_class()
                ]);

            return false;
        }

        return true;
    }
}
