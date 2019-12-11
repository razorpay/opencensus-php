<?php

namespace RZP\Reconciliator\Paypal\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Payment\Refund\Status;
use RZP\Gateway\Mozart\WalletPaypal\ReconFields;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const BLACKLISTED_COLUMNS = [];

    const COLUMN_REFUND_ID          = ReconFields::PAY_ID;

    const COLUMN_REFUND_AMOUNT      = ReconFields::AMOUNT;

    protected function getRefundId($row)
    {
        if (isset($row[ReconFields::PAY_ID]) === false)
        {
            return null;
        }

        $refundId = $row[ReconFields::PAY_ID];

        return $refundId;
    }

    protected function validateRefundReconStatus(array $row)
    {
        $refundReconStatus = $this->refund->getStatus();

        if ($refundReconStatus !== Status::PROCESSED)
        {
            $this->trace->info(TraceCode::RECON_INFO, [
                'info_code'         => Base\InfoCode::MIS_FILE_REFUND_FAILED,
                'refund_id'         => $this->refund->getId(),
                'refund_status'     => $this->refund->getStatus(),
                'gateway'           => $this->gateway
            ]);

            return false;
        }

        return true;
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        $expectedCurrency = $this->payment->getCurrency();

        $reconCurrency = $row[ReconFields::CURRENCY] ?? null;

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::CURRENCY_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'payment_id'        => $this->refund->payment->getId(),
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getRefundEntityAmount()
    {
        return $this->refund->getAmount();
    }
}
