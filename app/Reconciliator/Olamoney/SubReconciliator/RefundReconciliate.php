<?php

namespace RZP\Reconciliator\Olamoney;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;

class RefundReconciliate extends Base\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID          = 'Unique Bill Id';
    const COLUMN_SETTLED_AT         = 'Date of Settlement';
    const SETTLEMENT_DATE_FORMAT    = 'Y-m-d H:i:s.u';

    protected function getRefundId($row)
    {
        $refundId = $row[self::COLUMN_REFUND_ID];

        return $refundId;
    }

    protected function getGatewaySettledAt($row)
    {
        if (empty($row[self::COLUMN_SETTLED_AT]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::COLUMN_SETTLED_AT]);

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(self::SETTLEMENT_DATE_FORMAT, $columnSettledAt, 'Asia/Kolkata');
            $gatewaySettledAt = $gatewaySettledAt->timestamp;
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
}