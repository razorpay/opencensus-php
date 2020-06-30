<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Base;

class Validator extends Base\Validator
{
    const IFSC_LENGTH = 11;

    protected static $createRules = [
        Entity::GATEWAY             => 'required|string',
        Entity::TRANSACTION_ID      => 'required|string|max:255',
        Entity::MODE                => 'required',
        Entity::PAYEE_NAME          => 'nullable|string|max:100',
        Entity::PAYEE_ACCOUNT       => 'required|string|max:40',
        Entity::PAYEE_IFSC          => 'required|string|size:' . self::IFSC_LENGTH,
        Entity::PAYER_NAME          => 'nullable|string|max:100',
        Entity::PAYER_ACCOUNT       => 'nullable|string|max:40',
        Entity::PAYER_IFSC          => 'nullable|string',
        Entity::AMOUNT              => 'required|numeric|min:0',
        Entity::DESCRIPTION         => 'nullable|string|max:255',
        Entity::NARRATION           => 'nullable|string',
        Entity::TIME                => 'required',
        Entity::REQUEST_PAYLOAD     => 'required|json',
    ];
}
