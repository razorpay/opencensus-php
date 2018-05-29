<?php

namespace RZP\Reconciliator\Hitachi;

use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;

class RefundReconciliate extends Base\RefundReconciliate
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

    protected function getPaymentId(array $row)
    {
        $refundId = $this->getRefundId($row);

        $gatewayEntity = $this->getGatewayRefund($refundId);

        if ($gatewayEntity === null)
        {
            return null;
        }

        $paymentId = $gatewayEntity->getPaymentId();

        return $paymentId;
    }

    protected function getGatewayRefund(string $refundId)
    {
        $gatewayEntities = $this->repo->hitachi->findSuccessfulRefundByRefundId($refundId);

        if ($gatewayEntities->count() === 0)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'    => TraceCode::RECON_MISMATCH,
                    'message'       => 'Gateway refund not found.',
                    'refund_id'     => $refundId,
                    'gateway'       => $this->gateway,
               ]);

            return null;
        }

        $refundEntity = $gatewayEntities->first();

        return $refundEntity;
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

        $refundAmount = Base\Helper::getIntegerFormattedAmount($row[self::COLUMN_REFUND_AMOUNT]);

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

        $refundAmount = ($convertCurrency === true) ? $this->refund->getBaseAmount() : $this->refund->getAmount();

        if ($refundAmount !== $this->getReconRefundAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => 'Refund amount mismatch',
                    'expected_amount'   => $refundAmount,
                    'currency'          => $this->refund->getCurrency(),
                    'row'               => $row,
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

    protected function validateRefundCurrencyCodeEqualsReconCurrencyCode(array $row)
    {
        $expectedCurrency = Currency::getIsoCode($this->refund->getCurrency());

        $reconCurrency = $this->getReconCurrencyCode($row);

        if ($expectedCurrency !== $reconCurrency)
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'        => TraceCode::RECON_INFO_ALERT,
                    'message'           => 'Refund currency mismatch',
                    'expected_currency' => $expectedCurrency,
                    'recon_currency'    => $reconCurrency,
                    'row'               => $row,
                    'gateway'           => $this->gateway
                ]);

            return false;
        }

        return true;
    }
}
