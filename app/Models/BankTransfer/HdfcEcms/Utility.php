<?php

namespace RZP\Models\BankTransfer\HdfcEcms;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\BankTransfer\Mode;
use RZP\Models\VirtualAccount\Provider;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\BankTransfer\Entity as BankTransferEntity;

class Utility
{
    public function modifyInputDataToEntity($input)
    {
        $time = Carbon::createFromFormat('d-F-Y', $input[Entity::TRANSACTION_DATE], Timezone::IST)->getTimestamp();

        return [
            BankTransferEntity::PAYEE_ACCOUNT => $input[Entity::VIRTUAL_ACCOUNT_NO] ?? '',
            BankTransferEntity::PAYEE_IFSC    => Provider::IFSC[Provider::HDFC_ECMS],
            BankTransferEntity::PAYER_NAME    => $input[Entity::REMITTER_NAME] ?? '',
            BankTransferEntity::PAYER_ACCOUNT => $input[Entity::REMITTER_ACCOUNT_NO] ?? '',
            BankTransferEntity::PAYER_IFSC    => $input[Entity::REMITTER_IFSC] ?? '',
            BankTransferEntity::MODE          => strtolower($input[Entity::TYPE] ?? ''),
            BankTransferEntity::REQ_UTR       => $input[Entity::UNIQUE_ID],
            BankTransferEntity::TIME          => $time,
            BankTransferEntity::AMOUNT        => number_format($input[Entity::AMOUNT], 2, '.', ''),
            BankTransferEntity::DESCRIPTION   => $input[Entity::TRANSACTION_DESCRIPTION] ?? null,
            BankTransferEntity::NARRATION     => $input[Entity::UNIQUE_ID],
            BankTransferEntity::PAYEE_NAME    => $input[Entity::BENE_NAME] ?? '',
        ];
    }

    public function getHdfcEcmsResponse(array $input, $failureMessage, $statusCode = 1, array $response = null)
    {
        if ($response === null)
        {
            $response[Entity::RESPONSE_STATUS] = $statusCode;

            $response[Entity::RESPONSE_REASON] = $failureMessage;

            $response[Entity::TRANSACTION_ID] = $input[Entity::UNIQUE_ID];
        }

        return $response;
    }
}
