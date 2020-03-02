<?php

namespace RZP\Reconciliator\YesBank\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\YesBank;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use Razorpay\Spine\Exception\DbQueryException;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    const COLUMN_REFUND_ID                   = 'mtx_id';
    const COLUMN_ARN                         = 'arn';
    const COLUMN_SETTLEMENT_DATE             = 'transaction_date';
    const COLUMN_SETTLEMENT_TIME             = 'transaction_time';
    const COLUMN_REFUND_AMOUNT               = 'orig_txn_amount';

    const POSSIBLE_DATE_FORMATS            = ['d-M-Y', 'd-M-Y H:i:s'];

    protected function getRefundId($row)
    {
        if (isset($row[self::COLUMN_REFUND_ID]) === true)
        {
            return trim($row[self::COLUMN_REFUND_ID]);
        }

        return null;
    }

    protected function getGatewayAmount(array $row)
    {
        if (isset($row[self::COLUMN_REFUND_AMOUNT]) === true)
        {
            return Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT]);
        }

        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'           => 'Unable to get the gateway amount. This is unexpected.',
                'info_code'         => 'GATEWAY_AMOUNT_ABSENT',
                'row'               => $row,
                'gateway'           => $this->gateway
            ]);
    }

    protected function getArn($row)
    {
        if (isset($row[self::COLUMN_ARN]) === true)
        {
            return trim($row[self::COLUMN_ARN]);
        }

        return null;
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::COLUMN_SETTLEMENT_DATE]) === true)
        {
            return null;
        }

        $columnSettledAtDate = strtolower($row[self::COLUMN_SETTLEMENT_DATE]);

        $columnSettledAt = $columnSettledAtDate;

        if (isset($row[self::COLUMN_SETTLEMENT_TIME]) === true)
        {
            $columnSettledAtTime = strtolower($row[self::COLUMN_SETTLEMENT_TIME]);

            $columnSettledAt = $columnSettledAtDate . ' ' . $columnSettledAtTime;
        }

        $gatewaySettledAt = null;

        foreach (self::POSSIBLE_DATE_FORMATS as $possibleDateFormat)
        {
            try
            {
                $gatewaySettledAt = Carbon::createFromFormat($possibleDateFormat, $columnSettledAt, Timezone::IST);
                $gatewaySettledAt = $gatewaySettledAt->getTimestamp();
            }
            catch (\Exception $ex)
            {
                continue;
            }
        }

        return $gatewaySettledAt;
    }
}
