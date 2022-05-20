<?php

namespace RZP\Reconciliator\Kotak\SubReconciliator;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base\SubReconciliator;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_ID = 'REFUND MERCHANT REF NO';
    const COLUMN_REFUND_AMOUNT = 'AMOUNT';
    const COLUMN_REVERSAL_DATE = 'AUTHORIZED DATE';
    const REFUND_REF_NO = 'BANK REF NO';

    const BLACKLISTED_COLUMNS = [];

    protected function getRefundId(array $row)
    {
        return $row[self::COLUMN_REFUND_ID] ?? null;
    }

    protected function getReconRefundAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT]);
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_REVERSAL_DATE]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_REVERSAL_DATE]);

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat('d-M-y', $columnSettledAt, Timezone::IST);
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
