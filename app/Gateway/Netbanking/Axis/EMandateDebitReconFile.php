<?php

namespace RZP\Gateway\Netbanking\Axis;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Gateway\Base\Action as GatewayAction;
use RZP\Gateway\Netbanking\Axis\Emandate\StatusCode;
use RZP\Gateway\Netbanking\Base\EMandateDebitReconFile as BaseEMandateDebitReconFile;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingEntity;
use RZP\Models\Payment;

class EMandateDebitReconFile extends BaseEMandateDebitReconFile
{
    // Status codes
    // Keep these values in lowercase to do a case-insensitive check
    const STATUS_SUCCESS  = 'success';
    const STATUS_FAILURE  = 'failure';
    const STATUS_REJECTED = 'rejected';

    // Headings
    const HEADING_PAYMENT_ID        = 'Txn Reference';
    const HEADING_DEBIT_DATE        = 'Execution Date';
    const HEADING_MERCHANT_ID       = 'Originator ID';
    const HEADING_BANK_REF_NUMBER   = 'Mandate Ref/UMR';
    const HEADING_CUSTOMER_NAME     = 'Customer Name';
    const HEADING_DEBIT_ACCOUNT     = 'Customer Bank Account';
    const HEADING_DEBIT_AMOUNT      = 'Paid In Amount';
    const HEADING_STATUS            = 'Status';
    const HEADING_REMARK            = 'Return reason';

    protected $gateway = Payment\Gateway::NETBANKING_AXIS;

    protected $allowedStatuses = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILURE,
        self::STATUS_REJECTED
    ];

    protected function updatePaymentEntities(array $row)
    {
        //
        // Trimming the values, since w're getting these values from excel
        // sheet and might contain spaces at either ends of the values
        //
        $row = array_map(
            function($value) use ($row)
            {
                return trim(trim(str_replace("'", '', $value)));
            }, $row);

        $this->checkValidStatus($row);

        $gatewayPayment = $this->updateGatewayPayment($row);

        $payment = $this->repo->payment->fetchDebitEmandatePaymentPendingAuth(
            $this->gateway,
            $row[self::HEADING_PAYMENT_ID],
            $row[self::HEADING_DEBIT_ACCOUNT]
        );

        $this->updatePayment($gatewayPayment, $payment);
    }

    protected function updateGatewayPayment(array $row): NetbankingEntity
    {
        $paymentId = $row[self::HEADING_PAYMENT_ID];

        $attributes = $this->getGatewayAttributes($row);

        $gatewayPayment = $this->repo->netbanking->findByPaymentIdAndActionOrFail(
            $paymentId,
            GatewayAction::AUTHORIZE
        );

        $gatewayPayment->fill($attributes);

        $this->repo->netbanking->saveOrFail($gatewayPayment);

        return $gatewayPayment;
    }

    protected function checkValidStatus(array $row)
    {
        if (in_array(strtolower($row[self::HEADING_STATUS]), $this->allowedStatuses, true) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                '',
                ['row' => $row]);
        }
    }

    protected function getGatewayAttributes(array $row): array
    {
        $error = null;

        // If the payment fails, set the error message
        if (strtolower($row[self::HEADING_STATUS]) !== self::STATUS_SUCCESS)
        {
            $error = $row[self::HEADING_REMARK] ?? null;
        }

        return [
            NetbankingEntity::STATUS          => $row[self::HEADING_STATUS],
            NetbankingEntity::BANK_PAYMENT_ID => $row[self::HEADING_BANK_REF_NUMBER],
            NetbankingEntity::ACCOUNT_NUMBER  => $row[self::HEADING_DEBIT_ACCOUNT],
            NetbankingEntity::ERROR_MESSAGE   => $error,
            NetbankingEntity::RECEIVED        => 1,
        ];
    }

    protected function isAuthorized(NetbankingEntity $gatewayPayment): bool
    {
        return (strtolower($gatewayPayment->getStatus()) === self::STATUS_SUCCESS);
    }

    protected function getApiErrorCode(string $errorDescription): string
    {
        return StatusCode::getEmandateErrorCodeMap($errorDescription);
    }
}
