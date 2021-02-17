<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Netbanking;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Netbanking\Axis\Emandate\StatusCode;
use RZP\Gateway\Netbanking\Base\Entity as NetbankingBaseEntity;
use RZP\Gateway\Netbanking\Axis\EMandateDebitReconFileHeadings as Headings;

class Axis extends Base
{
    protected $gateway = Gateway::NETBANKING_AXIS;

    const STATUS_SUCCESS  = 'success';
    const STATUS_FAILURE  = 'failure';
    const STATUS_REJECTED = 'rejected';

    protected $allowedStatuses = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILURE,
        self::STATUS_REJECTED
    ];

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        $this->checkValidStatus($row[Headings::HEADING_STATUS]);

        return [
            self::PAYMENT_ID            => $row[Headings::HEADING_PAYMENT_ID],
            self::ACCOUNT_NUMBER        => $row[Headings::HEADING_DEBIT_ACCOUNT],
            self::GATEWAY_ERROR_MESSAGE => $row[Headings::HEADING_REMARK],
            self::GATEWAY_RESPONSE_CODE => $row[Headings::HEADING_STATUS],
            self::AMOUNT                => $row[Headings::HEADING_DEBIT_AMOUNT],
            self::GATEWAY_PAYMENT_ID    => $row[Headings::HEADING_BANK_REF_NUMBER]
        ];
    }

    protected function getGatewayAttributes(array $row): array
    {
        $error = null;

        // If the payment fails, set the error message
        if ($this->isAuthorized($row) === false)
        {
            $error = $row[self::GATEWAY_ERROR_MESSAGE] ?? null;
        }

        return [
            NetbankingBaseEntity::STATUS          => $row[self::GATEWAY_RESPONSE_CODE],
            NetbankingBaseEntity::BANK_PAYMENT_ID => $row[self::GATEWAY_PAYMENT_ID],
            NetbankingBaseEntity::ACCOUNT_NUMBER  => $row[self::ACCOUNT_NUMBER],
            NetbankingBaseEntity::ERROR_MESSAGE   => $error,
            NetbankingBaseEntity::RECEIVED        => 1,
        ];
    }

    protected function checkValidStatus($status)
    {
        if (in_array(strtolower($status), $this->allowedStatuses, true) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                $status,
                'Gateway response status is invalid'
            );
        }
    }

    protected function isAuthorized(array $content): bool
    {
        return (strtolower($content[self::GATEWAY_RESPONSE_CODE]) === self::STATUS_SUCCESS);
    }

    protected function getApiErrorCode(array $content): string
    {
        $errorDescription = $content[self::GATEWAY_ERROR_MESSAGE];

        return StatusCode::getEmandateDebitErrorDesc($errorDescription);
    }

    protected function removeCriticalDataFromTracePayload(array & $payloadEntry)
    {
        unset($payloadEntry[Headings::HEADING_DEBIT_ACCOUNT]);
    }
}
