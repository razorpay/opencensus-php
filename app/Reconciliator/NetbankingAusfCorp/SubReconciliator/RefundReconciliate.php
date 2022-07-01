<?php


use Carbon\Carbon;
use Razorpay\Trace\Logger;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Reconciliator\NetbankingAusfCorp\Constants;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate{

    protected function getRefundId(array $row)
    {
        return $row[Constants::PAYMENT_ID_EXT] ?? null;
    }

    protected function getReconRefundAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[Constants::REFUND_AMOUNT]);
    }

    protected function getGatewaySettledAt(array $row)
    {
        if (empty($row[self::PAYMENT_DATE]) === true)
        {
            return null;
        }

        $columnSettledAt = strtolower($row[self::PAYMENT_DATE]);

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat('dd-mm-yy', $columnSettledAt, Timezone::IST);
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
