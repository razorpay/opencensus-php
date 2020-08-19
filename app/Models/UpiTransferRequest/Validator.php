<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::GATEWAY                 => 'required|string',
        Entity::NPCI_REFERENCE_ID       => 'required|string|max:40',
        Entity::PAYEE_VPA               => 'required|string|max:40',
        Entity::PAYER_VPA               => 'required|string',
        Entity::PAYER_BANK              => 'nullable|string|max:40',
        Entity::PAYER_ACCOUNT           => 'nullable|string|max:40',
        Entity::PAYER_IFSC              => 'nullable|string',
        Entity::AMOUNT                  => 'required|integer|min:0',
        Entity::GATEWAY_MERCHANT_ID     => 'required|string',
        Entity::PROVIDER_REFERENCE_ID   => 'required|string',
        Entity::TRANSACTION_REFERENCE   => 'nullable|string',
        Entity::TRANSACTION_TIME        => 'required',
        Entity::REQUEST_PAYLOAD         => 'required|json',
    ];
}
