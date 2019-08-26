<?php

namespace RZP\Models\Batch\Processor\Emandate\Debit;

use RZP\Models\Batch;
use RZP\Gateway\Netbanking;
use RZP\Models\Payment\Gateway;

class Sbi extends Base
{
    protected $gateway = Gateway::NETBANKING_SBI;

    protected function getDataFromRow(array & $row): array
    {
        $row = array_map('trim', $row);

        return [
            self::PAYMENT_ID            => $row[ Batch\Header::SBI_EM_DEBIT_CUSTOMER_REF_NO ],
            self::ACCOUNT_NUMBER        => $row[ Batch\Header::SBI_EM_DEBIT_DEBIT_ACCOUNT_NUMBER],
            self::GATEWAY_ERROR_MESSAGE => $row[ Batch\Header::SBI_EM_DEBIT_REASON],
            self::GATEWAY_RESPONSE_CODE => $row[ Batch\Header::SBI_EM_DEBIT_DEBIT_STATUS],
            self::AMOUNT                => $row[ Batch\Header::SBI_EM_DEBIT_AMOUNT],
        ];
    }

    protected function getGatewayAttributes(array $content): array
    {
        return [
            Netbanking\Base\Entity::RECEIVED       => true,
            Netbanking\Base\Entity::ERROR_MESSAGE  => $content[self::GATEWAY_ERROR_MESSAGE],
            Netbanking\Base\Entity::STATUS         => $content[self::GATEWAY_RESPONSE_CODE],
        ];
    }

    protected function isAuthorized(array $content): bool
    {
        return Netbanking\Sbi\Emandate\Status::isDebitSuccess($content[self::GATEWAY_RESPONSE_CODE]);
    }

    protected function getApiErrorCode(array $content): string
    {
        $errorDescription = $content[self::GATEWAY_ERROR_MESSAGE];

        return Netbanking\Sbi\Emandate\ErrorCode::getDebitErrorCode($errorDescription);
    }

    protected function getNumRowsToSkipExcelFile()
    {
        return 5;
    }
}
