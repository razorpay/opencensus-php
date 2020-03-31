<?php

namespace RZP\Reconciliator\Hitachi\SubReconciliator;

use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Models\Currency\Currency;

class RefundReconciliate extends Base\SubReconciliator\RefundReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_REFUND_ID              = 'invoice_number';
    const COLUMN_ARN                    = 'arn';
    const COLUMN_AUTH_CODE              = 'auth_id';
    const COLUMN_FEE                    = 'fee_amount';
    const COLUMN_REFUND_AMOUNT          = 'amount';
    const COLUMN_ISSETTLED              = 'issettled';
    const COLUMN_DATETIME               = 'datetime';
    const COLUMN_CURRENCY_CODE          = 'tran_currency_code';

    const REFUND_RECON_SKIP_TIMESTAMP   = '2018-03-05 23:48:09';
    const DEFAULT_CURRENCY_CODE         = '356';

    protected function getRefundId(array $row)
    {
        /**
         * In MIS files, for the refunds before 2018-03-05 23:48:09,
         * we do not have refund id in invoice_number.
         * Such rows will be skipped.
         */

        if ($row[self::COLUMN_DATETIME] < self::REFUND_RECON_SKIP_TIMESTAMP)
        {
            return null;
        }

        $refundId = null;

        // Unsettled rows should be skipped while processing.
        if ($row[self::COLUMN_ISSETTLED] !== 'S')
        {
            $this->trace->error(
                TraceCode::RECON_ALERT,
                [
                    'info_code' => 'UNSETTLED_ROW_FOUND',
                    'message'   => 'Unsettled row found. Skipping',
                    'row'       => $row,
                    'gateway'   => $this->gateway
                ]);

            $this->setFailUnprocessedRow(false);
        }
        else
        {
            $refundId = $row[self::COLUMN_REFUND_ID];
        }

        return $refundId;
    }

    protected function getArn(array $row)
    {
        if (empty($row[self::COLUMN_ARN]) === true)
        {
            return null;
        }

        return $row[self::COLUMN_ARN];
    }

    protected function getReconRefundAmount(array $row)
    {
        if (isset($row[static::COLUMN_REFUND_AMOUNT]) === false)
        {
            return null;
        }

        $refundAmount = Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT]);

        return abs($refundAmount);
    }

    /**
     * Checks if refund amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validateRefundAmountEqualsReconAmount(array $row)
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $refundAmount = ($convertCurrency === true) ? $this->refund->getBaseAmount() : $this->refund->getGatewayAmount();

        if ($refundAmount !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'info_code'         => Base\InfoCode::AMOUNT_MISMATCH,
                    'refund_id'         => $this->refund->getId(),
                    'expected_amount'   => $refundAmount,
                    'recon_amount'      => $this->getReconRefundAmount($row),
                    'currency'          => $this->refund->getCurrency(),
                    'gateway'           => $this->gateway
                ]);

            return false;
        }
        return true;
    }

    protected function getReconCurrencyCode($row)
    {
        if (empty($row[self::COLUMN_CURRENCY_CODE]) === true)
        {
            $this->reportMissingColumn($row, self::COLUMN_CURRENCY_CODE);

            return null;
        }

        return $row[self::COLUMN_CURRENCY_CODE];
    }

    protected function validateRefundCurrencyEqualsReconCurrency(array $row) : bool
    {
        $convertCurrency = $this->payment->getConvertCurrency();

        $expectedCurrency = ($convertCurrency === true) ? self::DEFAULT_CURRENCY_CODE : Currency::getIsoCode($this->payment->getGatewayCurrency());

        $reconCurrency = $this->getReconCurrencyCode($row);

        if (($expectedCurrency !== $reconCurrency) and ( empty($reconCurrency) !== true))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => Base\InfoCode::CURRENCY_MISMATCH,
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
}
