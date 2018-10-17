<?php

namespace RZP\Reconciliator\EmandateAxis\SubReconciliator;

use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Reconciliator\Base;
use RZP\Gateway\Base\Action;
use RZP\Models\Base\PublicEntity;
use RZP\Gateway\Netbanking\Axis\Emandate\StatusCode;

class EmandateDebitReconciliate extends Base\SubReconciliator\EmandateDebitReconciliate
{
    const COLUMN_PAYMENT_ID        = 'txn_reference';
    const COLUMN_DEBIT_DATE        = 'execution_date';
    const ORIGINATOR_ID            = 'originator_id';
    const COLUMN_GATEWAY_TOKEN     = 'mandate_refumr';
    const COLUMN_CUSTOMER_NAME     = 'customer_name';
    const COLUMN_DEBIT_ACCOUNT     = 'customer_bank_account';
    const COLUMN_AMOUNT            = 'paid_in_amount';
    const COLUMN_MIS_INFO3         = 'mis_info3';
    const COLUMN_MIS_INFO4         = 'mis_info4';
    const COLUMN_FILE_REF          = 'file_ref';
    const COLUMN_STATUS            = 'status';
    const COLUMN_REASON            = 'return_reason';
    const COLUMN_RECORD_IDENTIFIER = 'record_identifier';

    const STATUS_SUCCESS  = 'Success';
    const STATUS_FAILURE  = 'Failure';
    const STATUS_REJECTED = 'Rejected';

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

    protected function getGatewayPayment($paymentId)
    {
        return $this->repo
                    ->netbanking
                    ->findByPaymentIdAndAction($paymentId, Action::AUTHORIZE);
    }

    protected function getGatewayToken(array $row)
    {
        if (empty($row[self::COLUMN_GATEWAY_TOKEN]) === false)
        {
            return trim($row[self::COLUMN_GATEWAY_TOKEN]);
        }

        return null;
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
        if (isset($this->paymentStatusMappings[$row[self::COLUMN_STATUS]]) === true)
        {
            return $this->paymentStatusMappings[$row[self::COLUMN_STATUS]];
        }

        throw new Exception\ReconciliationException(
            "Invalid payment status sent",
            [
                'row'     => $row,
                'gateway' => 'axis_emandate'
            ]
        );
    }

    protected function getApiErrorCodeMapped(array $rowDetails)
    {
        $gatewayErrorCode = $rowDetails[Base\Reconciliate::GATEWAY_ERROR_CODE];

        $this->checkValidStatus($gatewayErrorCode);

        return $this->getApiErrorCodeFromDescription($rowDetails);
    }

    /**
     * @param $status
     * @throws Exception\GatewayErrorException
     */
    protected function checkValidStatus($status)
    {
        if (in_array($status, $this->allowedStatuses, true) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                $status,
                'Gateway response status is invalid'
            );
        }
    }

    protected function getApiErrorCodeFromDescription(array $rowDetails): string
    {
        $errorDescription = $rowDetails[Base\Reconciliate::GATEWAY_ERROR_DESC];

        return StatusCode::getEmandateDebitErrorDesc($errorDescription);
    }
}
