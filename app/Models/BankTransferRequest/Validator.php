<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Base;

class Validator extends Base\Validator
{
    const IFSC_LENGTH = 11;

    protected static $createRules = [
        Entity::GATEWAY                 => 'required|string',
        Entity::TRANSACTION_ID          => 'required|string|max:255',
        Entity::MODE                    => 'required|string|max:4',
        Entity::PAYEE_NAME              => 'nullable|string|max:100',
        Entity::PAYEE_ACCOUNT           => 'required|string|max:40',
        Entity::PAYEE_IFSC              => 'required|string|size:' . self::IFSC_LENGTH,
        Entity::PAYER_NAME              => 'nullable|string|max:100',
        Entity::PAYER_ACCOUNT           => 'nullable|string|max:40',
        Entity::PAYER_IFSC              => 'nullable|string',
        Entity::PAYER_ACCOUNT_TYPE      => 'nullable|string|max:40',
        Entity::PAYER_ADDRESS           => 'nullable|string',
        Entity::AMOUNT                  => 'required|numeric|min:0',
        Entity::CURRENCY                => 'nullable|string|max:5',
        Entity::DESCRIPTION             => 'nullable|string|max:255',
        Entity::NARRATION               => 'nullable|string',
        Entity::TIME                    => 'required',
        Entity::ATTEMPT                 => 'nullable|integer',
        Entity::REQUEST_PAYLOAD         => 'required|json',
        Entity::FIRST_TIME_ON_TEST_MODE => 'sometimes|boolean',
    ];
}
