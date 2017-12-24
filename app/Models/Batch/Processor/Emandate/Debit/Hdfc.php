<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Exception;
use RZP\Gateway\Netbanking\Base as NetbankingBase;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as Headings;
use RZP\Gateway\Netbanking\Hdfc\ErrorCode;
use RZP\Models\Payment\Gateway;

class Hdfc extends Base
{
    const PROCESS = 'process';
    const REJECT  = 'reject';

    protected $gateway = Gateway::NETBANKING_HDFC;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        $paymentId      = $row[Headings::TRANSACTION_REF_NO];

        $tokenId        = $row[Headings::MANDATE_ID];

        $accountNumber  = $row[Headings::ACCOUNT_NO];

        $errorMessage   = $row[Headings::REJECTION_REMARKS];

        $status         = $row[Headings::STATUS];

        return [
            'payment_id'        => $paymentId,
            'token_id'          => $tokenId,
            'account_number'    => $accountNumber,
            'error_message'     => $errorMessage,
            'status'            => $status,
        ];
    }

    protected function getGatewayAttributes(array $parsedData): array
    {
        $gatewayStatus = strtolower($parsedData['status']);

        if (in_array($gatewayStatus, [self::PROCESS, self::REJECT], true) === false)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_INVALID_STATUS,
                '',
                '',
                ['$parsed_data' => $parsedData]);
        }

        return [
            NetbankingBase\Entity::RECEIVED       => true,
            NetbankingBase\Entity::ERROR_MESSAGE  => $parsedData['error_message'],
            NetbankingBase\Entity::STATUS         => $gatewayStatus,
        ];
    }

    protected function isAuthorized(NetbankingBase\Entity $gatewayPayment): bool
    {
        return ($gatewayPayment->getStatus() === self::PROCESS);
    }

    protected function getApiErrorCode(string $errorDescription): string
    {
        return ErrorCode::getApiErrorCode($errorDescription);
    }
}