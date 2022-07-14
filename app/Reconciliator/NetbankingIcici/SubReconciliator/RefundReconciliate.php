<?php

namespace RZP\Reconciliator\NetbankingIcici\SubReconciliator;

use Carbon\Carbon;
use Monolog\Logger;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base\SubReconciliator;

class RefundReconciliate extends SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_ID     = 'PRN';
    const COLUMN_REFUND_AMOUNT = 'Reversal Amount';
    const COLUMN_REVERSAL_DATE = 'Reversal Date';
    const REFUND_REF_NO        = 'ReversalId';

    const BLACKLISTED_COLUMNS = [];

    protected function getRefundId(array $row)
    {
        return $row[self::COLUMN_REFUND_ID] ?? null;
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
            $gatewaySettledAt = Carbon::createFromFormat('Ymd', $columnSettledAt, Timezone::IST);
            $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::ERROR,
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

    protected function getArn(array $row)
    {
        return $row[self::REFUND_REF_NO] ?? null;
    }
}
