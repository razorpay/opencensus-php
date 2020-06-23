<?php

namespace RZP\Reconciliator\Jiomoney\SubReconciliator;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Status;
use RZP\Models\Base\PublicEntity;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Spine\Exception\DbQueryException;

class PaymentReconciliate extends Base\SubReconciliator\PaymentReconciliate
{
    /*******************
     * Row Header Names
     *******************/
    const COLUMN_PAYMENT_ID             = 'merchant_ref_id';
    const COLUMN_PAYMENT_AMOUNT         = 'gross_amount';
    const COLUMN_GATEWAY_PAYMENT_ID     = 'transaction_id';
    const COLUMN_PAYMENT_STATUS         = 'payment_status';

    const COMMISSION                    = 'tran_com';
    const IGST                          = 'igst';
    const UGST                          = 'ugst';
    const CGST                          = 'cgst';
    const SGST                          = 'sgst';

    const GATEWAY_PAYMENT_DATE_FORMAT   = 'm/d/Y H:i:s';

    const PROCESSED                     = 'Processed';
    const SETTLED                       = 'Settled';

    protected function getPaymentId(array $row)
    {
        return ltrim($row[self::COLUMN_PAYMENT_ID] ?? null);
    }

    protected function getReconPaymentAmount(array $row)
    {
        $paymentAmount = floatval($row[self::COLUMN_PAYMENT_AMOUNT]) * 100;

        return intval(number_format($paymentAmount, 2, '.', ''));
    }

    protected function getGatewayServiceTax($row)
    {
        $gatewayServiceTax = 0;

        $gatewayServiceTax += Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::IGST]);
        $gatewayServiceTax += Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::UGST]);
        $gatewayServiceTax += Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::CGST]);
        $gatewayServiceTax += Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::SGST]);

        return $gatewayServiceTax;
    }

    protected function getGatewayFee($row)
    {
        $gatewayFee = 0;

        $gatewayFee += Base\SubReconciliator\Helper::getIntegerFormattedAmount($row[self::COMMISSION]);
        $gatewayFee += $this->getGatewayServiceTax($row);

        return $gatewayFee;
    }

    protected function getGatewayTransactionId(array $row)
    {
        return $row[self::COLUMN_GATEWAY_PAYMENT_ID] ?? null;
    }

    protected function getReconPaymentStatus(array $row)
    {
        $status = $row[self::COLUMN_PAYMENT_STATUS];

        $acceptedStatuses = [
            self::PROCESSED,
            self::SETTLED,
        ];

        if (in_array($status, $acceptedStatuses) === true)
        {
            return Status::AUTHORIZED;
        }

        else
        {
            return Status::FAILED;
        }
    }

    public function getGatewayPayment($paymentId)
    {
        try
        {
            return $this->repo
                        ->wallet_jiomoney
                        ->findSuccessfulPaymentsByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
        }
        catch (DbQueryException $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'     => Base\InfoCode::GATEWAY_PAYMENT_ABSENT,
                    'payment_id'    => $paymentId,
                    'gateway'       => $this->gateway,
                ]);

            return null;
        }
    }

    protected function setGatewayTransactionId(string $gatewayPaymentId, PublicEntity $gatewayPayment)
    {
        $dbGatewayTransactionId = trim($gatewayPayment->getGatewayPaymentId());

        if ((empty($dbGatewayTransactionId) === false) and
            ($dbGatewayTransactionId !== $gatewayPaymentId))
        {
            $infoCode = ($this->reconciled === true) ? Base\InfoCode::DUPLICATE_ROW : Base\InfoCode::DATA_MISMATCH;

            $this->messenger->raiseReconAlert(
                [
                    'trace_code'                => TraceCode::RECON_MISMATCH,
                    'info_code'                 => $infoCode,
                    'message'                   => 'Reference number in db is not same as in recon',
                    'payment_id'                => $this->payment->getId(),
                    'amount'                    => $this->payment->getBaseAmount(),
                    'db_reference_number'       => $dbGatewayTransactionId,
                    'recon_reference_number'    => $gatewayPaymentId,
                    'gateway'                   => $this->gateway
                ]);

            return;
        }

        $gatewayPayment->setGatewayPaymentId($gatewayPaymentId);
    }

    protected function setAllowForceAuthorization(Payment\Entity $payment)
    {
        $this->allowForceAuthorization = true;
    }

    protected function getInputForForceAuthorize($row)
    {
        return [
            'gateway_payment_id'    => $row[self::COLUMN_GATEWAY_PAYMENT_ID],
        ];
    }

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
}
