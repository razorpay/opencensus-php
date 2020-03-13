<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Batch\Header;
use RZP\Models\BankTransfer\Entity;

class BankTransfer
{
    public static function getBankTransferInsertInput(array $entry): array
    {
        return [
            Entity::PAYER_NAME    => $entry[Header::PAYER_NAME],
            Entity::PAYER_ACCOUNT => $entry[Header::PAYER_ACCOUNT],
            Entity::PAYER_IFSC    => $entry[Header::PAYER_IFSC],
            Entity::PAYEE_ACCOUNT => $entry[Header::PAYEE_ACCOUNT],
            Entity::PAYEE_IFSC    => $entry[Header::PAYEE_IFSC],
            Entity::MODE          => $entry[Header::MODE],
            Entity::REQ_UTR       => $entry[Header::UTR],
            Entity::TIME          => $entry[Header::TIME],
            Entity::AMOUNT        => $entry[Header::AMOUNT],
            Entity::DESCRIPTION   => $entry[Header::DESCRIPTION],
            Entity::NARRATION     => $entry[Header::NARRATION],
        ];
    }
}
