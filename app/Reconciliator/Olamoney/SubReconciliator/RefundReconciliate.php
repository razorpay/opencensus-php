<?php

namespace RZP\Reconciliator\Olamoney\SubReconciliator;

use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'unique_bill_id';
    const COLUMN_SETTLED_AT         = 'date_of_settlement';
    const SETTLEMENT_DATE_FORMAT    = 'Y-m-d H:i:s.u';
    const COLUMN_REFUND_AMOUNT      = 'bill_amount_in_rs';

    protected function getRefundId(array $row)
    {
        $refundId = substr($row[self::COLUMN_REFUND_ID], 0, 14);

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntities = $this->repo->wallet_olamoney->findSuccessfulRefundByRefundId(
                                                                $refundId,
                                                                Payment\Processor\Wallet::OLAMONEY);

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $paymentId = $gatewayEntities->first()->getPaymentId();

        return $paymentId;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(self::SETTLEMENT_DATE_FORMAT, $columnSettledAt, Timezone::IST);
            $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::INFO,
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code' => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'message'   => 'Unable to parse settlement date -> ' . $ex->getMessage(),
                    'date'      => $columnSettledAt,
                    'gateway'   => $this->gateway,
                ]);
        }

        return $gatewaySettledAt;
    }
}
