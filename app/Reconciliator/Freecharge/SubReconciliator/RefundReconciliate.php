<?php

namespace RZP\Reconciliator\Freecharge;

use Carbon\Carbon;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;


class RefundReconciliate extends Base\RefundReconciliate
{
    const COLUMN_REFUND_ID = 'Transaction Id';
    const COLUMN_SETTLED_AT = 'Transaction Date';
    const SETTLEMENT_DATE_FORMAT = 'd/m/Y H:i:s T';

    const REFUND_ID_INDEX = 1;

    protected function getRefundId(array $row)
    {
        $columnRefundId = $row[self::COLUMN_REFUND_ID];

        $columnRefundId = explode('_', $columnRefundId);

        $refundId = $columnRefundId[self::REFUND_ID_INDEX];

        return $refundId;
    }

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntities = $this->repo->wallet_olamoney->findSuccessfulRefundByRefundId(
                                                                $refundId,
                                                                Wallet::FREECHARGE);

        if ($gatewayEntities->count() === 0)
        {
            return null;
        }

        $paymentId = $gatewayEntities->first()->getPaymentId();

        return $paymentId;
    }

    protected function getGatewaySettledAt($row)
    {
        if ($row[self::COLUMN_SETTLED_AT] === null)
        {
            return null;
        }

        $gatewaySettledAt = null;

        try
        {
            $gatewaySettledAt = Carbon::createFromFormat(
                                    self::SETTLEMENT_DATE_FORMAT,
                                    $row[self::COLUMN_SETTLED_AT],
                                    'Asia/Kolkata');
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
