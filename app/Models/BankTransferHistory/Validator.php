<?php


namespace RZP\Models\BankTransferHistory;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BANK_TRANSFER_ID        => 'required|string|size:14',
        Entity::PAYER_NAME              => 'nullable|string|max:255',
        Entity::PAYER_ACCOUNT           => 'nullable|string|max:255',
        Entity::PAYER_IFSC              => 'nullable|string|max:255',
        Entity::PAYER_BANK_ACCOUNT_ID   => 'nullable|string|size:14',
        Entity::CREATED_BY              => 'sometimes|email|max:255',
    ];
}
