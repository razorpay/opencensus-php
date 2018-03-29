<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Netbanking;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Netbanking\Base as NetbankingBase;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;

class Hdfc extends Base
{
    const PROCESS = 'process';
    const REJECT  = 'reject';

    protected $gateway = Gateway::NETBANKING_HDFC;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        return [
            'payment_id'        => $row[Headings::TRANSACTION_REF_NO],
            'token_id'          => $row[Headings::MANDATE_ID],
            'account_number'    => $row[Headings::ACCOUNT_NO],
            'error_message'     => $row[Headings::REJECTION_REMARKS],
            'status'            => $row[Headings::STATUS],
        ];
    }

    protected function getGatewayAttributes(array $content): array
    {
        $gatewayStatus = strtolower($content['status']);

        if (in_array($gatewayStatus, [self::PROCESS, self::REJECT], true) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_STATUS,
                '',
                '',
                $content);
        }

        return [
            NetbankingBase\Entity::RECEIVED       => true,
            NetbankingBase\Entity::ERROR_MESSAGE  => $content['error_message'],
            NetbankingBase\Entity::STATUS         => $gatewayStatus,
        ];
    }

    protected function isAuthorized(array $content): bool
    {
        return (strtolower($content['status']) === self::PROCESS);
    }

    protected function getErrorDescription(array $content)
    {
        return $content['error_message'];
    }

    protected function getApiErrorCode(string $errorDescription): string
    {
        return Netbanking\Hdfc\ErrorCode::getApiErrorCode($errorDescription);
    }
}
