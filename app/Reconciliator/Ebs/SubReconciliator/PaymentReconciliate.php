<?php

namespace RZP\Reconciliator\Ebs\SubReconciliator;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Base;
use RZP\Trace\TraceCode;
use RZP\Gateway\Base\Action;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\SubReconciliator\Helper;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_KK_CESS            = 'krishi_kalyan_cess';
    const COLUMN_SB_CESS            = 'swachh_bharat_cess';
    const COLUMN_SERVICE_TAX        = 'service_tax';

    const COLUMN_SETTLED_AT         = ['settlement_date'];
    const COLUMN_PAYMENT_DATE       = ['txn_date'];
    const COLUMN_GATEWAY_PAYMENT_ID = ['paymentid'];
    const COLUMN_FEE                = ['tdr', 'tdr_amt'];
    const COLUMN_PAYMENT_AMOUNT     = ['captured', 'credit'];
    const COLUMN_PAYMENT_ID         = ['merchant_ref_no', 'merchant_refno'];

    /**
     * Gets payment_id from row data
     *
     * @param array $row
     * @return string|null
     */
    protected function getPaymentId(array $row)
    {
        return Helper::getArrayFirstValue($row, self::COLUMN_PAYMENT_ID);
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo->ebs->findBypaymentIdAndActionOrFail($paymentId, Action::AUTHORIZE);
    }

    /**
     * Gets amount captured.
     *
     * Since we get two type of sheets,
     * both have different column headers for payment amount.
     * We need to check which one of them is set
     * and get the payment amount accordingly.
     *
     * @param $row array
     *
     * @return int $paymentAmount integer
     */
    protected function getReconPaymentAmount(array $row)
    {
        $paymentAmount = Helper::getArrayFirstValue($row, self::COLUMN_PAYMENT_AMOUNT);

        $paymentAmount = Helper::getIntegerFormattedAmount(abs($paymentAmount));

        return $paymentAmount;
    }

    /**
     * Gets service tax levied by EBS
     *
     * Some files have KKC & SBC tax given separately
     * If they are present separately,
     * it means it's not added to the service tax.
     *
     * @param $row array
     * @return $serviceTax float
     */
    protected function getGatewayServiceTax($row)
    {
        // Convert service tax into paise
        $serviceTax = $row[self::COLUMN_SERVICE_TAX];

        // Check for SB & KK Cess
        if (isset($row[self::COLUMN_SB_CESS]) === true)
        {
            $sbCess = $row[self::COLUMN_SB_CESS];

            $serviceTax += $sbCess;
        }

        if (isset($row[self::COLUMN_KK_CESS]) === true)
        {
            $kkCess = $row[self::COLUMN_KK_CESS];

            $serviceTax += $kkCess;
        }

        return Helper::getIntegerFormattedAmount(abs($serviceTax));
    }

    /**
     * Gets TDR
     *
     * @param $row array
     * @return $fee float
     */
    protected function getGatewayFee($row)
    {
        $fee = Helper::getArrayFirstValue($row, self::COLUMN_FEE);

        // Convert fee into basic unit of currency (ex: paise)
        $fee = Helper::getIntegerFormattedAmount($fee);

        // Already in basic unit of currency. Hence, no conversion needed
        $serviceTax = $this->getGatewayServiceTax($row);

        $fee += $serviceTax;

        return $fee;
    }

    /**
     * Checks if payment amount is equal to amount from row
     * raises alert in case of mismatch
     *
     * @param array $row
     * @return bool
     */
    protected function validatePaymentAmountEqualsReconAmount(array $row)
    {
        if ($this->payment->getBaseAmount() !== $this->getReconPaymentAmount($row))
        {
            $this->messenger->raiseReconAlert(
                [
                    'trace_code'      => TraceCode::RECON_INFO_ALERT,
                    'info_code'       => Base\InfoCode::AMOUNT_MISMATCH,
                    'payment_id'      => $this->payment->getId(),
                    'expected_amount' => $this->payment->getBaseAmount(),
                    'recon_amount'    => $this->getReconPaymentAmount($row),
                    'currency'        => $this->payment->getCurrency(),
                    'gateway'         => $this->gateway
                ]);

            return false;
        }

        return true;
    }

    protected function getGatewaySettledAt(array $row)
    {
        $settledAt = Helper::getArrayFirstValue($row, self::COLUMN_SETTLED_AT);

        //
        // settledAt date comes in two formats : "16/04/2019" or "16/04/19"
        // Need to get the timestamp accordingly
        //

        $explodedArray = explode('/', $settledAt);
        $year = end($explodedArray);

        $format = 'd/m/y';

        if (strlen($year) > 2)
        {
            // if year is in 4 digit format
            $format = 'd/m/Y';
        }

        $settledAtTimestamp = null;

        try
        {
            $settledAtTimestamp = Carbon::createFromFormat($format, $settledAt, Timezone::IST)->getTimestamp();
        }
        catch (\Exception $ex)
        {

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => Base\InfoCode::INCORRECT_DATE_FORMAT,
                    'settled_at'        => $settledAt,
                    'expected_format'   => $format,
                    'payment_id'        => $this->payment->getId(),
                    'gateway'           => $this->gateway
                ]
            );
        }

        return $settledAtTimestamp;
    }

    protected function getGatewayPaymentId(array $row)
    {
        $gatewayPaymentId = Helper::getArrayFirstValue($row, self::COLUMN_GATEWAY_PAYMENT_ID);

        return $gatewayPaymentId;
    }
}
