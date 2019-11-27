<?php

namespace RZP\Reconciliator\EmandateAxis\SubReconciliator;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Reconciliator\Base\SubReconciliator\Helper;
use RZP\Gateway\Netbanking\Axis\Emandate\StatusCode;

class EmandateDebitReconciliate extends Base\SubReconciliator\EmandateDebitReconciliate
{
    const COLUMN_PAYMENT_ID        = 'txn_reference';
    const COLUMN_DEBIT_DATE        = 'execution_date';
    const ORIGINATOR_ID            = 'originator_id';
    const COLUMN_GATEWAY_TOKEN     = 'mandate_refumr';
    const COLUMN_CUSTOMER_NAME     = 'customer_name';
    const COLUMN_DEBIT_ACCOUNT     = 'customer_bank_account';
    const COLUMN_PAYMENT_AMOUNT    = 'paid_in_amount';
    const COLUMN_MIS_INFO3         = 'mis_info3';
    const COLUMN_MIS_INFO4         = 'mis_info4';
    const COLUMN_FILE_REF          = 'file_ref';
    const COLUMN_STATUS            = 'status';
    const COLUMN_REASON            = 'return_reason';
    const COLUMN_RECORD_IDENTIFIER = 'record_identifier';

    const STATUS_SUCCESS  = 'success';
    const STATUS_FAILURE  = 'failure';
    const STATUS_REJECTED = 'rejected';

    const BLACKLISTED_COLUMNS = [
        self::COLUMN_CUSTOMER_NAME,
    ];

    protected $allowedStatuses = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILURE,
        self::STATUS_REJECTED
    ];

    protected $paymentStatusMappings = [
        self::STATUS_SUCCESS  => Payment\Status::AUTHORIZED,
        self::STATUS_FAILURE  => Payment\Status::FAILED,
        self::STATUS_REJECTED => Payment\Status::FAILED,
    ];

    protected function getPaymentId(array $row)
    {
        if (empty($row[self::COLUMN_PAYMENT_ID]) === false)
        {
            return $row[self::COLUMN_PAYMENT_ID];
        }

        return null;
    }

    public function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getGatewayErrorCode(array $row)
    {
        if (empty($row[self::COLUMN_STATUS]) === false)
        {
            return trim($row[self::COLUMN_STATUS]);
        }

        return null;
    }
    protected function getGatewayErrorDescription(array $row)
    {
        if (empty($row[self::COLUMN_REASON]) === false)
        {
            return $row[self::COLUMN_REASON];
        }

        return null;
    }

    protected function getGatewayStatusCode(array $row)
    {
        if (empty($row[self::COLUMN_STATUS]) === false)
        {
            return $row[self::COLUMN_STATUS];
        }

        return null;
    }

    /**
     * @param array $row
     * @return mixed|null
     * @throws Exception\ReconciliationException
     */
    protected function getReconPaymentStatus(array $row)
    {
        $status = strtolower(trim($row[self::COLUMN_STATUS]));

        if (isset($this->paymentStatusMappings[$status]) === true)
        {
            return $this->paymentStatusMappings[$status];
        }

        $this->messenger->raiseReconAlert(
            [
                'trace_code'      => TraceCode::RECON_INFO_ALERT,
                'message'         => 'Invalid payment status sent',
                'payment_id'      => $this->payment->getId(),
                'status'          => $status,
                'gateway'         => $this->gateway
            ]);
    }

    protected function getApiErrorCodeMapped(array $rowDetails)
    {
        return $this->getApiErrorCodeFromDescription($rowDetails);
    }

    protected function getApiErrorCodeFromDescription(array $rowDetails): string
    {
        $errorDescription = $rowDetails[Base\Reconciliate::GATEWAY_ERROR_DESC] ?? null;

        return StatusCode::getEmandateDebitErrorDesc($errorDescription);
    }

    protected function getReconPaymentAmount(array $row)
    {
        return Helper::getIntegerFormattedAmount($row[self::COLUMN_PAYMENT_AMOUNT] ?? null);
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
